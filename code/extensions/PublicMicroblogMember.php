<?php

/**
 * An object that represents the 'public' user.
 *
 * @author marcus
 */
class PublicMicroblogMember extends Member
{
    private static $fake_email = 'public@maildrop.cc';

    public function __construct($record = null, $isSingleton = false, $model = null)
    {
        parent::__construct($record, $isSingleton, $model);
        $this->Email = self::config()->fake_email;
        $this->FirstName = 'Public';
        $this->Surname = 'User';
        $this->Balance = 1;
        $this->ID = -1;
    }

    public function write($showDebug = false, $forceInsert = false, $forceWrite = false, $writeComponents = false)
    {
        return -1;
    }
}