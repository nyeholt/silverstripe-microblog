<?php

namespace Symbiote\MicroBlog\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

/**
 * @author marcus@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class MicroPostVote extends DataObject
{
    private static $table_name = 'MicroPostVote';

    private static $db = array(
        'Direction'        => 'Int',
    );

    private static $has_one = array(
        'User'        => Member::class,
        'Post'        => MicroPost::class,
    );
}
