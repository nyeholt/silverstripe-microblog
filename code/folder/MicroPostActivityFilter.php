<?php

/**
 * 
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class MicroPostActivityFilter implements RequestFilter {
	
	/**
	 * @var MicroBlogService
	 */
	public $microBlogService;
	
	public function postRequest(\SS_HTTPRequest $request, \SS_HTTPResponse $response, \DataModel $model) {
		$actions = $this->microBlogService->getUserActions();
		
		if ($actions && count($actions)) {
			$members = Member::get()->filter('ID', array_keys($actions));

			foreach ($members as $member) {
				if ($member->exists()) {
					$member->LastPostView = SS_Datetime::now()->getValue();
					$member->write();
				}
			}
		}
	}

	public function preRequest(\SS_HTTPRequest $request, \Session $session, \DataModel $model) {
		
	}

}
