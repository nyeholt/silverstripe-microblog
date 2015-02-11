<?php

/**
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class MicroBlogSystemSettings extends Extension {
	private static $many_many = array(
		'LoggedInGroups'			=> 'Group',
	);
	
	public function updateCMSFields($fields) {
		$allGroups = Group::get()->filter('ParentID', 0)->map()->toArray();
		$groups = ListboxField::create('LoggedInGroups', 'Groups representing logged in users', $allGroups);
		$groups->setMultiple(true);
		$fields->addFieldToTab('Root.MicroBlogSettings', $groups);
	}
	
	public function updateSiteCMSFields($fields) {
		$this->updateCMSFields($fields);
	}
}
