<?php

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class TaggableExtension extends DataExtension
{
    private static $many_many = array(
        'Tags'        => 'PostTag'
    );
    
    private static $many_many_extraFields = array(
        'Tags'    => array(
            'Tagged'    => 'SS_Datetime',
        )
    );
}
