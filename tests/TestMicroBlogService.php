<?php

namespace Symbiote\MicroBlog\Test;

use Symbiote\MicroBlog\Service\MicroBlogService;
use Symbiote\MicroBlog\Model\MicroPost;
use SilverStripe\Security\Member;
use SilverStripe\Dev\SapphireTest;

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
		
		$tags = singleton(MicroBlogService::class)->extractTags($post);
	}
	
	public function testUserFollowing() {
		
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
		$this->logInWithPermission();
		MicroPost::get()->removeAll();
		
		$group = $this->objFromFixture('Group', 'posters');
		$member = $this->objFromFixture('Member', 'user1');
		$user2 = $this->objFromFixture('Member', 'user2');
		
		$groups = $member->Groups()->toArray();
		
		$svc = singleton(MicroBlogService::class);
		/* @var $svc MicroBlogService */
		
		$post = $svc->createPost($member, "My post content"); // , null, 0, null, array('groups' => $group->ID));
		
		$this->assertTrue($post->checkPerm('View', $member));
		$this->assertFalse($post->checkPerm('View', $user2));
		
		$post->giveAccessTo(array('groups' => $group->ID));
		
		$this->assertTrue($post->checkPerm('View', $user2));
		
	}
	
	public function testCreateTypedPost() {
		MicroPost::get()->removeAll();
		$svc = singleton(MicroBlogService::class);
		$svc->typeAge = array();
		/* @var $svc MicroBlogService */
		
		$member = $this->objFromFixture('Member', 'user1');
		
		$post = $svc->createPost($member, "My test post");
		
		$this->assertTrue($post->ID > 0);
		
		// get the list, and check that this post is in it
		$posts = $svc->getStatusUpdates();
		
		$this->assertEquals($post->ID, $posts[0]->ID);
		
		$post2 = $svc->createPost($member, "Another test post", array('PostType'	=> 'mypost'));
		
		$posts = $svc->getStatusUpdates();
		$this->assertEquals(2, count($posts));
		$this->assertEquals($post2->ID, $posts[0]->ID);
		
		$svc->typeAge = array('mypost' => 2);
		sleep(3);
		
		$posts = $svc->getStatusUpdates();
		$this->assertEquals(1, count($posts));
		
		$this->assertEquals($post->ID, $posts[0]->ID);
		
	}
}
