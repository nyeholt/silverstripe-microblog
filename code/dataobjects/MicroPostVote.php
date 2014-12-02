<?php

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class MicroPostVote extends DataObject {
	private static $db = array(
		'Direction'		=> 'Int',
	);
	
	private static $has_one = array(
		'User'		=> 'Member',
		'Post'		=> 'MicroPost',
	);
}
