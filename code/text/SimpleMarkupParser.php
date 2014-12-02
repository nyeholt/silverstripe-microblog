<?php

/**
 * Description of viewhelpers
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class SimpleMarkupParser extends TextParser {
	
	public function parse() {
		return nl2br($this->content);
	}
}
