<?php

namespace Symbiote\MicroBlog\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Assets\File;
use Symbiote\MicroBlog\Extension\ScoredRateable;
use Symbiote\MicroBlog\Extension\TaggableExtension;
use SilverStripe\Security\Security;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataList;
use SilverStripe\View\Parsers\URLSegmentFilter;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use Symbiote\MicroBlog\Service\TransactionManager;

/**
 * @author marcus@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class MicroPost extends DataObject
{ /* implements Syncroable { */

    private static $table_name = 'MicroPost';

    private static $db = array(
        'Title'                => 'Varchar(255)',
        'Content'            => 'Text',
        'RenderedContent'    => 'Text',
        'Author'            => 'Varchar(255)',
        'OriginalLink'        => 'Varchar',
        'OriginalContent'    => 'Text',
        'IsOembed'            => 'Boolean',
        'Deleted'            => 'Boolean',
        'Hidden'            => 'Boolean',
        'NumReplies'        => 'Int',
        'Target'            => 'Varchar',        // ClassName,ID
        'TargetLink'        => 'Varchar(255)',      // URL where the post was created
        'PostType'            => 'Varchar',
        'DisableReplies'    => 'Boolean',
        'PublicAccess' => 'Boolean',
        'NumChildren' => 'Int',
    );

    private static $has_one = array(
        'ThreadOwner'    => Member::class,            // owner of the thread this is in
        'Parent'        => MicroPost::class,
        'Thread'        => MicroPost::class,
        'Attachment'    => File::class,
        'Owner'         => Member::class,
    );

    private static $has_many = array(
        'Replies'        => MicroPost::class . '.Parent',
    );

    private static $many_many = array(
        'Mentions'      => Member::class,
    );

    private static $defaults = array(
        'PublicAccess'        => false,
        'NumChildren'           => 0,
        'InheritPerms'        => true,        // we'll have  default container set soon
    );

    private static $extensions = array(
        ScoredRateable::class,
        // 'Restrictable',
        TaggableExtension::class,
    );

    private static $summary_fields = array(
        'PostTitle',
        'Author',
        'PostSummary',
        'Created'
    );

    private static $searchable_fields = array(
        'Title',
        'Content',
        'Tags.Title',
    );

    private static $default_sort = 'ID DESC';

    /**
     * Should deletes be complete from the DB or just a 'soft' delete that has things filtered
     * instead?
     *
     * @var boolean
     */
    private static $soft_delete = false;

    /**
     * Do we automatically detect oembed data and change comments?
     *
     * Override using injector configuration
     *
     * @var boolean
     */
    public $oembedDetect = true;

    /**
     * @var SocialGraphService
     */
    public $socialGraphService;

    /**
     * @var MicroBlogService
     */
    public $microBlogService;


    /**
     * @var SyncrotronService
     */
    public $syncrotronService;

    private $afterWriteRender = false;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        if ($this->Deleted && !Permission::check('ADMIN')) {
            // remove the 'original content' field for non-admins
            $fields->replaceField('OriginalContent', ReadonlyField::create('DeletedMessage', "Original Content", "Only admins may view deleted content"));
        }

        return $fields;
    }

    public function onBeforeWrite()
    {
        $member = Security::getCurrentUser();
        if (!$this->ThreadOwnerID) {
            if ($this->ParentID) {
                $this->ThreadOwnerID = $this->Parent()->ThreadOwnerID;
            } else {
                $this->ThreadOwnerID = $member->ID;
            }
        }

        if (!$this->OwnerID) {
            $this->OwnerID = $member->ID;
        }

        if (!$this->Author) {
            $this->Author = Security::getCurrentUser()->getTitle();
        }

        if ($this->AttachmentID && strlen($this->Content) == 0) {
            $attachment = $this->Attachment();
            $link = '';
            if ($attachment instanceof Image) {
                $scaled = $attachment->MaxWidth(1024);
                $link = $scaled->Link();
                $this->Content = '![' . $attachment->Title . '](' . $link . ')';
            } else {
                $link = $attachment->Link();
                $this->Content = '[' . $attachment->Title . '](' . $link . ')';
            }
        }

        if (!$this->Title) {
            if ($this->AttachmentID) {
                $this->Title = basename($this->Attachment()->Filename);
            } else {
                $this->Title = str_replace("\n", " ", $this->socialGraphService->extractTitle($this->Content));
            }
        }
        parent::onBeforeWrite();

        if ($this->ID) {
            $this->RenderedContent = $this->Content; //  $this->renderWith('PostContent')->raw();
        } else {
            $this->afterWriteRender = true;
        }
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if ($this->afterWriteRender) {
            $this->afterWriteRender = false;
            $this->write();
        }

        $mentions = $this->mentionedMembers();

        if (count($mentions)) {
            foreach ($mentions as $mentioned) {
                $this->Mentions()->add($mentioned);
            }
        } else {
            $this->Mentions()->removeAll();
        }
    }


    public function toFilteredMap()
    {
        $map = $this->toMap();

        if (!$this->canEdit()) {
            unset($map['OriginalContent']);
        }

        $map['CanEdit'] = $this->canEdit() ? "1" : "0";

        return $map;
    }

    /**
     * Gives access to this micropost, based on information in the $to array
     *
     * @param array $to
     *			The people/groups this post is being sent to. This is an array of
     *			- logged_in: boolean (logged in users; uses a system config setting to determine which group represents 'logged in'
     *			- members: an array, or comma separated string, of member IDs
     *			- groups: an array, or comma separated string, of group IDs
     */
    public function giveAccessTo($to)
    {
        if ($to) {
            $grantTo = array();
            if (isset($to['logged_in']) && $to['logged_in']) {
                // find the 'logged in' group, and grant to that.
                $groups = null;
                if (class_exists('Multisites')) {
                    $groups = Multisites::inst()->getCurrentSite()->LoggedInGroups()->toArray();
                } else {
                    $groups = SiteConfig::current_site_config()->LoggedInGroups()->toArray();
                }
                if ($groups) {
                    $grantTo = array_merge($grantTo, $groups);
                }
            }
            // todo evaluate security implication of posting to arbitrary members...
            // do we need to check 'friends' status here?
            if (isset($to['members']) && count($to['members'])) {
                if (!is_array($to['members'])) {
                    $to['members'] = explode(',', $to['members']);
                }
                foreach ($to['members'] as $memberId) {
                    $id = (int)$memberId;
                    $toMember = Member::get()->byID($id);
                    if ($toMember) {
                        $grantTo[] = $toMember;
                    }
                }
            }

            if (isset($to['groups']) && count($to['groups'])) {
                if (!is_array($to['groups'])) {
                    $to['groups'] = explode(',', $to['groups']);
                }
                foreach ($to['groups'] as $groupId) {
                    $groupId = (int)$groupId;
                    $group = Group::get()->byID($groupId);
                    if ($group) {
                        $grantTo[] = $group;
                    }
                }
            }

            if (count($grantTo)) {
                foreach ($grantTo as $grantee) {
                    $this->permissionService->grant($this, 'View', $grantee);
                }
            }

            // what about to the public?
            if (isset($to['public'])) {
                $this->PublicAccess = true;
                $this->write();
            }
        }
    }

    /**
     * Has this post been read by the given user?
     *
     * @param Member $member
     * @return boolean
     */
    public function isUnreadByUser($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        if ($member && $member->ID) {
            return strtotime($this->Created) > strtotime($member->LastPostView);
        }
    }

    /**
     * has this post been edited? return 'true' if the diff between created and last edited
     * is greater than a 'grace' period.
     */
    public function isEdited($grace = 300)
    {
        return (strtotime($this->LastEdited) - strtotime($this->Created)) > $grace;
    }

    /**
     * Get a summary of the post
     *
     * @return string
     */
    public function PostSummary()
    {
        return $this->obj('Content')->ContextSummary(40, 'poweapfawepofj');
    }

    /**
     * Returns the title of this post (trimmed down in length for sanity)
     *
     * @return string
     */
    public function PostTitle()
    {
        return $this->obj('Title')->LimitCharacters(40, 'afwef');
    }

    /**
     * Get the content of this post with hash-tags converted to links
     *
     * @return string
     */
    public function ConvertedContent()
    {
        $content = $this->Content;
        if (preg_match_all('/#([a-z0-9_-]+)/is', $content, $matches)) {

            foreach ($matches[1] as $tag) {
                $link = Controller::join_links('micropost', '?tags=' . urlencode($tag));
                $content = str_replace('#' . $tag, "[\\#$tag]($link)", $content);
            }
        }
        return DBField::create_field('Text', $content);
    }

    public static function handle_video($arguments, $url, $parser, $shortcode)
    {
        $attrs = array();
        $attrs[] = isset($arguments['w']) ? 'width="' . Convert::raw2xml($arguments['w']) . '"' : '';
        $attrs[] = isset($arguments['h']) ? 'height="' . Convert::raw2xml($arguments['h']) . '"' : '';
        $attrs[] = isset($arguments['controls']) ? 'controls="' . Convert::raw2xml($arguments['controls']) . '"' : '';

        $tag = '<video ' . implode(' ', $attrs) . '>';
        $tag .= '<source src="' . Convert::raw2att($url) . '" type="video/mp4"></source>';
        $tag .= '</video>';
        return $tag;
    }

    public function getPostTarget()
    {
        if ($this->Target && strpos($this->Target, ',')) {
            list($type, $id) = explode(',', $this->Target);
            $item = DataList::create($type)->byID($id);
            return $item->canView() ? $item : null;
        }
    }

    /**
     * Whether the current context is that of the post target.
     *
     * @return boolean
     */
    public function currentContext()
    {
        $tgt = Controller::curr()->getRequest()->getVar('target');
        return strlen($tgt) > 0 && $this->Target == $tgt;
    }


    /**
     * Get the list of members mentioned in this post
     */
    public function mentionedMembers()
    {
        $members = array();
        if (preg_match_all('/@(.*?):(\d+)/', $this->Content, $matches)) {
            foreach ($matches[2] as $match) {
                $member = Member::get()->byID((int)$match);
                if ($member && $member->ID) {
                    $members[] = $member;
                }
            }
        }
        return $members;
    }

    /**
     * Handle the wilson rating specially
     *
     * @param type $field
     * @return string
     */
    public function hasOwnTableDatabaseField($field)
    {
        if ($field == 'WilsonRating') {
            return "Double";
        }
        if ($field == 'ActiveRating') {
            return "Int";
        }
        if ($field == 'PositiveRating') {
            return "Int";
        }
        return parent::hasOwnTableDatabaseField($field);
    }

    /**
     * Is this post an image?
     *
     * @return boolean
     */
    public function IsImage()
    {
        return $this->socialGraphService->isImage($this->Content);
    }

    /**
     * Check contents of the post for things like tags, user references, external
     * references etc.
     */
    public function analyseContent()
    {
        $this->microBlogService->extractTags($this);
        $this->socialGraphService->convertPostContent($this);
    }

    /**
     * Tag this post with a particular tag
     *
     * @param string $tag
     */
    public function tag($tags, $clearExisting = false)
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }

        if ($clearExisting) {
            $this->Tags()->removeAll();
        }

        $created = array();
        foreach ($tags as $tag) {
            if (!preg_match('/[a-z0-9_-]/i', $tag)) {
                continue;
            }
            $existing = PostTag::get()->filter(array('Title' => $tag))->first();
            if (!$existing) {
                $existing = PostTag::create();
                $existing->Title = $tag;
                $existing->write();
            }
            $this->Tags()->add($existing, array('Tagged' => date('Y-m-d H:i:s')));
            $created[] = $existing;
        }
        return $created;
    }

    /**
     * Gets the list of current votes on this object by the current user
     *
     * @param Member $user
     *
     * @return ArrayList
     */
    public function currentVotesByUser($user = null)
    {
        if (!$user) {
            $user = Security::getCurrentUser();
        }
        $votes = MicroPostVote::get()->filter(array('UserID' => $user->ID, 'PostID' => $this->ID));
        return $votes->toArray();
    }

    /**
     * When 'deleting' an object, we actually just remove all its content
     */
    public function delete()
    {
        $this->RenderedContent = '';
        if ($this->canDelete()) {
            $this->Tags()->removeAll();
            if ($this->ParentID) {
                $parent = $this->Parent();
                Injector::inst()->get(TransactionManager::class)->runAsAdmin(function () use ($parent) {
                    $num = $parent->NumChildren;
                    $parent->NumChildren = $num > 0 ? $num - 1 : 0;
                    $parent->write();
                });
            }
            // if we have replies, we can't delete completely!
            if ($this->config()->soft_delete || ($this->Replies()->exists() && $this->Replies()->count() > 0)) {
                $count = $this->Replies()->count();
                $item = $this->Replies()->first();
                $this->Deleted = true;
                $this->OriginalContent = $this->Author . "\n\n" . $this->Content;
                $this->Content = _t('MicroPost.DELETED', '[deleted]');
                $this->Author = $this->Content;
                $this->write();
            } else {
                return parent::delete();
            }
        }
    }

    public function canView($member = null)
    {
        $can = parent::canView($member);
        
        $config = SiteConfig::current_site_config();

        $public = $this->PublicAccess && $config->canViewPages($member);

        return $can || $public || (Security::getCurrentUser() && $this->OwnerID === Security::getCurrentUser()->ID);
    }

    public function canEdit($member = null)
    {
        $can = parent::canEdit();
        return $can || (Security::getCurrentUser() && $this->OwnerID === Security::getCurrentUser()->ID);
     }

    /**
     * handles SiteTree::canAddChildren, useful for other types too
     */
    public function canAddChildren()
    {
        if ($this->checkPerm('View')) {
            return true;
        } else {
            return false;
        }
    }

    public function formattedPost()
    {
        return Convert::raw2xml($this->Content);
    }

    public function Link()
    {
        $additional = '';
        if (strlen($this->Title)) {
            $additional = str_replace('.', '-', URLSegmentFilter::create()->filter($this->Title));
        }

        $curr = Controller::curr();

        if ($curr && $curr instanceof TimelineController) {
            return $curr->Link('show/' . $this->ID . '/' . $additional);
        }

        return 'microblog/show/' . $this->ID . '/' . $additional;
    }

    public function ThreadLink()
    {
        if ($this->ThreadID != $this->ID) {
            return $this->Thread()->Link();
        }
        return $this->Link();
    }

    public function AbsoluteThreadLink()
    {
        return Director::absoluteURL($this->ThreadLink());
    }

    public function AbsoluteLink()
    {
        return Director::absoluteURL($this->Link());
    }

    /**
     * Gets all the replies to this post
     *
     * @return ArrayList
     */
    public function Posts()
    {
        return $this->microBlogService->getRepliesTo($this);
    }



    /**
     * We need to define a  permission source to ensure the
     * ParentID isn't used for permission inheritance
     */
    public function permissionSource()
    {
        if ($this->ParentID) {
            return $this->Parent();
        }
        if ($this->PermSourceID) {
            return $this->PermSource();
        }

        if ($this->ID && $this->Target && strpos($this->Target, ',')) {
            list($type, $id) = explode(',', $this->Target);
            $item = DataList::create($type)->byID($id);
            if ($item) {
                return $item;
            }
        }
    }

    /**
     * Get a list of all the members who should receive notifications based on the
     * notificationType variable
     *
     * @param string $notificationType
     *				The notification type being sent
     * @return array
     */
    public function getRecipients($notificationType)
    {
        switch ($notificationType) {
            case 'MICRO_POST_CREATED': {
                    $members = $this->mentionedMembers();
                    return $members;
                }
        }
    }

    /**
     * Update the list of fields that are indexed for a microblog post
     *
     * This provides cleaner direct support for the Elastic search module
     *
     * @param ArrayObject $fieldValues
     */
    public function updateSearchableData(ArrayObject $fieldValues)
    {
        $tags = $this->Tags()->column('Title');
        $fieldValues['Tags'] = $tags;
    }

    /**
     * Return a list of available keywords in the format
     * array('keyword' => 'A description') to help users format notification fields
     * @return array
     */
    public function getAvailableKeywords()
    {
        return array(
            'Content'        => 'Raw content of the post',
            'HTMLContent'    => 'Rendered HTML of the post content',
            'Title'            => 'Title of the post (if set)',
            'Link'            => 'Relative link to the individual post',
            'AbsoluteLink'    => 'Absolute link to the individual post',
            'ThreadLink'    => 'Relative link to the thread containing the post',
            'AbsoluteThreadLink'    => 'Absolute link to the thread containing the post'
        );
    }

    /**
     * Gets an associative array of data that can be accessed in
     * notification fields and templates
     * @return array
     */
    public function getNotificationTemplateData()
    {
        return array(
            'Content'        => $this->Content,
            'HTMLContent'    => $this->ConvertedContent(),
            'Title'            => $this->Title,
            'Link'            => $this->Link(),
            'AbsoluteLink'    => $this->AbsoluteLink(),
            'ThreadLink'    => $this->ThreadLink(),
            'AbsoluteThreadLink'    => $this->AbsoluteThreadLink(),
        );
    }

    public function forSyncro()
    {
        $props = $this->syncrotronService->syncroObject($this);
        unset($props['PermSourceID']);

        $props['Post_ThreadEmail'] = $this->ThreadOwner()->Email;
        $props['Post_OwnerEmail'] = $this->Owner()->Email;

        return $props;
    }

    public function fromSyncro($properties)
    {
        $this->syncrotronService->unsyncroObject($properties, $this);

        // now make sure the other things are aligned
        if (isset($properties->Post_ThreadEmail)) {
            $member = DataList::create('Member')->filter(array('Email' => $properties->Post_ThreadEmail))->first();
            if ($member) {
                $this->ThreadOwnerID = $member->ID;
            }
        }

        if (isset($properties->Post_OwnerEmail)) {
            $member = DataList::create('Member')->filter(array('Email' => $properties->Post_OwnerEmail))->first();
            if ($member) {
                $this->OwnerID = $member->ID;
            }
        }

        // bind the correct permission source
        $this->permissionSource();
    }
}
