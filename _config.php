<?php

BBCodeParser::enable_smilies(true);

ShortcodeParser::get('default')->register('mb_video', array('MicroPost', 'handle_video'));
ShortcodeParser::get('default')->register('mention_person', array('PostFormatter', 'replace_mentions'));
