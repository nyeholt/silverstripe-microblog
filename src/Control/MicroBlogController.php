<?php

namespace Symbiote\MicroBlog\Control;

use SilverStripe\Control\Controller;
use Symbiote\MicroBlog\Model\MicroPost;
use SilverStripe\Security\Security;
use PageController;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;

class MicroBlogController extends PageController
{
    private static $allowed_actions = [
        'show',
        'media',
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

    public function media() {
        $item = $this->getMediaItem();

        if ($item && $item->canView()) {
            $settings = $this->microblogSettings();
            $settings['FetchFilter'] = ['Target' => 'File,' . $item->ID];
            $settings['Filter'] = ['ParentID' => 0, 'Target' => 'File,' . $item->ID];
            $settings['Target'] = 'File,' . $item->ID;

            return $this->renderWith(
                ['MicroBlogController_media', 'Page'], 
                [
                    'Settings' => $settings, 
                    'Item' => $item,
                    'IsImage' => $item instanceof Image ? true : false,
                ]
            );
        }
    }

    protected function getMediaItem() {
        $itemId = (int) $this->request->param('ID');

        if ($itemId) {
            $item = File::get()->byID($itemId);
            if ($item && $item->canView()) {
                return $item;
            }
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
