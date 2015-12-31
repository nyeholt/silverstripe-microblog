<?php
if (class_exists('Dashlet')) {
    /**
 * Displays the list of tags in the system
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class TagsDashlet extends Dashlet
{
    public static $title = 'Tags';
}

    class TagsDashlet_Controller extends Dashlet_Controller
    {
    
        public function Tags()
        {
            $select = array(
            'Title'
        );
            $query = new SQLQuery($select, 'PostTag');
        
            $query->selectField('count(PostTag.ID)', 'Number');
            $query->selectField('PostTag.ID');
        
            $query->addInnerjoin('MicroPost_Tags', 'PostTag.ID = MicroPost_Tags.PostTagID');
        
            $date = date('Y-m-d H:i:s', strtotime('-1 month'));
            $query->addWhere("MicroPost_Tags.Tagged > '$date'");
            $query->addWhere('"PostTag"."Title" NOT LIKE \'SELF_TAG%\'');
            $query->addGroupBy('PostTag.ID');

            $query->setLimit(20);
        
            $rows = $query->execute();
        
            $tags = ArrayList::create();
        
            foreach ($rows as $row) {
                $data = new ArrayData($row);
                $data->Link = Controller::join_links(TimelineController::URL_SEGMENT, '?tags=' . urlencode($data->Title));
            
                $tags->push($data);
            }
            return $tags;
        }
    }
}
