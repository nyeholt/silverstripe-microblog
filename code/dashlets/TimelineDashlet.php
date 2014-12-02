<?php

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class TimelineDashlet extends Dashlet {
	public static $title = 'Timeline';
	
	public function canCreate($member = null) {
		if (!$member) {
			$member = Member::currentUser();
		}
		return $member->ID > 0;
	}
}

class TimelineDashlet_Controller extends Dashlet_Controller {
	
	private static $allowed_actions = array(
		'timeline',
		'ShowDashlet',
	);
	
	/**
	 * @var MicroBlogService
	 * 
	 */
	public $microBlogService;
	public $securityContext;

	static $dependencies = array(
		'microBlogService'		=> '%$MicroBlogService',
		'securityContext'		=> '%$SecurityContext',
	);
	
	public function __construct($widget = null, $parent = null) {
		parent::__construct($widget, $parent);
		if ($parent && $parent->getRequest()) {
			$this->request = $parent->getRequest();
		}
	}
	
	
	public function TimelineUrl() {
		return 'timeline';
	}

	public function ShowDashlet() {
		Requirements::javascript('microblog/javascript/timeline-dashlet.js');
		return '';
		// oh man this is so hacky, but I don't really quite know the best way to do what I want which is
		// one controller and about ten different ways to access it... all depending on context of course!
		$controller = $this->timeline();
		$controller->init();
		$rendered = $controller->renderWith('FullTimeline'); 
		// $controller->index(); // $controller->handleRequest($this->request, $this->model);
		return $rendered instanceof SS_HTTPResponse ? $rendered->getBody() : $rendered;
	}
}
