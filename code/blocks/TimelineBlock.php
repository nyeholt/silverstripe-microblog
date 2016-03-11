<?php

if (class_exists('Block')) {
    class TimelineBlock extends Block
    {

        private static $singular_name = 'Timeline Block';
        private static $plural_name = 'Timeline Blocks';

        private static $db = array(
        'ShowTaggedWith'        => 'MultiValueField'
    );

        public function TimelineUrl()
        {
            TimelineController::include_microblog_requirements();
            Requirements::javascript('microblog/javascript/timeline-dashlet.js');
            $tags = $this->ShowTaggedWith->getValues();
            $extra = '';
            if (count($tags)) {
                $extra = '?tags=' . urlencode(implode(',', $tags));
            }
            return 'timeline' . $extra;
        }
    }
}
