<?php

/**
 * @author marcus
 */
class TestPostFormatter extends SapphireTest
{
    public function testMentionExtract() {
        $content = '@mention:2 a user @other:3';

        $formatter = new PostFormatter();
        $out = $formatter->parseMentions($content);

        $expect = '[mention_person person="2" name="mention"] a user [mention_person person="3" name="other"]';

        $this->assertEquals($expect, $out);
    }

    public function testParsePost() {
        $content = '@mention:2 a user @other:3';

        $formatter = new PostFormatter($content);

        PostFormatter::$mentioned[2] = 'mention';
        PostFormatter::$mentioned[3] = 'other';

        $out = $formatter->parse();


        $expect = '<span class="timeline-user-mention">mention</span> a user <span class="timeline-user-mention">other</span>';
        $this->assertEquals($expect, $out);
    }
}