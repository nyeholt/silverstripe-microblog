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
	
	/**
	 * Only notify about a particular page being published if it hasn't been published in the last
	 * X number of days
	 *
	 * @var int
	 */
	public $notifyOncePer = 86400;
	
	public function onAfterPublish(&$original) {
		$lastPub = strtotime($original->LastEdited);
		
		if (time() - $lastPub < $this->notifyOncePer) {
			return;
		}
		
		$text = <<<POST
**%s %s** updated the page **[%s](%s)** at %s
POST;
		$member = Member::currentUser();
		if ($member && $member->ID) {
			$tgt = $this->owner->ClassName . ',' . $this->owner->ID;
			$content = sprintf(_t('MicroBlog.PAGE_PUBlISH_NOTICE', $text), $member->FirstName, $member->Surname, $this->owner->Title, $this->owner->Link(), date('H:i M jS'));
			$this->microBlogService->createPost($member, $content, array('PostType' => 'notice-post'), 0, $tgt);
		}
	}
}
