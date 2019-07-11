<?php

namespace Symbiote\MicroBlog\Text;

use SilverStripe\Security\Member;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * @author marcus@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class PostFormatter 
{
    // cached list of already-mentioned people. 
    // stored for later lookup / use
    public static $mentioned = array();

    protected $content = '';

    public function __construct($content = "")
    {
        $this->content = $content;
    }

    public function parse()
    {
        $html = ShortcodeParser::get_active()->parse($this->parseMentions($this->content));
        // $purifier = new HTMLPurifier($this->buildPurifierConfig());
        // $html = $purifier->purify($html);
        return $html;
    }

    public function parseMentions($content)
    {
        if (preg_match_all('/@(.*?):(\d+)/', $content, $matches)) {

            $replacements = [];
            for ($i = 0, $c = count($matches[2]); $i < $c; $i++) {
                $memberId = $matches[2][$i];
                $name = $matches[1][$i];
                $fullReplace = '@' . $name . ':' . $memberId;
                $shortCode = '[mention_person person="' . $memberId . '" name="' . str_replace('"', '-', $name) . '"]';

                $replacements[$fullReplace] = $shortCode;
            }

            $returnText = str_replace(array_keys($replacements), array_values($replacements), $content);

            return $returnText;
        }
        return $content;
    }

    public static function replace_mentions($arguments, $content = null, $parser = null)
    {
        if (!isset($arguments['person']) || !is_numeric($arguments['person'])) return;

        $id = $arguments['person'];
        $name = isset($arguments['name']) ? $arguments['name'] : '';

        // get the member, and see if the name matches the title, which verifies that the user
        // who submitted knows who the person is
        $member = Member::get()->byID($id);

        if ($member || isset(static::$mentioned[$id])) {
            if (!isset(static::$mentioned[$id])) {
                static::$mentioned[$id] = $member->getTitle();
            }

            return sprintf(
                '<span data-href="%s" class="timeline-user-mention">%s</span>',
                'timeline/user/' . $id,
                $name
            );
        }
    }

    protected function buildPurifierConfig()
    {

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
