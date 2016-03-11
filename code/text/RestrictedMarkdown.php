<?php

include_once BASE_PATH . '/microblog/thirdparty/parsedown-1.5.1/Parsedown.php';

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class RestrictedMarkdown extends TextParser
{

    public function __construct($content = "")
    {
        parent::__construct($content);
    }

    public function parse()
    {
        $parsedown = new RestrictedMarkdownParser();
        $html = $parsedown->parse(ShortcodeParser::get_active()->parse($this->content));
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        $html = $purifier->purify($html);
        return $html;
    }
}

class RestrictedMarkdownParser extends Parsedown
{

    public function __construct()
    {
        $this->InlineTypes['@'][] = 'Mentions';
        $this->inlineMarkerList .= '@';
    }

    protected function inlineMentions($Element)
    {
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
                            'class'        => 'timeline-user-mention'
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
