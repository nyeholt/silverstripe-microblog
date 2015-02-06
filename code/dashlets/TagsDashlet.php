<?php
if (class_exists('Dashlet')) {
/**
 * Displays the list of tags in the system
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class TagsDashlet extends Dashlet {
	public static $title = 'Tags';
}

class TagsDashlet_Controller extends Dashlet_Controller {
	
	public function Tags() {
		
		$select = array(
			'Title'
		);
		$query = new SQLQuery($select, 'PostTag');
		
		$query->selectField('count(PostTag.ID)', 'Number');
		$query->selectField('PostTag.ID');
		
		$query->addInnerjoin('MicroPost_Tags', 'PostTag.ID = MicroPost_Tags.PostTagID');
		
		$date = date('Y-m-d H:i:s', strtotime('-1 month'));
		$query->addWhere("MicroPost_Tags.Tagged > '$date'");
		
		$query->addGroupBy('PostTag.ID');
		
		$query->setLimit(20);
		
		$rows = $query->execute();
		
		$tags = ArrayList::create();
		
		$home = PostAggregatorPage::get()->first();
		
		foreach ($rows as $row) {
			$data = new ArrayData($row);
			if ($home) {
				$data->Link = $home->Link('tag/' . $data->Title);
			}
			
			$tags->push($data);
		}
		return $tags;
	}
}
}