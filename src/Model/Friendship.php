<?php

namespace Symbiote\MicroBlog\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Describes a relationship between users 
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class Friendship extends DataObject
{

    private static $table_name = 'Friendship';

    private static $db = array(
        'Status'            => "Enum('Approved,Pending','Pending')",
    );

    private static $has_one = array(
        'Initiator'            => Member::class,
        'Other'                => Member::class,
    );

    private static $defaults = array(
        'Status'            => 'Pending',
    );

    /**
     * get the 'other' view of this friendship 
     */
    public function reciprocal()
    {
        return Friendship::get()->filter(array(
            'InitiatorID'        => $this->OtherID,
            'OtherID'            => $this->InitiatorID
        ))->first();
    }

    public function canView($member = null)
    {
        return true;
    }

    public function canEdit($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        return $member->ID == $this->InitiatorID;
    }

    public function canDelete($member = null)
    {
        return $this->canEdit($member);
    }
}
