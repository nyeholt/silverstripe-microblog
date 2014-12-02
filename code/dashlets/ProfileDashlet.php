<?php

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class ProfileDashlet extends Dashlet {
	public static $title = 'Profile';
}

class ProfileDashlet_Controller extends Dashlet_Controller {
	
	
	public function SettingsForm() {
		$fields = new FieldList();

		// TODO Translate the permission
		$opts = array_combine(MicroBlogMember::$permission_options, MicroBlogMember::$permission_options);
		$fields->push(new DropdownField('PostPermission', _t('ProfileDashlet.POST_PERM', 'Post permissions'), $opts));
		
		$actions = new FieldList(new FormAction('savesettings', _t('ProfileDashlet.SAVE', 'Update')));
		
		$form = new Form($this, 'SettingsForm', $fields, $actions);
		$form->addExtraClass('ajaxsubmitted');
		
		$form->loadDataFrom(Member::currentUser());
		return $form;
	}

	public function savesettings($data, Form $form) {
		$fields = array(
			'PostPermission'
		);
		$form->saveInto(Member::currentUser(), $fields);
		Member::currentUser()->write();
		Member::currentUser()->updatePostPermissions();
		return $this->SettingsForm()->forAjaxTemplate();
	}
}