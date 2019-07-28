<?php

namespace Symbiote\MicroBlog\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

/**
 * A generic list of members. Essentially a group without any parent
 * inheritance, that removes management of user lists away from 
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class SimpleMemberList extends DataObject 
{
    private static $table_name = 'SimpleMemberList';
    
    private static $db = array(
        'Title'        => 'Varchar(255)',
        
    );

    private static $many_many = array(
        'Members'        => Member::class,
    );
    
    public function getAllMembers()
    {
        return $this->Members();
    }
}
