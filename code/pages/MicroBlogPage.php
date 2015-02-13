<?php

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class MicroBlogPage extends Page {
	private static $db = array(
		'ShowTaggedWith'		=> 'MultiValueField',
		'AddTags'				=> 'MultiValueField',
		'SelfTagPosts'			=> 'Boolean',
		'CustomOptions'			=> 'MultiValueField',
	);

	public function selfTag() {
		return 'SELF_TAG_' . $this->ID;
	}
	
	public function canAddChildren($member = null) {
		return Member::currentUserID() > 0;
	}
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		
		$fields->addFieldToTab('Root.MicroBlog', MultiValueTextField::create('ShowTaggedWith', _t('MicroBlog.SHOW_POSTS_TAGGED', 'Show posts with these tags')));
		$fields->addFieldToTab('Root.MicroBlog', MultiValueTextField::create('AddTags', _t('MicroBlog.ADD_TAGS', 'Add the following tags to posts')));
		
		$fields->addFieldToTab('Root.MicroBlog', $cb = CheckboxField::create('SelfTagPosts', _t('MicroBlog.SELF_TAG', 'Tag posts against this page')));
		
		$cb->setDescription('Self-tagging will mean posts added via this page will not appear on other microblog pages, but may still appear in global timelines');
		
		$defaults = Config::inst()->get('TimelineController', 'options');
		
		$options = array_combine(array_keys($defaults), array_keys($defaults));
		$values = array('1'	=> 'Enabled', '0' => 'Disabled');

		$custom = $this->CustomOptions->getValues();
		if (!$custom || !count($custom)) {
			$this->CustomOptions = $defaults;
		}

		
		$fields->addFieldToTab('Root.MicroBlog', KeyValueField::create('CustomOptions', _t('MicroBlog.CUSTOM_OPTIONS', 'Options'), $options, $values));
		
		return $fields;
	}
}

class MicroBlogPage_Controller extends TimelineController {
	public $microBlogService;
	public $securityContext;

	static $dependencies = array(
		'microBlogService'		=> '%$MicroBlogService',
		'securityContext'		=> '%$SecurityContext',
	);
	
	private static $allowed_actions = array(
		'PostForm',
		'UploadForm'
	);
	
	public function __construct($dataRecord = null) {
		parent::__construct($dataRecord);
	}
	
	private $custOptions; 
	public function Options() {
		if ($this->custOptions) {
			return $this->custOptions;
		}
		
		$custom = $this->CustomOptions->getValues();
		
		if (count($custom)) {
			$this->custOptions = ArrayData::create($custom);
			return $this->custOptions;
		}
		
		return parent::Options();
	}
	
	public function PostForm() {
		$form = parent::PostForm();
		$form->Fields()->push(HiddenField::create('PostTarget', '', get_class($this->data()) . ',' . $this->data()->ID));
		return $form;
	}

	public function UploadForm() {
		$form = parent::UploadForm();
		$form->Fields()->push(HiddenField::create('PostTarget', '', get_class($this->data()) . ',' . $this->data()->ID));
		return $form;
	}

	public function getFilterTags() {
		$tags = parent::tagsFromRequest();

		$setTags = $this->ShowTaggedWith->getValues();
		if ($setTags && count($setTags)) {
			$tags = array_merge($tags, $setTags);
		}
		
		if ($this->data()->SelfTagPosts) {
			$tags[] = $this->data()->selfTag();
		}
		
		return $tags;
	}

	/**
	 * What tags are present in the request (ie that should filter and be applied to posts
	 */
	protected function afterPostCreated(MicroPost $post) {
		$tags = array();
		
		if ($this->data()->SelfTagPosts) {
			$tags[] = $this->data()->selfTag();
		}
		
		$add = $this->AddTags->getValues();
		if (count($add)) {
			$tags = array_merge($tags, $add);
		}

		$post->tag($tags);
	}


	/**
	 * Don't return the TimelineController link, use the standard content controller link, so as to ensure
	 * subsequent requests come back to _this_ page, not /timeline
	 *
	 * @param string|null $action Action to link to.
	 * @return string
	 */
	public function Link($action = null) {
		$link = $this->data()->Link(($action ? $action : true));
		
		$params = array();
		
		$tags = $this->getRequest()->getVar('tags');
		if (strlen($tags)) {
			$params['tags'] = $tags;
		}

		$popup = $this->getRequest()->getVar('popup');
		if ($popup) {
			$params['popup'] = 1;
		}
		
		$tagstr = count($params) ? '?' . http_build_query($params) : '';
		return Controller::join_links($link, $tagstr);
	}
}