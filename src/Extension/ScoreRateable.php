<?php

namespace Symbiote\MicroBlog\Extension;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\DataQuery;

/**
 * Lower bound of Wilson score confidence interval for a Bernoulli parameter
 *
 * @see http://www.evanmiller.org/how-not-to-sort-by-average-rating.html
 *
 * @author marcus@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class ScoredRateable extends DataExtension
{
    private static $db = array(
        'Up'            => 'Int',
        'Down'            => 'Int',

    );

    /**
     * Add in rating information
     *
     * @param SQLQuery $query
     * @param DataQuery $dataQuery
     */
    public function augmentSQL(SQLSelect $query, DataQuery $dataQuery = null)
    {

        $base = ClassInfo::baseDataClass($this->owner);

        $base = $base::config()->table_name;

        $simple = DB::get_conn() instanceof SQLite3Database ? true : false;

        if ($simple) {
            $bound = "(($base.Up / $base.Down) / ($base.Up + $base.Down))";
        } else {
            $bound = "(($base.Up + 1.9208) / ($base.Up + $base.Down) - " .
                "1.96 * SQRT(($base.Up * $base.Down) / ($base.Up + $base.Down) + 0.9604) / " .
                "($base.Up + $base.Down)) / (1 + 3.8416 / ($base.Up + $base.Down))  / SQRT(HOUR(TIMEDIFF(NOW(), $base.Created)) + 1)";
        }

        $query->selectField($bound, 'WilsonRating');

        $query->selectField("($base.Up + $base.Down)", 'ActiveRating');

        $query->selectField("($base.Up - $base.Down)", "PositiveRating");
    }
}
