<?php

/**
 * Creates a notice post after a page is published. 
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class PublishedPageNotice extends SiteTreeExtension {
	/**
	 * @var MicroBlogService
	 */
	public $microBlogService;
	
	public function onAfterPublish(&$original) {
		$text = <<<POST
**%s %s** updated the page **[%s](%s)** at %s
POST;
		$member = Member::currentUser();
		if ($member && $member->ID) {
			$content = sprintf(_t('MicroBlog.PAGE_PUBlISH_NOTICE', $text), $member->FirstName, $member->Surname, $this->owner->Title, $this->owner->Link(), date('Y-m-d H:i:s'));
			$this->microBlogService->createPost($member, $content, array('PostType' => 'notice-post'), 0, null, array('logged_in' => true));
		}
	}
}
