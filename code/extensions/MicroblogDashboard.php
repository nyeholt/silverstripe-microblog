<?php

/**
 * Makes dashboard permissions based on the owning Member
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class MicroblogDashboard extends DataExtension {
	public function permissionSource() {
		return $this->owner->Owner();
	}
}
