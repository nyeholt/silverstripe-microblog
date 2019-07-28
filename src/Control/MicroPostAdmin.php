<?php

namespace Symbiote\MicroBlog\Control;

use SilverStripe\Admin\ModelAdmin;
use Symbiote\MicroBlog\Model\MicroPost;
use Symbiote\MicroBlog\Model\SimpleMemberList;

/**
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class MicroPostAdmin extends ModelAdmin
{
    private static $managed_models = [
        MicroPost::class,
        SimpleMemberList::class
    ];
    private static $url_segment = 'microposts';
    private static $menu_title = 'Posts';
}
