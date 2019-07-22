<?php

namespace Symbiote\MicroBlog\Control;

use SilverStripe\Control\Controller;
use Symbiote\MicroBlog\Model\MicroPost;
use SilverStripe\Security\Security;
use PageController;

class MicroBlogController extends PageController
{
    private static $allowed_actions = [
        'show',
    ];

    public function init()
    {
        parent::init();
    }

    protected function getPost()
    {
        $postId = (int) $this->request->param('ID');

        if ($postId) {
            $post = MicroPost::get()->byID($postId);
            if ($post && $post->canView()) {
                return $post;
            }
        }
    }

    public function show()
    {
        $post = $this->getPost();

        if ($post && $post->canView()) {
            $settings = $this->microblogSettings();
            $settings['SingleView'] = true;
            $settings['FetchFilter'] = ['ThreadID' => $post->ThreadID];
            $settings['Filter'] = ['ID' => $post->ID];

            return $this->renderWith(['MicroBlogController_show', 'Page'], ['Settings' => $settings]);
        }
    }

    public function microblogSettings()
    {
        // {"Member": {"Name": "$CurrentMember.Title", "ID": "$CurrentMember.Title"}, "apiKey": "$CurrentMember.Token.Token"}
        $member = Security::getCurrentUser();
        $memberJson = [
            'Surname' => $member ? $member->Surname : '',
            'FirstName' => $member ? $member->FirstName : '',
            'Username' => $member ? $member->Username : '',
            'Name' => $member ? $member->getTitle() : '',
            'ID' => $member ? $member->ID : 0,
        ];

        return [
            'Member' => $memberJson,
            'apiKey' => $member ? $member->getToken()->Token : '',
        ];
    }
}
