<?php

namespace Symbiote\MicroBlog\Text;

use HTMLPurifier_Config;
use HTMLPurifier;

class Purifier
{
    public function purify($content)
    {
        $purifier = new HTMLPurifier($this->buildPurifierConfig());
        $html = $purifier->purify($content);
        return $html;
    }

    protected function buildPurifierConfig()
    {

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('CSS.AllowTricky', true);
        $config->set('HTML.DefinitionID', 'html5-definitions'); // unqiue id
        $config->set('HTML.DefinitionRev', 1);

        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%'); //allow YouTube and Vimeo
        // This line is important allow iframe in allowed elements or it will not work    
        $config->set('HTML.AllowedElements', array('iframe')); // <-- IMPORTANT
        $config->set('HTML.AllowedAttributes', 'iframe@src,iframe@allowfullscreen,iframe@width,iframe@height,iframe@allow,iframe@frameborder');

        $def = $config->maybeGetRawHTMLDefinition();
        if (!$def) {
            $def = $config->getDefinition('HTML');
        }
        if ($def) {
            $def->addElement('section', 'Block', 'Flow', 'Common');
            $def->addElement('nav', 'Block', 'Flow', 'Common');
            $def->addElement('article', 'Block', 'Flow', 'Common');
            $def->addElement('aside', 'Block', 'Flow', 'Common');
            $def->addElement('header', 'Block', 'Flow', 'Common');
            $def->addElement('footer', 'Block', 'Flow', 'Common');
            $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
            $def->addElement('figcaption', 'Inline', 'Flow', 'Common');
            // $def->addElement('iframe', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
            //     'src' => 'URI',
            //     'type' => 'Text',
            //     'width' => 'Length',
            //     'height' => 'Length',
            //     'frameborder' => 'Text',
            //     'allow' => 'Text',
            //     'allowfullscreen' => 'Bool',
            // ]);

            $def->addElement('video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', array(
                'src' => 'URI',
                'type' => 'Text',
                'width' => 'Length',
                'height' => 'Length',
                'poster' => 'URI',
                'preload' => 'Enum#auto,metadata,none',
                'controls' => 'Bool',
            ));
            $def->addElement('source', 'Block', 'Flow', 'Common', array(
                'src' => 'URI',
                'type' => 'Text',
            ));
        }
        return $config;
    }
}
