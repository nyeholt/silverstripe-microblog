<?php

/**
 * Lower bound of Wilson score confidence interval for a Bernoulli parameter
 * 
 * @see http://www.evanmiller.org/how-not-to-sort-by-average-rating.html
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class Rateable extends DataExtension {
	private static $db = array(
		'Up'			=> 'Int',
		'Down'			=> 'Int',
		
	);
	
	/**
	 * Add in rating information
	 * 
	 * @param SQLQuery $query
	 * @param DataQuery $dataQuery 
	 */
	public function augmentSQL(SQLQuery &$query) {
		
		$base = ClassInfo::baseDataClass($this->owner);
		
		$bound = '((Up + 1.9208) / (Up + Down) - ' . 
                   '1.96 * SQRT((Up * Down) / (Up + Down) + 0.9604) / ' .
                   '(Up + Down)) / (1 + 3.8416 / (Up + Down))  / SQRT(HOUR(TIMEDIFF(NOW(), '.$base.'.Created)) + 1)';
	
		$query->selectField($bound, 'WilsonRating');
	}
}
