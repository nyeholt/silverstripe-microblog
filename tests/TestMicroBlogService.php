<?php

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class TestMicroBlogService extends SapphireTest {
	
	public function setUp() {
		Restrictable::set_enabled(false);
	}
	
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
		
		$svc->addFriendship($memberOne->Profile(), $memberTwo->Profile());
		
		// gah - ss3's testing setup needs to be better sorted to be able to do this bit...
	}
}
