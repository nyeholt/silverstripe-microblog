<?php
use Symbiote\MicroBlog\Model\MicroPost;
use Symbiote\MicroBlog\Text\PostFormatter;
use SilverStripe\View\Parsers\ShortcodeParser;

ShortcodeParser::get('default')->register('mb_video', array(MicroPost::class, 'handle_video'));
ShortcodeParser::get('default')->register('mention_person', array(PostFormatter::class, 'replace_mentions'));
// ShortcodeParser::get('default')->register('microblog_timeline', array('TimelineController', 'timeline_shortcode'));

