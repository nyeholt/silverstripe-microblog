<?php

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class MicroBlogPage extends Page {

}

class MicroBlogPage_Controller extends TimelineController {
	
	
	public $microBlogService;
	public $securityContext;

	static $dependencies = array(
		'microBlogService'		=> '%$MicroBlogService',
		'securityContext'		=> '%$SecurityContext',
	);
	
	public function __construct($dataRecord = null) {
		parent::__construct($dataRecord);
	}
}