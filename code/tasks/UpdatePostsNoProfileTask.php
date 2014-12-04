<?php

/**
 * 
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class UpdatePostsNoProfileTask extends BuildTask {

	public function run($request) {
		$posts = MicroPost::get()->filter('ParentID', 0);
		
		foreach ($posts as $post) {
			$sql = 'UPDATE "MicroPost" SET "ThreadOwnerID" = ' . $post->OwnerID . ' WHERE "ThreadID" = ' . $post->ID;
			DB::query($sql);
		}
	}
}
