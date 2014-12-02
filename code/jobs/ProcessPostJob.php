<?php

/**
 * Performs post processing of a post to do things like oembed lookup etc
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class ProcessPostJob extends AbstractQueuedJob {
	
	// what number of downvotes does spam attract
	const SPAM_DOWN = 10;
	
	// what to use as 'spam' content
	const SPAM_CONTENT = '[spam]';
	
	
	public static $api_key = '';
	
	private static $dependencies = array(
		'socialGraphService'	=> '%$SocialGraphService',
		'microBlogService'		=> '%$MicroBlogService',
	);
	
	/**
	 * @var MicroBlogService
	 */
	public $microBlogService;
	
	/**
	 * @var SocialGraphService
	 */
	public $socialGraphService;
	
	
	public function __construct($post = null) {
		if ($post) {
			$this->setObject($post);
			$this->totalSteps = 1;
		}
	}
	
	public function getTitle() {
		return 'Processing #' . $this->getObject()->ID;
	}
	
	public function getJobType() {
		return QueuedJob::IMMEDIATE;
	}
	
	public function process() {
		$post = $this->getObject();
		if ($post) {
			$author = $post->Owner();
			$balance = $author->Balance;

			if (self::$api_key && $post->Content != self::SPAM_CONTENT) {
				require_once Director::baseFolder() . '/microblog/thirdparty/defensio/Defensio.php';
				$defensio = new Defensio(self::$api_key);
				$document = array(
					'type' => 'comment', 
					'content' => $post->Content, 
					'platform' => 'silverstripe_microblog', 
					'client' => 'MicroBlog Defensio-PHP | 0.1 | Marcus Nyeholt | marcus@silverstripe.com.au', 
					'async' => 'false'
				);

				try {
					$result = $defensio->postDocument($document);

					if ($result && isset($result[1])) {
						if ($result[1]->allow == 'false') {
							$post->Content = self::SPAM_CONTENT;
							$post->Down += self::SPAM_DOWN;
							$post->write();
							$author->Down += self::SPAM_DOWN;
							$author->write();
						}
					}
				} catch (Exception $e) {
					SS_Log::log($e, SS_Log::WARN);
				}
			}
			
			if ($post->Content != self::SPAM_CONTENT) {
				$post->analyseContent();
				$post->write();
			}
		}

		$this->isComplete = true;
	}
}
