<?php

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class RestrictedMarkdown extends TextParser {
	public function __construct($content = "") {
		parent::__construct($content);
	}

	public function parse(){
		include_once BASE_PATH . '/microblog/thirdparty/parsedown-0.9.0/Parsedown.php';
		$parsedown = new Parsedown();
		return $parsedown->parse(strip_tags(ShortcodeParser::get_active()->parse($this->content)));
		
		require_once BASE_PATH . '/microblog/thirdparty/phpmarkdown/markdown.php';
		return Markdown(strip_tags(ShortcodeParser::get_active()->parse($this->content)));
	}
}