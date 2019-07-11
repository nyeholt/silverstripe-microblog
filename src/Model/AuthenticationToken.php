<?php

namespace Symbiote\MicroBlog\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

class AuthenticationToken extends DataObject
{
    private static $table_name = 'AuthenticationToken';

    private static $db = [
        'Token' => 'Varchar(1024)',
    ];

    private static $has_one = [
        'Member' => Member::class,
    ];

    private static $default_sort = 'ID DESC';

    private static $summary_fields = ['Token', 'Created'];
}