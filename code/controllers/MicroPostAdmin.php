<?php

/**
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class MicroPostAdmin extends ModelAdmin
{
    private static $managed_models = array('MicroPost');
    private static $url_segment = 'microposts';
    private static $menu_title = 'Posts';
}
