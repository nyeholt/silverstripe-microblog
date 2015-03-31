<?php

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class TestMicroBlogService extends SapphireTest {
	
	protected static $fixture_file = 'microblog_test_data.yml';
	
	public function testTagExtract() {
		
		$this->logInWithPermission();
		$post = MicroPost::create();
		$post->Content = <<<POST
	This is #content
		
being created in this #post
		
POST;
		$post->write();
		
		$tags = singleton('MicroBlogService')->extractTags($post);
	}
	
	public function testUserFollowing() {
		
		Restrictable::set_enabled(false);
		
		$memberOne = Member::create();
		$memberOne->Email = 'one@one.com';
		$memberOne->Password = '1234';
		$memberOne->write();
		
		$memberTwo = Member::create();
		$memberTwo->Email = 'two@two.com';
		$memberTwo->Password = '1234';
		$memberTwo->write();

		$svc = singleton('MicroBlogService');
		/* @var $svc MicroBlogService */
		
		singleton('SecurityContext')->setMember($memberOne);
		$svc->addFriendship($memberOne, $memberTwo);
		
		// gah - ss3's testing setup needs to be better sorted to be able to do this bit...
	}
	
	public function testCreatePost() {
		Restrictable::set_enabled(true);
		$this->logInWithPermission();
		MicroPost::get()->removeAll();
		
		$group = $this->objFromFixture('Group', 'posters');
		$member = $this->objFromFixture('Member', 'user1');
		$user2 = $this->objFromFixture('Member', 'user2');
		
		$groups = $member->Groups()->toArray();
		
		$svc = singleton('MicroBlogService');
		/* @var $svc MicroBlogService */
		
		$post = $svc->createPost($member, "My post content"); // , null, 0, null, array('groups' => $group->ID));
		
		$this->assertTrue($post->checkPerm('View', $member));
		$this->assertFalse($post->checkPerm('View', $user2));
		
		$post->giveAccessTo(array('groups' => $group->ID));
		
		$this->assertTrue($post->checkPerm('View', $user2));
		
	}
}
