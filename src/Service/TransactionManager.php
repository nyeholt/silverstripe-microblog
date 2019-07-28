<?php
namespace Symbiote\MicroBlog\Service;

use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\ORM\DB;

/**
 * Helper for running bits and pieces of code in transactions
 * 
 * Especially useful for running code as different user
 * 
 * @author marcus@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class TransactionManager
{
    public function __construct()
    { }

    public function runAsAdmin($closure)
    {
        // TODO This is so horribly ugly - is there no better way to know that we're in dev/build for the first time?
        $admins = Permission::get_members_by_permission('ADMIN')->First();
        if (!$admins) {
            return;
        }
        $admin = Security::findAnAdministrator();
        return $this->run($closure, $admin);
    }

    public function run($closure, $as = null)
    {
        DB::get_conn()->transactionStart();
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        $current = Security::getCurrentUser();
        if ($as) {
            Security::setCurrentUser($as);
        }

        $return = null;
        if (is_array($closure)) {
            $return = call_user_func_array($closure, $args);
        } else {
            $return = $closure();
        }

        Security::setCurrentUser($current);
        DB::get_conn()->transactionEnd();

        return $return;
    }
}
