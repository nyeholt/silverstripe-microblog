<?php

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class MicroBlogService {
	
	/**
	 * @var DataService 
	 */
	public $dataService;
	
	/**
	 * @var SecurityContext
	 */
	public $securityContext;
	
	/**
	 * @var TransactionManager
	 */
	public $transactionManager;
	
	/**
	 * @var QueuedJobService
	 */
	public $queuedJobService;
	
	/**
	 * @var PermissionService
	 */
	public $permissionService;
	
	/**
	 *
	 * @var NotificationService
	 */
	public $notificationService;
	
	/**
	 * Do we allow anonymous posting?
	 *
	 * @var boolean
	 */
	public $allowAnonymousPosts = false;
	
	public $postProcess = false;
	
	/**
	 * The items that we can sort things by
	 *
	 * @var array
	 */
	public $canSort = array('WilsonRating', 'ID', 'Created', 'Up', 'Down', 'ActiveRating', 'PositiveRating');
	
	/**
	 * A request length list of actions that users have taken
	 *
	 * @var array
	 */
	protected $userActions = array();
	
	private static $dependencies = array(
		'dataService'			=> '%$DataService',
		'permissionService'		=> '%$PermissionService',
		'queuedJobService'		=> '%$QueuedJobService',
		'securityContext'		=> '%$SecurityContext',
		'transactionManager'	=> '%$TransactionManager',
	);
	
	public function __construct() {

	}
	
	public function webEnabledMethods() {
		return array(
			'globalFeed'		=> 'GET',
			'unreadPosts'		=> 'GET',
			'createPost'		=> 'POST',
			'deletePost'		=> 'POST',
			'vote'				=> 'POST',
			'getStatusUpdates'	=> 'GET',
			'getTimeline'		=> 'GET',
			'addFriendship'		=> 'POST',
			'removeFriendship'	=> 'POST',
			'rawPost'			=> 'GET',
			'savePost'			=> 'POST',
			'findMember'		=> 'GET',
		);
	}
	
	public function getUserActions() {
		return $this->userActions;
	}
	
	public function unreadPosts($tags = null) {
		$member = $this->securityContext->getMember();
		if (!$member || !$member->ID) {
			return array();
		}
		return $member->getUnreadPosts($tags);
	}

	/**
	 * Creates a new post for the given member
	 *
	 * @param Member $member
	 *			The member creating the post. Will default to the calling member if not specified
	 * @param string $content
	 *			The content being loaded into the post
	 * @param int $parentId
	 *			The ID of a micropost that is considered the 'parent' of this post
	 * @param mixed $target
	 *			The "target" of this post; may be a data object (ie context of the post) or a user/group
	 * @param array $to
	 *			The people/groups this post is being sent to. This is an array of
	 *			- logged_in: boolean (logged in users; uses a system config setting to determine which group represents 'logged in'
	 *			- members: an array, or comma separated string, of member IDs
	 *			- groups: an array, or comma separated string, of group IDs
	 * @return MicroPost 
	 */
	public function createPost(DataObject $member, $content, $title = null, $parentId = 0, $target = null, $to = null) {
		if (!$member) {
			$member = $this->securityContext->getMember();
		}
		
		if (!$member->exists() && !$this->allowAnonymousPosts) {
			throw new Exception("Anonymous posting disallowed");
		}

		$post = MicroPost::create();
		$post->Content = $content;
		$post->Title = $title;
		$post->OwnerID = $member->ID;
		$post->Target = $target;

		if ($parentId) {
			$parent = MicroPost::get()->restrictedByID($parentId);
			if ($parent) {
				$post->ParentID = $parentId;
				$post->ThreadID = $parent->ThreadID;
				$post->Target = $parent->Target;
			}
		}

		if (isset($to['public'])) {
			$post->PublicAccess = (bool) $to['public'];
		}

		$post->write();
		
		// if we're a good poster, scan its content, otherwise post process it for spam
		if ($member->Balance >= MicroBlogMember::BALANCE_THRESHOLD) {
			$post->analyseContent();
			$post->write();
		} else {
			$this->queuedJobService->queueJob(new ProcessPostJob($post));
		}
		
		// set its thread ID
		if (!$post->ParentID) {
			$post->ThreadID = $post->ID;
			$post->write();
		}
		
		if ($post->ID != $post->ThreadID) {
			$thread = MicroPost::get()->restrictedByID($post->ThreadID); 
			if ($thread) {
				$owner = $thread->Owner();
				$this->transactionManager->run(function () use ($post, $thread) {
					$thread->NumReplies += 1;
					$thread->write();
				}, $owner);
			}
		}

		$this->rewardMember($member, 2);
		
		if ($to) {
			$grantTo = array();
			if (isset($to['logged_in']) && $to['logged_in']) {
				// find the 'logged in' group, and grant to that.
				$groups = null;
				if (class_exists('Multisites')) {
					$groups = Multisites::inst()->getCurrentSite()->LoggedInGroups()->toArray();
				} else {
					$groups = SiteConfig::current_site_config()->LoggedInGroups()->toArray();
				}
				if ($groups) {
					$grantTo = array_merge($grantTo, $groups);
				}
			}
			// todo evaluate security implication of posting to arbitrary members...
			// do we need to check 'friends' status here?
			if (isset($to['members']) && count($to['members'])) {
				if (!is_array($to['members'])) {
					$to['members'] = explode(',', $to['members']);
				}
				foreach ($to['members'] as $memberId) {
					$id = (int) $memberId;
					$toMember = Member::get()->byID($id);
					if ($toMember) {
						$grantTo[] = $toMember;
					}
				}
			}
			
			if (isset($to['groups']) && count($to['groups'])) {
				if (!is_array($to['groups'])) {
					$to['groups'] = explode(',', $to['groups']);
				}
				foreach ($to['groups'] as $groupId) {
					$groupId = (int) $groupId;
					$group = Group::get()->byID($groupId);
					if ($group) {
						$grantTo[] = $group;
					}
				}
			}
			
			if (count($grantTo)) {
				foreach ($grantTo as $grantee) {
					$this->permissionService->grant($post, 'View', $grantee);
				}
			}
		}

		// we stick this in here so the UI can update...
		$post->RemainingVotes = $member->VotesToGive;

		$post->extend('onCreated', $member, $target);
		if ($this->notificationService) {
			$this->notificationService->notify('MICRO_POST_CREATED', $post);
		}

		return $post;
	}
	
	/**
	 * Gets the raw post if allowed
	 * 
	 * @param int $id 
	 */
	public function rawPost($id) {
		$item = $this->dataService->byId('MicroPost', $id);
		if ($item->checkPerm('Write')) {
			return $item;
		}
	}
	
	/**
	 * Save the post
	 * 
	 * @param DataObject $post
	 * @param type $data 
	 */
	public function savePost(DataObject $post, $data) {
		if ($post->checkPerm('Write') && isset($data['Content'])) {
			$post->Content = $data['Content'];
			if ($this->securityContext->getMember()->Balance >= MicroBlogMember::BALANCE_THRESHOLD) {
				$post->analyseContent();
				$post->write();
			} else {
				$this->queuedJobService->queueJob(new ProcessPostJob($post));
			}
			$html = $post->renderWith('PostContent');
			return $post;
		}
	}

	/**
	 * Extracts tags from an object's content where the tag is preceded by a #
	 * 
	 * @param MicroPost $object 
	 * 
	 */
	public function extractTags(DataObject $object, $field = 'Content') {
		if (!$object->hasExtension('TaggableExtension')) {
			return array();
		}
		$content = $object->$field;

		if (preg_match_all('/#([a-z0-9_-]+)/is', $content, $matches)) {
			$object->tag($matches[1], true);
		}
		
		return $object->Tags();
	}

	/**
	 * Reward a member with a number of votes to be given
	 * @param type $member
	 * @param type $votes 
	 */
	public function rewardMember($member, $votes) {
		$member->VotesToGive += $votes;
		$this->transactionManager->run(function () use ($member) {
			$member->write();
		}, $member);
	}
	
	/**
	 * Get all posts that the current user has access to
	 *
	 * @param type $number 
	 */
	public function globalFeed($filter = array(), $orderBy = 'ID DESC', $since = null, $number = 10, $markViewed = true) {
		$number = (int) $number;
		
		if (!count($filter)) {
			$filter = array('ParentID' => 0);
		}
		$filter['Deleted'] = 0;

		$items = MicroPost::get()->filter($filter)->sort($orderBy);
		
		if ($since) {
			$since = (int) $since;
			$items = $items->filter('ID:GreaterThan', $since);
		}

		if ($markViewed) {
			$this->recordUserAction();
		}
		
		return $items->limit($number)->restrict();
	}
	
	/**
	 * Gets all the status updates for a particular user before a given time
	 * 
	 * @param array $filter
	 *			The specific member to get status updates from
	 * @param type $sortBy
	 *			The order in which the items should be sorted
	 * @param type $since
	 *			The ID after which to retrieve 
	 * @param boolean $before
	 *			The ID before which to retrieve
	 * @param boolean $topLevelOnly
	 *			Whether to retrieve top-level posts only
	 * @param array $tags
	 *			A set of tags to filter posts by
	 * @param int $offset
	 *			Offset to start returning results by
	 * @param int $number
	 *			How many results to return
	 *			
	 */
	public function getStatusUpdates($filter = array(), $sortBy = 'ID', $since = 0, $before = false, $topLevelOnly = true, $tags = array(), $offset = 0, $number = 10) {
		if ($filter instanceof Member) {
			$userIds[] = $filter->ProfileID;
			$filter = array(
				'ThreadOwnerID'		=> $userIds, 
			);
		}
		if (!$filter) {
			$filter = array();
		}
		return $this->microPostList($filter, $sortBy, $since, $before, $topLevelOnly, $tags, $offset, $number);
	}

	/**
	 * Gets all the updates for a given user's list of followers for a given time
	 * period
	 *
	 * @param type $member
	 * @param type $beforeTime
	 * @param type $number 
	 */
	public function getTimeline(DataObject $member, $sortBy = 'ID',  $since = 0, $before = false, $topLevelOnly = true, $tags = array(), $offset = 0, $number = 10) {
		$following = $this->friendsList($member);

		// TODO Following points to a list of Profile IDs, NOT user ids.
		$number = (int) $number;
		$userIds = array();
		if ($following) {
			$userIds = $following->map('OtherID', 'OtherID');
			$userIds = $userIds->toArray();
		}

		$userIds[] = $member->ID;
		
		$filter = array(
			'OwnerID' => $userIds, 
		);
		
		return $this->microPostList($filter, $sortBy, $since, $before, $topLevelOnly, $tags, $offset, $number);
	}
	
	/**
	 * Get the list of replies to a particular post
	 * 
	 * @param DataObject $to
	 * @param type $since
	 * @param type $beforePost
	 * @param type $topLevelOnly
	 * @param type $number 
	 * 
	 * @return DataList
	 */
	public function getRepliesTo(DataObject $to, $sortBy = 'ID', $since = 0, $before = false, $topLevelOnly = false, $tags = array(), $offset = 0, $number = 100) {
		$filter = array(
			'ParentID'			=> $to->ID, 
		);
		return $this->microPostList($filter, $sortBy, $since, $before, $topLevelOnly, $tags, $offset, $number);
	}
	
	/**
	 * Create a list of posts depending on a filter and time range
	 * 
	 * @param array $filter
	 *			
	 * @param int $since
	 *				The ID after which to get posts 
	 * @param int $before
	 *				The ID or pagination offset from which to get posts before. 
	 * @param type $topLevelOnly
	 *              Only retrieve the top level of posts. 
	 * @param array $tags
	 *			A set of tags to filter posts by
	 * @param int $offset
	 *			Offset to start returning results by
	 * @param int $number
	 *			How many results to return
	 * 
	 * @return DataList 
	 */
	public function microPostList($filter, $sortBy = 'ID', $since = 0, $before = false, $topLevelOnly = true, $tags = array(), $offset = 0, $number = 10) {
		if ($topLevelOnly) {
			$filter['ParentID'] = '0';
		}

		$filter['Deleted'] = 0;
		
		if ($since) {
			$since = Convert::raw2sql($since); 
			$filter['ID:GreaterThan'] = $since;
		}
		
		if ($before !== false) {
			$before = (int) $before;
			$filter['ID:LessThan'] = $before;
		} 

		$sort = array();

		if (is_string($sortBy)) {
			if (in_array($sortBy, $this->canSort)) {
				$sort[$sortBy] = 'DESC';
			} 

			// final sort as a tie breaker
			$sort['ID'] = 'DESC';
		} else if (is_array($sortBy)) {
			// $sort = $sortBy;
			foreach ($sortBy as $sortKey => $sortDir) {
				if (in_array($sortKey, $this->canSort)) {
					$sort[$sortKey] = $sortDir;
				} 
			}
		} else {
			$sort = array('ID' => 'DESC');
		}

		$offset = (int) $offset;
		$limit = $number ? $offset . ', ' . (int) $number : '';

		if (count($tags)) {
			$filter['Tags.Title'] = $tags;
		}

		$this->recordUserAction();
		return MicroPost::get()->filter($filter)->sort($sort)->limit($limit)->restrict();
	}
	
	
	protected function recordUserAction($member = null) {
		if (!$member) {
			$member = $this->securityContext->getMember();
		}
		
		if ($member && $member->ID) {
			$this->userActions[$member->ID] = $member->ID;
		}
	}
	
	/**
	 * Search for a member or two
	 * 
	 * @param string $searchTerm 
	 * @return DataList
	 */
	public function findMember($searchTerm) {
		$term = Convert::raw2sql($searchTerm);
		$current = (int) $this->securityContext->getMember()->ID;
		$filter = '("Username" LIKE \'' . $term .'%\' OR "FirstName" LIKE \'' . $term .'%\' OR "Surname" LIKE \'' . $term . '%\') AND "ID" <> ' . $current;

		$items = DataList::create('Member')->where($filter)->restrict();
		
		return $items;
	}

	/**
	 * Create a friendship relationship object
	 * 
	 * @param DataObject $member
	 *				"me", as in the person who triggered the follow
	 * @param DataObject $followed
	 *				"them", the person "me" is wanting to add 
	 * @return \Friendship
	 * @throws PermissionDeniedException 
	 */
	public function addFriendship(DataObject $member, DataObject $followed) {
		if (!$member || !$followed) {
			throw new PermissionDeniedException('Read', 'Cannot read those users');
		}

		if ($member->ID != $this->securityContext->getMember()->ID) {
			throw new PermissionDeniedException('Write', 'Cannot create a friendship for that user');
		}

		$existing = Friendship::get()->filter(array(
			'InitiatorID'		=> $member->ID,
			'OtherID'			=> $followed->ID,
		))->first();

		if ($existing) {
			return $existing;
		}
		
		// otherwise, we have a new one!
		$friendship = new Friendship;
		$friendship->InitiatorID = $member->ID;
		$friendship->OtherID = $followed->ID;
		
		// we add the initiator into the 
		
		// lets see if we have the reciprocal; if so, we can mark these as verified 
		$reciprocal = $friendship->reciprocal();

		// so we definitely add the 'member' to the 'followers' group of $followed
		$followers = $followed->getGroupFor(MicroBlogMember::FOLLOWERS);
		$followers->Members()->add($member);

		if ($reciprocal) {
			$reciprocal->Status = 'Approved';
			$reciprocal->write();
			
			$friendship->Status = 'Approved';
			
			// add to each other's friends groups
			$friends = $followed->getGroupFor(MicroBlogMember::FRIENDS);
			$friends->Members()->add($member);
			
			
			$friends = $member->getGroupFor(MicroBlogMember::FRIENDS);
			$friends->Members()->add($followed);
		}

		$friendship->write();
		return $friendship;
	}
	
	/**
	 * Remove a friendship object
	 * @param DataObject $relationship 
	 */
	public function removeFriendship(DataObject $relationship) {
		if ($relationship && $relationship->canDelete()) {
			
			// need to remove this user from the 'other's followers group and friends group
			// if needbe
			if ($relationship->Status == 'Approved') {
				$reciprocal = $relationship->reciprocal();
				if ($reciprocal) {
					// set it back to pending
					$reciprocal->Status = 'Pending';
					$reciprocal->write();
				}
				
				$friends = $relationship->Other()->getGroupFor(MicroBlogMember::FRIENDS);
				$relationship->Initiator()->Groups()->remove($friends);
				
				$friends = $relationship->Initiator()->getGroupFor(MicroBlogMember::FRIENDS);
				$relationship->Other()->Groups()->remove($friends);
			}
			
			$followers = $relationship->Other()->getGroupFor(MicroBlogMember::FOLLOWERS);
			$relationship->Initiator()->Groups()->remove($followers);
			
			$relationship->delete();
			return $relationship;
		}
	}
	
	/** 
	 * Get a list of friends for a particular member
	 * 
	 * @param DataObject $member
	 * @return DataList
	 */
	public function friendsList(DataObject $member) {
		if (!$member) {
			return;
		}
		$list = DataList::create('Friendship')->filter(array('InitiatorID' => $member->ID));
		return $list;
	}
	
	/**
	 * Delete a post
	 * 
	 * @param DataObject $post 
	 */
	public function deletePost(DataObject $post) {
		if (!$post) {
			return;
		}
		if ($post->checkPerm('Delete')) {
			$post->delete();
		}

		return $post;
	}
	
	/**
	 * Vote for a particular post
	 * 
	 * @param DataObject $post 
	 */
	public function vote(DataObject $post, $dir = 1) {
		$member = $this->securityContext->getMember();
		
		if ($member->VotesToGive <= 0) {
			$post->RemainingVotes = 0;
			return $post;
		}

		// we allow multiple votes - as many as the user has to give!
		$currentVote = null; // MicroPostVote::get()->filter(array('UserID' => $member->ID, 'PostID' => $post->ID))->first();
		
		if (!$currentVote) {
			$currentVote = MicroPostVote::create();
			$currentVote->UserID = $member->ID;
			$currentVote->PostID = $post->ID;
		}
		
		$currentVote->Direction = $dir > 0 ? 1 : -1;
		$currentVote->write();
		
		$list = DataList::create('MicroPostVote');
		
		$upList = $list->filter(array('PostID' => $post->ID, 'Direction' => 1));
		$post->Up = $upList->count();
		
		$downList = $list->filter(array('PostID' => $post->ID, 'Direction' => -1));
		$post->Down = $downList->count();
		
		$owner = $post->Owner();
		if (!$post->OwnerID || !$owner || !$owner->exists()) {
			$owner = Security::findAnAdministrator();
		}
		
		// write the post as the owner, and calculate some changes for the author
		$this->transactionManager->run(function () use ($post, $currentVote, $member) {
			$author = $post->Owner();
			if ($author && $author->exists() && $author->ID != $member->ID) {
				if ($currentVote->Direction > 0) {
					$author->Up += 1;
				} else {
					$author->Down += 1;
				}
				$author->write();
			}
			$post->write();
		}, $owner);

		$this->rewardMember($member, -1);
		$post->RemainingVotes = $member->VotesToGive;

		return $post;
	}
}

class MicroblogPermissions implements PermissionDefiner {
	public function definePermissions() {
		return array(
			'ViewPosts',
			'ViewProfile',
		);
	}
}