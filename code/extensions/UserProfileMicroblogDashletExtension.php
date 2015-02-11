<?php

/**
 * 
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class UserProfileMicroblogDashletExtension extends Extension {
	public function updateProfileDashletForm($form) {
		$opts = array_combine(MicroBlogMember::$permission_options, MicroBlogMember::$permission_options);
		$form->Fields()->push(DropdownField::create('PostPermission', _t('ProfileDashlet.POST_PERM', 'Post permissions'), $opts));
	}
}
