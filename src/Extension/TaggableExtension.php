<?php

namespace Symbiote\MicroBlog\Extension;

use SilverStripe\ORM\DataExtension;
use Symbiote\MicroBlog\Model\PostTag;

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class TaggableExtension extends DataExtension
{
    private static $many_many = array(
        'Tags'        => PostTag::class
    );

    private static $many_many_extraFields = array(
        'Tags'    => array(
            'Tagged'    => 'Datetime',
        )
    );
}
