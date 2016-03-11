<?php
if (class_exists('Dashlet')) {
    /**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class FriendsDashlet extends Dashlet
{
    public static $title = "People";
    
    public function canCreate($member = null)
    {
        return parent::canCreate($member);
    }
}

    class FriendsDashlet_Controller extends Dashlet_Controller
    {
    
        private static $allowed_actions = array(
        'FriendSearchForm',
    );
    
        public $microBlogService;
        public $securityContext;

        public static $dependencies = array(
        'microBlogService'        => '%$MicroBlogService',
        'securityContext'        => '%$SecurityContext',
    );
    
        public function init()
        {
            parent::init();
        
            Requirements::javascript(THIRDPARTY_DIR . '/jquery-form/jquery.form.js');
            Requirements::javascript('microblog/javascript/friends.js');
        }
    
        public function FriendSearchForm()
        {
            $fields = new FieldList(
            new TextField('Term', _t('MicroBlog.SEARCH_FOR_FRIENDS', 'New people'))
        );
        
            $actions = new FieldList(new FormAction('find', _t('MicroBlog.GO', 'Go')));
            $form = new Form($this, 'FriendSearchForm', $fields, $actions);
        
            return $form;
        }
    
        public function find($data, Form $form)
        {
            $term = isset($data['Term']) ? $data['Term'] : null;
        
            if ($term) {
                if ($this->request->isAjax()) {
                    $possible = $this->microBlogService->findMember($term);
                    if ($possible) {
                        $output = $this->customise(array('Items' => $possible))->renderWith('FriendsResultList');
                        return $output;
                    }
                }
            }
        
            return '';
        }
    }
}
