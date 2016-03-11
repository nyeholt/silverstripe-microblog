<?php

/**
 * Information about a user that's visible to everyone
 * 
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class PublicProfile extends DataExtension
{
    private static $db = array(
        'Username'        => 'Varchar',
        'Votes'            => 'Int',
    );

    public function Link()
    {
        $member = $this->owner->Member();
        if ($member) {
            return $member->Link();
        }

        $microblog = MicroBlogPage::get()->filter('ParentID', 0)->first(); //  DataObject::get_one('MicroBlogPage', '"ParentID" = 0');
        if ($microblog) {
            return $microblog->Link() . '/' . $this->MemberID;
        }
    }

    public function canView($member=null)
    {
        return true;
    }
}
