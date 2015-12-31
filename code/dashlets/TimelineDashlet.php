<?php

if (class_exists('Dashlet')) {
    /**
     * @author marcus@silverstripe.com.au
     * @license BSD License http://silverstripe.org/bsd-license/
     */
    class TimelineDashlet extends Dashlet
    {
        public static $title = 'Timeline';
        
        private static $db = array(
            'FilterTags'        => 'MultiValueField',
        );

        public function canCreate($member = null)
        {
            if (!$member) {
                $member = Member::currentUser();
            }
            return $member->ID > 0;
        }
        
        public function getDashletFields()
        {
            $fields = parent::getDashletFields();
            $all = PostTag::get()->map('Title', 'Title')->toArray();
            $fields->insertAfter(MultiValueTextField::create('FilterTags', 'Tags to filter by', $all), 'Title');
            return $fields;
        }
    }

    class TimelineDashlet_Controller extends Dashlet_Controller
    {

        private static $allowed_actions = array(
            'timeline',
            'ShowDashlet',
        );

        /**
         * @var MicroBlogService
         * 
         */
        public $microBlogService;
        public $securityContext;

        public static $dependencies = array(
            'microBlogService'        => '%$MicroBlogService',
            'securityContext'        => '%$SecurityContext',
        );

        public function __construct($widget = null, $parent = null)
        {
            parent::__construct($widget, $parent);
            if ($parent && $parent->getRequest()) {
                $this->request = $parent->getRequest();
            }
        }


        public function TimelineUrl()
        {
            $tags = $this->widget->FilterTags->getValues();
            $extra = '';
            if (count($tags)) {
                $extra = '?tags=' . urlencode(implode(',', $tags));
            }
            return 'timeline' . $extra;
        }

        public function ShowDashlet()
        {
            // explicit inclusion so it doesn't need to jquery.ondemand these files, which tends to die
            Requirements::javascript('microblog/javascript/timeline-dashlet.js');
            
            return '';
            // oh man this is so hacky, but I don't really quite know the best way to do what I want which is
            // one controller and about ten different ways to access it... all depending on context of course!
            $controller = $this->timeline();
            $controller->init();
            $rendered = $controller->renderWith('FullTimeline');
            // $controller->index(); // $controller->handleRequest($this->request, $this->model);
            return $rendered instanceof SS_HTTPResponse ? $rendered->getBody() : $rendered;
        }
    }
}
