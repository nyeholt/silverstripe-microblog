<?php

/**
 * Controller that handles timeline interaction
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class TimelineController extends ContentController {
	const URL_SEGMENT = 'timeline';
	
	const POOR_USER_THRESHOLD = -100;
	
	private static $default_public = false;
	private static $default_logged_in = true;
	
	private static $jquery_lib = 'framework/thirdparty/jquery/jquery.js';
	private static $jquery_ui_lib = 'framework/thirdparty/jquery-ui/jquery-ui.js';
	
	private static $options = array(
		'Threaded'			=> false,			// should we show a threaded view?
		'Replies'			=> true,			// allow replies?
		'Voting'			=> true,			// allow voting?
//		'Edits'				=> true,
		
		'ShowReply'			=> true,			// show the reply box all the time?
		'Sorting'			=> false,			// allow sorting? (not exposed at present, a few bugs exist)
		'UserTitle'			=> false,			// should users be allowed to set titles?
		'ShowTitlesOnly'	=> false,			// Should we only show the titles when listing posts? (more forum like)
		'ShowTitlesInPost'	=> false,			// Should titles be displayed within post content?
	);

	private static $allowed_actions = array(
		'digest',
		'StatusForm',
		'PostForm',
		'FollowForm',
		'UnFollowForm',
		'UploadForm',
		'flatlist',
		'Timeline',
		'user',
		'show',
		'rss',
	);
	
	/**
	 * @var MicroBlogService
	 */
	public $microBlogService;
	
	/**
	 * @var SecurityContext
	 */
	public $securityContext;
	
	protected $parentController = null;
	protected $showReplies = true;
	
	/**
	 * Context user indicates who 'owns' the feed of posts being viewed
	 * 
	 * Only really relevant when deciding whether to show the 'add post' form in 
	 * Dashlet view mode, which means this code really should be refactored. 
	 * 
	 * @var Member
	 */
	protected $contextUser = null;

	static $dependencies = array(
		'microBlogService'		=> '%$MicroBlogService',
		'securityContext'		=> '%$SecurityContext',
	);

	public function __construct($parent = null, $replies = true, $context = null) {
		if ($parent instanceof DataObject) {
			parent::__construct($parent);
		} else {
			parent::__construct();
			$this->parentController = $parent;
		}
		
		$this->showReplies = $replies;
		$this->contextUser = $context ? $context : Member::currentUser();
	}

	public function init() {
		if ($this->getSession() && $this->data()->exists()) {
			Versioned::choose_site_stage($this->getSession());
		} else {
			Versioned::reading_stage('Live');
		}
		
		parent::init();
		
		Requirements::block(THIRDPARTY_DIR . '/prototype/prototype.js');
		if (self::config()->jquery_lib != THIRDPARTY_DIR . '/jquery/jquery.js') {
			Requirements::block(THIRDPARTY_DIR . '/jquery/jquery.js');
		}
		
		Requirements::javascript(self::config()->jquery_lib);
		Requirements::javascript(self::config()->jquery_ui_lib);
		
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(THIRDPARTY_DIR . '/javascript-templates/tmpl.js');
		Requirements::javascript(THIRDPARTY_DIR . '/javascript-loadimage/load-image.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-form/jquery.form.js');
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/i18n.js');
		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/ssui.core.js');
		
		Requirements::javascript('webservices/javascript/webservices.js');
		
		
		Requirements::combine_files('_microblog_combined.js', array(
			'microblog/javascript/jquery.autogrow-textarea.js',
			'microblog/javascript/jquery-textcomplete-0.3.7/jquery.overlay.js',
			'microblog/javascript/jquery-textcomplete-0.3.7/jquery.textcomplete.js',
			'microblog/javascript/showdown/showdown.min.js',
			'microblog/javascript/marked.js',
			'microblog/javascript/date.js',
			'microblog/javascript/microblog.js',
			'microblog/javascript/timeline.js',
			'microblog/javascript/local-storage.js',
			'microblog/javascript/microblog-statesave.js',
		));

		Requirements::css('microblog/javascript/jquery-textcomplete-0.3.7/jquery.textcomplete.css');
		
		Requirements::css('microblog/css/timeline.css');

		$member = $this->securityContext->getMember();
		if ($member && $member->ID) {
			if ($member->Balance < self::POOR_USER_THRESHOLD) {
				throw new Exception("Broken pipe");
			}
		}
	}
	
	public function IsEnabled($option) {
		$opts = self::config()->options;
		return isset($opts[$option]) && $opts[$option]; 
	}
	
	private $arrayOptions; 
	public function Options() {
		if (!$this->arrayOptions) {
			$this->arrayOptions = ArrayData::create(self::config()->options);
		}
		return $this->arrayOptions;
	}

	public function index() {
		if ($this->request->isAjax()) {
			return $this->renderWith('FullTimeline');
		}
		return $this->renderWith(array('FullTimeline', 'Page'));
	}

	public function MemberDetails() {
		$m = $this->securityContext->getMember();
		if ($m) {
			return Varchar::create_field('Varchar', Convert::raw2json(array(
				'Title'			=> $m->getTitle(),
				'FirstName'		=> $m->FirstName,
				'Surname'		=> $m->Surname,
				'MemberID'		=> $m->ID,
			)));
		}
	}
	
	public function digest() {
		if (!Permission::check('ADMIN')) {
			return $this->httpError(403);
		}
		$posts = $this->microBlogService->globalFeed(array(
			'Created:GreaterThan'	=> date('Y-m-d 00:00:00', strtotime('-1 month')),
		), $orderBy = 'ID DESC', $since = null, $number = 10, $markViewed = false);

		if (!count($posts)) {
			return;
		}

		$content = SSViewer::execute_template('DigestEmail', ArrayData::create(array(
			'Posts'		=> $posts,
			'Member'	=> Member::currentUser()
		)));
		
		$content = HTTP::absoluteURLs($content);
		
		echo $content;
	}
	
	/**
	 * Show a particular post
	 * 
	 * Note that this MAY be triggered directly from a request via 'viewpost' routing, so 
	 * don't rely on the $this->data() var to be filled
	 * 
	 * @return type 
	 */
	public function show() {
		// cast with int here forces the rest of the text to be stripped
		$id = (int) $this->request->param('ID');

		if ($id) {
			$since = $this->request->getVar('since');
			if (!$since) {
				$since = $id - 1;
			} else {
				
			}

			$posts = $this->microBlogService->getStatusUpdates(null, array('ID' => 'ASC'), $since, false, false, array(), 0, 1);
			$post = $posts->first();
			
			if (!$post) {
				return Security::permissionFailure();
			}

			$options = $this->Options();
			$options->Replies = true;
			$options->ShowTitlesOnly = false;
			
			$timeline = trim($this->customise(array('Post' => $id, 'ForceContent' => true, 'Posts' => $posts, 'Options' => $options))->renderWith('Timeline'));
			
			if (Director::is_ajax()) {
				return $timeline;
			}

			$data = array(
				'Timeline'		=> $timeline,
				'OwnerFeed'		=> $timeline,
				'Post'			=> $id,
			);

			$timeline = $this->customise($data)->renderWith('FullTimeline');
			
			return $this->customise(array('Title' => $post->Title, 'Content' => $timeline))->renderWith(array('TimelineController_show', 'Page'));
		}
	}
	
	public function StatusForm () {
		$fields = new FieldList(
			$taf = new TextareaField('Content', _t('MicroBlog.POST', 'Post'))
		);
		$taf->setRows(3);
		$taf->setColumns(120);
		
		$actions = new FieldList(
			new FormAction('savepost', _t('MicroBlog.SAVE', 'Add'))
		);
		
		$form = new Form($this, 'StatusForm', $fields, $actions);
		return $form;
	}

	public function FollowForm() {
		$fields = new FieldList(
			new HiddenField('OtherID', 'Other', $this->ViewingUserID())
		);
		$actions = new FieldList(
			new FormAction('follow', _t('MicroBlog.FOLLOW', 'Follow'))
		);
		return new Form($this, 'FollowForm', $fields, $actions);
	}
	
	public function UnFollowForm() {
		$fields = new FieldSet(
			new HiddenField('OtherID', 'Other', $this->ViewingUserID())
		);
		$actions = new FieldSet(
			new FormAction('unfollow', _t('MicroBlog.UNFOLLOW', 'UnFollow'))
		);
		return new Form($this, 'UnFollowForm', $fields, $actions);
	}
	
	/**
	 * TODO Update to match new api... 
	 */
	public function follow($data, $form) {
		if (!$this->securityContext->getMember()) {
			return Security::permissionFailure($this);
		}
		$otherID = (int) (isset($data['OtherID']) ? $data['OtherID'] : null);
		if ($otherID) {
			$other = DataObject::get_by_id('Member', $otherID);
			$this->microBlogService->addFollower($other, $this->securityContext->getMember());
		}
		$this->redirectBack();
	}
	
	/**
	 * TODO Update to match new api... 
	 */
	public function unfollow($data, $form) {
		if (!$this->securityContext->getMember()) {
			return Security::permissionFailure($this);
		}
		$otherID = (int) (isset($data['OtherID']) ? $data['OtherID'] : null);
		if ($otherID) {
			$other = DataObject::get_by_id('Member', $otherID);
			$this->microBlogService->removeFollower($other, $this->securityContext->getMember());
		}
		$this->redirectBack();
	}
	
	/**
	 * Output RSS feed
	 */
	public function rss() {
		$entries = $this->microBlogService->globalFeed();
		$feed = new RSSFeed($entries, $this->Link('rss'), 'Global updates');
		$feed->outputToBrowser();
	}

	
	public function PostForm () {
		$fields = FieldList::create();
		
		if ($this->Options()->UserTitle) {
			$fields->push($title = TextField::create('Title', _t('MicroBlog.TITLE', 'Title')));
			$title->setAttribute('placeholder', _t('MicroBlog.TITLE_PLACEHOLDER', 'Title (optional)'));
		}

		$fields->push($taf = new TextareaField('Content', _t('MicroBlog.POST', 'Post')));

		$taf->setAttribute('placeholder', _t('MicroBlog.CONTENT_PLACEHOLDER', 'Add content here, eg text or a link'));
		$taf->setRows(3);
		$taf->addExtraClass('expandable');
		$taf->addExtraClass('postContent');
		$taf->addExtraClass('preview');
		
		$public = CheckboxField::create('PublicUsers', 'Public users', Config::inst()->get('TimelineController', 'default_public'));
		$loggedIn = CheckboxField::create('LoggedInUsers', "Logged in users", Config::inst()->get('TimelineController', 'default_logged_in'));
		$member = MultiSelect2Field::create('Members', "To", Member::get()->map()->toArray())->setMultiple(true);
		$group = MultiSelect2Field::create("Groups", "To Groups", Group::get()->filter("ParentID", 0)->map()->toArray())->setMultiple(true);
		
		$fields->push($public);
		$fields->push($loggedIn);
		$fields->push($member);
		$fields->push($group);
		
		$target = $this->getTargetFilter();
		if ($target) {
			$fields->push(HiddenField::create('PostTarget', '', $target));
		}
		
		$actions = new FieldList(
			new FormAction('savepost', _t('MicroBlog.SAVE', 'Add'))
		);
		
		$form = new Form($this, 'PostForm', $fields, $actions);
		
		$this->extend('updatePostForm', $form);
		
		return $form;
	}
	
	public function savepost($data, Form $form) {
		if (!$this->securityContext->getMember()) {
			return Security::permissionFailure($this);
		}
		$post = null;

		if (isset($data['Content']) && strlen($data['Content'])) {
			$post = $this->savePostFromForm($data, $form);
		}

		if (Director::is_ajax() && $post && $post->ID) {
			$result = array(
				'response'		=> $post->toMap(),
			);
			$this->response->addHeader('Content-type', 'application/json');
			return Convert::raw2json($result);
		}
		if (Director::is_ajax()) {
			return '{"message": "invalid"}';
		}
		
		$this->redirectBack();
	}
	
	/**
	 * Called after a post is created prior, allowing other controllers to do things with the post
	 * 
	 */
	protected function afterPostCreated(MicroPost $post) {
		
	}
	
	public function UploadForm() {
		Requirements::combine_files('minimal_uploadfield.js', array(
			THIRDPARTY_DIR . '/jquery-fileupload/jquery.iframe-transport.js',
			THIRDPARTY_DIR . '/jquery-fileupload/jquery.fileupload.js',
		));

		$fields = new FieldList($field = new FileField('Attachment', _t('MicroBlog.FILE_UPLOAD', 'Upload files')));
		$actions = new FieldList(new FormAction('uploadFiles', _t('MicroBlog.UPLOAD_FILES', 'Upload')));
		
		$folderName = $this->securityContext->getMember()->memberFolder()->Filename;
		if (strpos($folderName, 'assets/') === 0) {
			$folderName = substr($folderName, 7);
		}
		$field->relationAutoSetting = false;
		$field->setFolderName($folderName);
		
		// these values will be copied across from the post form at post time
		$public = CheckboxField::create('PublicUsers', 'Public users', Config::inst()->get('TimelineController', 'default_public'));
		$loggedIn = CheckboxField::create('LoggedInUsers', "Logged in users", Config::inst()->get('TimelineController', 'default_logged_in'));
		$member = MultiSelect2Field::create('Members', "To", Member::get()->map()->toArray())->setMultiple(true);
		$group = MultiSelect2Field::create("Groups", "To Groups", Group::get()->filter("ParentID", 0)->map()->toArray())->setMultiple(true);
		
		$fields->push($public);
		$fields->push($loggedIn);
		$fields->push($member);
		$fields->push($group);
		
		$target = $this->getTargetFilter();
		if ($target) {
			$fields->push(HiddenField::create('PostTarget', '', $target));
		}
		
		$form = new Form($this, 'UploadForm', $fields, $actions);
		$form->addExtraClass('fileUploadForm');
		return $form;
	}
	
	
	public function uploadFiles($data, Form $form) {
		if (!$this->securityContext->getMember()) {
			throw new PermissionDeniedException('Write');
		}
		if (isset($data['Attachment'])) {
			$post = $this->savePostFromForm($data, $form);
			// need to do it this way to trigger the upload field doing its thing. 
			$form->saveInto($post);
			// get the file field back, grab the file, and set it as the attachement id of the post
			$field = $form->Fields()->dataFieldByName('Attachment');
			$post->AttachmentID = $field->getUpload()->getFile()->ID;
			
			if ($post->AttachmentID) {
				$post->write();
				$this->afterPostCreated($post);
				
				// @todo clean this up for NON js browsers
				return Convert::raw2json($post->toMap());
			}
		}
	}
	
	protected function savePostFromForm($data, $form) {
		$content = isset($data['Content']) ? $data['Content'] : '';
		$parentId = isset($data['ParentID']) ? $data['ParentID'] : 0;
		$target = isset($data['PostTarget']) ? $data['PostTarget'] : null;
		$title = isset($data['Title']) ? $data['Title'] : null;

		$to = array(
			'public'	=> isset($data['PublicUsers']) ? $data['PublicUsers'] : null,
			'logged_in'	=> isset($data['LoggedInUsers']) ? $data['LoggedInUsers'] : null,
			'members'	=> isset($data['Members']) ? $data['Members'] : null,
			'groups'	=> isset($data['Groups']) ? $data['Groups'] : null,
		);

		$post = $this->microBlogService->createPost($this->securityContext->getMember(), $content, array('Title' => $title), $parentId, $target, $to);

		$tags = $this->tagsFromRequest();
		$post->tag($tags);
		$this->afterPostCreated($post);
		
		return $post;
	}
	
	/**
	 * Get tags submitted in this request
	 * 
	 * @return array
	 */
	protected function tagsFromRequest() {
		$tags = $this->owner->getRequest()->getVar('tags') ? $this->owner->getRequest()->getVar('tags') : '';

		if (strlen($tags)) {
			$tags = explode(',', $tags);
		} else {
			$tags = array();
		}
		
		return $tags;
	}
	
	/**
	 * What tags should be used to filter the list of posts that are going to be displayed?
	 * 
	 * @return array
	 */
	public function getFilterTags() {
		return $this->tagsFromRequest();
	}
	
	/**
	 * What was the target of this post? 
	 * 
	 * @return string
	 */
	public function getTargetFilter() {
		$target = $this->getRequest()->getVar('target');
		if ($target) {
			// verify it's a class/ID
			list($targetType, $targetId) = explode(',', $target);
			$targetId = (int) $targetId;
			if (class_exists($targetType) && $targetId > 0) {
				return "$targetType,$targetId";
			}
		}
	}
	
	/**
	 * Retrieve a flat list of posts, regardless of specific hierarchy
	 */
	public function flatlist() {
		$replies = (bool) $this->owner->getRequest()->getVar('replies');
		
		$since = $this->owner->getRequest()->getVar('since');
		$before = (int) $this->owner->getRequest()->getVar('before');
		
		if (!$before) {
			$before = false;
		}
		
		$filter = array();
		
		$tags = $this->getFilterTags();
		
		$target = $this->getTargetFilter();
		if ($target) {
			$filter['Target'] = $target;
		}
		
		$sort = $this->request->getVar('sort');
		if (!$sort) {
			$sort = 'ID';
		}
		
		$offset = $this->request->getVar('offset');
		
		$noReplies = $this->Options()->Replies;
		if (!$noReplies) {
			$replies = false;
		}

		$timeline = $this->owner->microBlogService->getStatusUpdates($filter, $sort, $since, $before, !$replies, $tags, $offset);

		$props = array(
			'Posts' => $timeline, 
			'Options' => $this->Options(),
			'QueryOffset'	=> $timeline->QueryOffset,
			'SortBy'		=> $sort,
			'Target'		=> $target,
			'ForceContent'		=> true
		);
		return trim($this->owner->customise($props)->renderWith('Timeline'));
	}
	
	/**
	 * Return the rendered HTML that represents all the posts that the current user has access to view
	 * 
	 * @return string
	 */
	public function Timeline() {
		$since = $this->owner->getRequest()->getVar('since');
		$offset = (int) $this->owner->getRequest()->getVar('before');
		if (!$offset) {
			$offset = false;
		}
		$filter = array();
		$tags = $this->getFilterTags();
		$target = $this->getTargetFilter();
		if ($target) {
			$filter['Target'] = $target;
		}
		
		$sort = $this->request->getVar('sort');
		if (!$sort) {
			$sort = 'ID';
		}

		$data = $this->owner->microBlogService->getStatusUpdates($filter, $sort, $since, $offset, $toplevel = true, $tags);
		
		$props = array(
			'Posts' => $data, 
			'Options' => $this->Options(),
			'QueryOffset'	=> $data->QueryOffset,
			'Target'		=> $target,
			'SortBy'		=> $sort
		);

		return trim($this->owner->customise($props)->renderWith('Timeline'));
	}
	
	public function UserTimeline() {
		$replies = (bool) $this->request->getVar('replies');
		
		$since = $this->request->getVar('since');
		$offset = (int) $this->request->getVar('before');
		if (!$offset) {
			$offset = false;
		}
		
		$tags = $this->getFilterTags();

		$sort = $this->request->getVar('sort');
		if (!$sort) {
			$sort = 'ID';
		}

		$timeline = $this->microBlogService->getTimeline($this->securityContext->getMember(), $sort, $since, $offset, !$replies, $tags);
		
		$props = array(
			'Posts'			=> $timeline, 
			'Options'		=> $this->Options(),
			'QueryOffset'	=> $timeline->QueryOffset,
			'SortBy'		=> $sort
		);
		
		return trim($this->customise($props)->renderWith('Timeline'));
	}

	public function OwnerFeed() {
		$since = $this->request->getVar('since');
		$offset = (int) $this->request->getVar('before');

		$owner = $this->contextUser;
		$data = ArrayList::create();
		if (!$owner || !$owner->exists()) {
			$data = $this->microBlogService->globalFeed();
		} else {
			$replies = (bool) $this->request->getVar('replies');
			$data = $this->microBlogService->getStatusUpdates($owner, null, $since, $offset, !$replies);
		}

		$props = array(
			'Posts'			=> $data, 
			'Options'		=> $this->Options(),
			'QueryOffset'	=> $data->QueryOffset,
			'SortBy'		=> 'ID',
		);

		return trim($this->customise($props)->renderWith('Timeline'));
	}

	/**
	 * Returns the object that indicates who 'owns' the feed being viewed
	 * @return Member 
	 */
	public function ContextUser() {
		return $this->contextUser;
	}
	
	public function Link($action = '') {
		if ($this->parentController) {
			$link = $this->parentController->Link(self::URL_SEGMENT);
		} else {
			$link = 'timeline';
		}
		
		$params = array();
		
		$tags = $this->getRequest()->getVar('tags');
		if (strlen($tags)) {
			$params['tags'] = $tags;
		}
		
		$popup = $this->getRequest()->getVar('popup');
		if ($popup) {
			$params['popup'] = 1;
		}
		
		$target = $this->getTargetFilter();
		if ($target) {
			$params['target'] = $target;
		}
		
		$tagstr = count($params) ? '?' . http_build_query($params) : '';
		return Controller::join_links($link, $action, $tagstr);
	}
	
	
	/**
	 * View a particular user's feed
	 */
	public function user() {
		$user = (int) $this->request->param('ID');
		
		$since = $this->owner->getRequest()->getVar('since');
		$offset = (int) $this->owner->getRequest()->getVar('before');
		if (!$offset) {
			$offset = false;
		}
		$filter = array('OwnerID' => $user);
		$tags = $this->getFilterTags();
		$target = $this->getTargetFilter();
		if ($target) {
			$filter['Target'] = $target;
		}
		
		$sort = $this->request->getVar('sort');
		if (!$sort) {
			$sort = 'ID';
		}
		
		
		$data = $this->owner->microBlogService->getStatusUpdates($filter, $sort, $since, $offset, $toplevel = true, $tags);
		
		$props = array(
			'Posts' => $data, 
			'Options' => $this->Options(),
			'QueryOffset'	=> $data->QueryOffset,
			'Target'		=> $target,
			'SortBy'		=> $sort
		);
		
		$timeline = trim($this->customise($props)->renderWith('Timeline'));

		if (Director::is_ajax()) {
			return $timeline;
		}

		$data = array(
			'Timeline'		=> '<strong>WARNING: USER VIEWS ARE IN ALPHA </strong>' . $timeline,
			'OwnerFeed'		=> $timeline,
		);

		return $this->customise($data)->renderWith(array('FullTimeline', 'Page'));
	}
	
	public function UserFeed() {
		if (!$this->securityContext->getMember()) {
			// return;
		}
		$id = $this->ViewingUserID();
		if ($id) {
			$user = DataObject::get_by_id('Member', $id);
			if ($user && $user->exists()) {
				$data = $this->microBlogService->getStatusUpdates($user);
			}
		} else if ($this->securityContext->getMember()) {
			$data = $this->microBlogService->getTimeline($this->securityContext->getMember());
		}
		return $data;
	}

	public function ViewingUserID() {
		$id = (int) $this->request->param('ID');
		return $id;
	}
}
