<?php

/**
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class MicroPostDigestJob extends AbstractQueuedJob {
	/**
	 * @var MicroBlogService
	 */
	public $microBlogService;

	/**
	 *
	 * @var TransactionManager
	 */
	public $transactionManager;
	
	public function __construct($since = 0, $type = null, $groupId = 0) {
		
		if (!$this->totalSteps && $groupId) {
			// means we need to build the list of users 
			$this->currentStep = 1;
			$this->totalSteps = 2;
			
			if ($type) {
				$this->type = $type;
			} else if (!$this->type) {
				$this->type = 'daily';
			}

			if (!$since) {
				$since = isset($_GET['since']) ? strtotime($_GET['since']) : 0;
			}

			if ($since) { 
				if (!is_numeric($since)) {
					$since = strtotime($since);
				}
				$this->since = $since;
			}

			$groupId = (int) (isset($_GET['groupId']) ? $_GET['groupId'] : $groupId);

			// get _all_ user IDs from within this parent group
			if ($groupId) {
				$this->groupId = $groupId;
				$parent = Group::get()->byID($groupId);
				if ($parent) {
					$allMembers = $parent->Members();
					$allMembers = $allMembers->filter('DigestType', $type);
					$members = $allMembers->map()->toArray();
					if (count($members)) {
						$this->members = array_keys($members);
						$this->totalSteps = count($members);
					}
				}
			}
		} else {
			
		}
	}
	
	public function getTitle() {
		return sprintf(_t('MicroBlog.DIGEST_JOB', '%s post digest email'), $this->type);
	}
	
	public function getSignature() {
		return md5('digest ' . $this->type);
	}
	
	public function process() {
		$fromTime = $this->since ? $this->since : 0;
		
		$members = $this->members;
		$nextId = array_shift($members);
		$this->members = $members;
		
		$member = Member::get()->byID($nextId);
		$microBlogService = $this->microBlogService;
		
		// if we don't have a 'since' time, we need to only scan from 'now' onwards, to prevent _every_ 
		// post from being collected
		if (!$fromTime) {
			$fromTime = time();
		}
		
		$since = date('Y-m-d 00:00:00', $fromTime);
		
		if ($member && $member->ID) {
			$this->transactionManager->run(function () use ($microBlogService, $since, $member) {
				$posts = $microBlogService->globalFeed(array(
					'Created:GreaterThan'	=> $since
				), $orderBy = 'ID DESC', $since = null, $number = 10, $markViewed = false);
				
				if (!count($posts)) {
					return;
				}
				
				$content = SSViewer::execute_template('DigestEmail', ArrayData::create(array(
					'Posts'		=> $posts,
					'Member'	=> $member
				)));
				
				$content = HTTP::absoluteURLs($content);

				$config = SiteConfig::current_site_config();

				$mail = new Email();
				$mail->setTo($member->Email);
				$mail->setBody($content);
				$mail->setSubject($config->Title . ' digest');

				$mail->send();
			}, $member);
		}

		$this->currentStep++;

		if (count($members) == 0) {
			$nextTime = $this->type == 'weekly' ? '+1 week' : '+1 day';
			$nextDate = date('Y-m-d 23:55:00', strtotime($nextTime));
			$nextJob = new MicroPostDigestJob(time(), $this->type, $this->groupId);
			singleton('QueuedJobService')->queueJob($nextJob, $nextDate);
			$this->isComplete = true;
		}
	}
}
