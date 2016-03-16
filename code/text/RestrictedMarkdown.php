<?php

include_once BASE_PATH . '/microblog/thirdparty/parsedown-1.5.1/Parsedown.php';

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class RestrictedMarkdown extends TextParser {

    public function __construct($content = "") {
        parent::__construct($content);
    }

    public function parse() {
        $parsedown = new RestrictedMarkdownParser();
        $html = $parsedown->parse(ShortcodeParser::get_active()->parse($this->content));
        $purifier = new HTMLPurifier($this->buildPurifierConfig());
        $html = $purifier->purify($html);
        return $html;
    }
    
    protected function buildPurifierConfig() {
        
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('CSS.AllowTricky', true);
        $config->set('HTML.DefinitionID', 'html5-definitions'); // unqiue id
        $config->set('HTML.DefinitionRev', 1);
        if ($def = $config->maybeGetRawHTMLDefinition()) {
            $def->addElement('section', 'Block', 'Flow', 'Common');
            $def->addElement('nav', 'Block', 'Flow', 'Common');
            $def->addElement('article', 'Block', 'Flow', 'Common');
            $def->addElement('aside', 'Block', 'Flow', 'Common');
            $def->addElement('header', 'Block', 'Flow', 'Common');
            $def->addElement('footer', 'Block', 'Flow', 'Common');
            $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
            $def->addElement('figcaption', 'Inline', 'Flow', 'Common');

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

class RestrictedMarkdownParser extends Parsedown {

    public function __construct() {
        $this->InlineTypes['@'][] = 'Mentions';
        $this->inlineMarkerList .= '@';
    }

    protected function inlineMentions($Element) {

        if (preg_match('/@(.*?):(\d+)/', $Element['text'], $matches)) {
            $member = Member::get()->byID($matches[2]);

            if ($member && $member->getTitle() == $matches[1]) {
                return array(
                    'extent' => strlen($matches[0]),
                    'element' => array(
                        'name' => 'span',
                        'text' => $matches[1],
                        'attributes' => array(
                            'data-href' => 'timeline/user/' . $matches[2],
                            'class' => 'timeline-user-mention'
                        ),
                    ),
                );
            }
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'span',
                    'text' => $matches[1]
                ),
            );
        }
    }

}
