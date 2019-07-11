<?php

namespace Symbiote\MicroBlog\Model;

use SilverStripe\ORM\DataObject;

/**
 * @author <marcus@symbiote.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class PostTag extends DataObject
{
    private static $table_name = 'PostTag';

    private static $db = array(
        'Title'        => 'Varchar(128)'
    );
}
