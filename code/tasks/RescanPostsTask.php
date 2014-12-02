<?php

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class RescanPostsTask extends BuildTask {
	public function run($request) {
		$id = (int) $request->getVar('id');
		
		if ($id) {
			$post = DataList::create('MicroPost')->byID($id);
			$post->analyseContent();
			$post->write();
			$job = new ProcessPostJob($post);
			$job->process();
		} else if ($request->getVar('all')) {
			$posts = DataList::create('MicroPost');
			foreach ($posts as $post) {
				if ($post->Content != '[spam]') {
					$post->analyseContent();
					$post->write();
					singleton('QueuedJobService')->queueJob(new ProcessPostJob($post));
					
				}
			}
		}
	}
}
