<?php


namespace Symbiote\MicroBlog\Model;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Security\Security;

if (!class_exists(BaseElement::class)) {
    return;
}

class MicroBlogElement extends BaseElement
{
    private static $table_name = 'MicroBlogElement';

    public function getType()
    {
        return _t('Microblog.ELEMENT_NAME', 'MicroBlog element');
    }

    public function microblogSettings()
    {
        $member = Security::getCurrentUser();
        $user = $member ? [
            'ID' => $member->ID,
            'FirstName' => $member->FirstName,
            'Surname' => $member->Surname,
            'Email' => $member->Email,
        ] : [];

        $settings = [
            'Member' => $user,
            'apiKey' => 'not set',
        ];

        $p = $this->getPage();

        if ($p->URLSegment != 'home') {
            $settings['Target'] = 'Page,' . $p->ID;
        }

        return $settings;
    }
}
