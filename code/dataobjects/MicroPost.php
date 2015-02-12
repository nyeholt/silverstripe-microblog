<?php

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class MicroPost extends DataObject { /* implements Syncroable { */
	private static $db = array(
		'Title'				=> 'Varchar(255)',
		'Content'			=> 'Text',
		'Author'			=> 'Varchar(255)',
		'OriginalLink'		=> 'Varchar',
		'OriginalContent'	=> 'Text',
		'IsOembed'			=> 'Boolean',
		'Deleted'			=> 'Boolean',
		'NumReplies'		=> 'Int',
		'Target'			=> 'Varchar',
	);

	private static $has_one = array(
		'ThreadOwner'	=> 'Member',			// owner of the thread this is in
		'Parent'		=> 'MicroPost',
		'Thread'		=> 'MicroPost',
		'Attachment'	=> 'File',

		'PermSource'	=> 'PermissionParent',
	);

	private static $has_many = array(
		'Replies'		=> 'MicroPost.Parent',
	);
	
	private static $defaults = array(
		'PublicAccess'		=> false,
		'InheritPerms'		=> true,		// we'll have  default container set soon
	);
	
	private static $extensions = array(
		'Rateable',
		'Restrictable',
		'TaggableExtension',
	);

	private static $summary_fields = array(
		'PostTitle', 
		'Author',
		'PostSummary',
		'Created'
	);
	
	private static $searchable_fields = array(
		'Title',
		'Content'
	);
	
	private static $dependencies = array(
		'socialGraphService'	=> '%$SocialGraphService',
		'microBlogService'		=> '%$MicroBlogService',
		'securityContext'		=> '%$SecurityContext',
	);
	
	private static $default_sort = 'ID DESC';

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
	 * @var SecurityContext
	 */
	public $securityContext;
	
	/**
	 * @var SyncrotronService 
	 */
	public $syncrotronService;

	public function onBeforeWrite() {
		$member = $this->securityContext->getMember();
		if (!$this->ThreadOwnerID) {
			if ($this->ParentID) {
				$this->ThreadOwnerID = $this->Parent()->ThreadOwnerID;
			} else {
				$this->ThreadOwnerID = $member->ID;
			}
		}

		if (!$this->Author) {
			$this->Author = $this->securityContext->getMember()->getTitle();
		}
		
		if (!$this->Title) {
			if ($this->AttachmentID) {
				$this->Title = basename($this->Attachment()->Filename);
			} else {
				$this->Title = str_replace("\n", " ", $this->socialGraphService->extractTitle($this->Content));
			}
		}
		parent::onBeforeWrite();
	}
	
	public function isUnreadByUser($member = null) {
		if (!$member) {
			$member = $this->securityContext->getMember();
		}
		
		if ($member && $member->ID) {
			return strtotime($this->Created) > strtotime($member->LastPostView);
		}
	}
	
	/**
	 * has this post been edited? return 'true' if the diff between created and last edited
	 * is greater than a 'grace' period.
	 */
	public function isEdited($grace = 300) {
		return (strtotime($this->LastEdited) - strtotime($this->Created)) > $grace;
	}
	
	public function PostSummary() {
		return $this->obj('Content')->ContextSummary(40, 'poweapfawepofj');
	}
	
	public function PostTitle() {
		return $this->obj('Title')->LimitCharacters(40, 'afwef');
	}
	
	public function UntaggedContent() {
		$content = $this->Content;
		if (preg_match_all('/#([a-z0-9_-]+)/is', $content, $matches)) {
			
			foreach ($matches[1] as $tag) {
				$link = Controller::join_links(TimelineController::URL_SEGMENT, '?tags=' . urlencode($tag));
				$content = str_replace('#' . $tag, "[\\#$tag]($link)", $content);
			}
		}
		return DBField::create_field('Text', $content);
	}
	
	/**
	 * Handle the wilson rating specially 
	 * 
	 * @param type $field
	 * @return string 
	 */
	public function hasOwnTableDatabaseField($field) {
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

	public function IsImage() {
		return $this->socialGraphService->isImage($this->Content);
	}
	
	/**
	 * Check contents of the post for things like tags, user references, external
	 * references etc. 
	 */
	public function analyseContent() {
		$this->microBlogService->extractTags($this);
		$this->socialGraphService->convertPostContent($this);
	}
	
	/**
	 * Tag this post with a particular tag
	 * 
	 * @param string $tag
	 */
	public function tag($tag) {
		if (!preg_match('/[a-z0-9_-]/i', $tag)) {
			return;
		}

		$existing = PostTag::get()->filter(array('Title' => $tag))->first();
		if (!$existing) {
			$existing = PostTag::create();
			$existing->Title = $tag;
			$existing->write();
		}
		$this->Tags()->add($existing, array('Tagged' => date('Y-m-d H:i:s')));
		return $existing;
	}

	/**
	 * When 'deleting' an object, we actually just remove all its content 
	 */
	public function delete() {
		if ($this->checkPerm('Delete')) {
			$this->Tags()->removeAll();
			// if we have replies, we can't delete completely!
			if ($this->Replies()->exists() && $this->Replies()->count() > 0) {
				$count = $this->Replies()->count();
				$item = $this->Replies()->first();
				$this->Deleted = true;
				$this->Content = _t('MicroPost.DELETED', '[deleted]');
				$this->Author = $this->Content;
				$this->write();
			} else {
				return parent::delete();
			}
		}
	}

	/**
	 * handles SiteTree::canAddChildren, useful for other types too
	 */
	public function canAddChildren() {
		if ($this->checkPerm('View')) {
			return true;
		} else {
			return false;
		}
	}

	public function formattedPost() {
		return Convert::raw2xml($this->Content);
	}

	public function Link() {
		$additional = '';
		if (strlen($this->Title)) {
			$additional = str_replace('.', '-', URLSegmentFilter::create()->filter($this->Title));
		}
		
		$curr = Controller::curr();
		
		if ($curr && $curr instanceof TimelineController) {
			return $curr->Link('show/' . $this->ID . '/' . $additional);
		}
		
		return 'timeline/show/' . $this->ID . '/' . $additional;
		return 'timeline/show/' . $this->ID . $additional;
	}
	
	public function AbsoluteLink() {
		return Director::absoluteURL($this->Link());
	}

	public function Posts() {
		return $this->microBlogService->getRepliesTo($this);
	}
	
	/**
	 * We need to define a  permission source to ensure the 
	 * ParentID isn't used for permission inheritance 
	 */
	public function permissionSource() {
		if ($this->ParentID) {
			return $this->Parent();
		}
		if ($this->PermSourceID) {
			return $this->PermSource();
		}
		
		if ($this->Target && strpos($this->Target, ',')) {
			list($type, $id) = explode(',', $this->Target);
			$item = DataList::create($type)->byID($id);
			if ($item) {
				return $item;
			}
		}
		
		// @TODO Move this to an extension that can be enabled per-project instead of by default. 
		// otherwise, find a post by this user and use the shared parent
//		$owner = $this->Owner();
//		if ($owner && $owner->exists()) {
//			$source = $owner->postPermissionSource();
//			$this->PermSourceID = $source->ID;
//			// TODO: Remove this; it's only used until all posts have an appropriate permission source...
//			if ($this->ID) {
//				Restrictable::set_enabled(false);
//				$this->write();
//				Restrictable::set_enabled(true);
//			}
//			return $source;
//		}
	}

	public function forSyncro() {
		$props = $this->syncrotronService->syncroObject($this);
		unset($props['PermSourceID']);
		
		$props['Post_ThreadEmail'] = $this->ThreadOwner()->Email;
		$props['Post_OwnerEmail'] = $this->Owner()->Email;
		
		return $props;
	}

	public function fromSyncro($properties) {
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
