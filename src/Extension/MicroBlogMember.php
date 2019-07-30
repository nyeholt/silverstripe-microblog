<?php

namespace Symbiote\MicroBlog\Extension;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Assets\Folder;
use Symbiote\MicroBlog\Model\SimpleMemberList;
use Symbiote\MicroBlog\Service\MicroBlogService;
use Symbiote\MicroBlog\Service\TransactionManager;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\Forms\FieldList;
use Symbiote\MicroBlog\Model\AuthenticationToken;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Security\RandomGenerator;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Security\Permission;

/**
 *
 * @author marcus@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class MicroBlogMember extends DataExtension
{
    const FRIENDS = 'Friends';
    const FOLLOWERS = 'Followers';
    const BALANCE_THRESHOLD = -20;

    private static $microblog_group_name = 'Microblog user groups';

    private static $db = array(
        'Username'                    => 'Varchar(64)',
        'PostPermission'            => 'Varchar(64)',
        'VotesToGive'                => 'Int',
        'Balance'                    => 'Int',
        'Up'                        => 'Int',
        'Down'                        => 'Int',
        'LastPostView'                => 'Datetime',
        'DigestType'                => "Enum('none,daily,weekly', 'none')",
    );

    private static $has_one = [
        'UploadFolder'            => Folder::class,

        // where all our friends get added 
        'FriendsGroup'            => SimpleMemberList::class,
        'FollowersGroup'        => SimpleMemberList::class,
    ];

    private static $defaults = array(
        'PostPermission'        => 'Hidden'
    );

    private static $dependencies = [
        'microBlogService'        => '%$' . MicroBlogService::class,
        'transactionManager'    => '%$' . TransactionManager::class
    ];

    static $permission_options = array(
        'Hidden',
        'Friends only',
        'Friends and followers',
        'Logged In',
        'Public'
    );

    static $summary_fields = array(
        'Username',
        'Up',
        'Down',
        'Balance',
    );

    /**
     * @var MicroBlogService
     */
    public $microBlogService;

    /**
     * @var PermissionService
     */
    public $permissionService;

    /**
     * @var TransactionManager 
     */
    public $transactionManager;

    /**
     * Whether empty users should have their usernames set to a not-very-random string. 
     * This is to handle some annoying scenarios encountered in bulk user imports from
     * LDAP type systems.
     * 
     * @var boolean
     */
    public $generateUsername = false;

    private $unreadPosts;

    /**
     * Gets the latest posts that _this_ member can view
     */
    public function getUnreadPosts($target = '')
    {
        // this little cache bit has absolutely no effect because SS will re-create (And requery) the database
        // on EVERY call to currentMember
        if (!$this->unreadPosts) {
            $filter = array(
                'Created:GreaterThan'        => $this->owner->LastPostView,
            );

            if (strlen($target)) {
                $filter['Target'] = $target;
            }

            $this->unreadPosts = $this->microBlogService->globalFeed($filter, $orderBy = 'ID DESC', $since = null, $number = 20, $markViewed = false);
        }

        return $this->unreadPosts;
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('UploadFolder');

        $opts = array_combine(self::$permission_options, self::$permission_options);
        $fields->replaceField('PostPermission', DropdownField::create('PostPermission', _t('ProfileDashlet.POST_PERM', 'Post permissions'), $opts));

        if ($token = $this->getToken()) {
            $fields->addFieldToTab('Root.Main', ReadonlyField::create('AuthToken', 'Your auth token', $token->Token), 'Email');
        }
    }

    public function getToken() {
        $token = AuthenticationToken::get()->filter(['MemberID' => $this->owner->ID])->sort('ID DESC')->first();
        return $token;
    }

    public function onBeforeWrite()
    {
        $token = AuthenticationToken::get()->filter(['MemberID' => $this->owner->ID])->sort('ID DESC')->first();

        if (!$token) {
            $generator = new RandomGenerator();
            $token = AuthenticationToken::create([
                'Token' => $generator->randomToken('sha1'),
                'MemberID' => $this->owner->ID,
            ])->write();
        }
        if (!strlen($this->owner->Username)) {
            $field = $this->owner->Email ? 'Email' : 'FirstName';
            if ($this->owner->$field) {
                $name = $this->owner->$field;
                $name = preg_replace("/[^[:alnum:][:space:]]/ui", '_', $name);
                $this->owner->Username = $name;
            } else {
                if ($this->generateUsername) {
                    $this->owner->Username = microtime(true) . mt_rand(1000, 9999);
                } else {
                    throw new ValidationException("Cannot create user without a username");
                }
            }
        }

        // @TODO Make this allow various utf8 characters
        if (!preg_match('/^[a-z0-9_\. -]+$/i', $this->owner->Username)) {
            throw new ValidationException('Username must only contain word characters');
        }

        if (!$this->owner->ID) {
            // find an existing user with this username and bail if so
            $existing = Member::get()->filter(array('Username' => $this->owner->Username))->first();
            if ($existing && $existing->ID) {
                throw new ValidationException("Username already exists");
            }
        }

        parent::onBeforeWrite();

        if ($this->owner->OwnerID != $this->owner->ID) {
            $this->owner->OwnerID = $this->owner->ID;
        }

        if (!$this->owner->ID) {
            $this->owner->InheritPerms = true;
        }

        $changed = $this->owner->isChanged('Username') || $this->owner->isChanged('FirstName') || $this->owner->isChanged('Surname') || $this->owner->isChanged('Email');

        //		if ($this->owner->ID) {
        //			$this->syncProfile($profile);
        //		} else if ($this->owner->ProfileID && $changed) {
        //			$this->syncProfile($this->owner->Profile());
        //		}

        $this->getGroupFor(self::FRIENDS);
        $this->getGroupFor(self::FOLLOWERS);

        $this->owner->Balance = $this->owner->Up - $this->owner->Down;

        $this->memberFolder();
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
    }

    public function canView()
    {
        return true;
    }

    public function canVote()
    {
        return $this->owner->VotesToGive > 0;
    }


    public function memberFolder()
    {
        if (!$this->owner->UploadFolderID || !$this->owner->UploadFolder()->exists()) {

            $multisiteFolder = null;
            // get the folder for this user
            $name = md5($this->owner->ID . time());
            $path = 'user-files/' . $name;
            if ($multisiteFolder) {
                $path = $multisiteFolder->Name . '/' . $path;
            }

            $this->owner->UploadFolderID = Folder::find_or_make($path)->ID;
        }
        return $this->owner->UploadFolder();
    }

    /**
     * gets the group that this user's friends belong to 
     */
    public function getGroupFor($type)
    {
        $groupType = $type . 'Group';
        $groupTypeID = $type . 'GroupID';

        if ($this->owner->$groupTypeID) {
            return $this->owner->$groupType();
        }

        $title = $this->owner->Email . ' ' . $type;
        $group = SimpleMemberList::get()->filter(array('Title' => $title))->first();
        if ($group && $group->exists()) {
            $this->owner->$groupTypeID = $group->ID;
            return $group;
        } else {
            $createGroup = function () use ($title) {
                $group = SimpleMemberList::create();
                $group->Title = $title;
                $group->write();
                return $group;
            };

            if (Permission::check('ADMIN')) {
                $group = $createGroup();
            } else {
                $group = $this->transactionManager->runAsAdmin($createGroup);
            }
            

            if ($group) {
                $this->owner->$groupTypeID = $group->ID;
            }
            return $group;
        }
    }

    public function toFilteredMap()
    {
        $allowed = array(
            'FirstName',
            'Surname',
            'Username',
            'ID',
        );

        $map = array();
        foreach ($allowed as $prop) {
            $map[$prop] = $this->owner->$prop;
        }

        $map['Title'] = $this->owner->getTitle();

        return $map;
    }

    public function Friends()
    {
        return $this->microBlogService->friendsList($this->owner);
    }

    public function Link()
    {
        return 'timeline/user/' . $this->owner->ID;
    }
}
