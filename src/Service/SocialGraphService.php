<?php

namespace Symbiote\MicroBlog\Service;

use SilverStripe\ORM\FieldType\DBField;
use Embed\Embed;

/**
 * @author marcus@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class SocialGraphService
{

    public $oembedOptions = [
        'min_image_width' => 100,
        'min_image_height' => 100,
        'width' => '',
        'height' => '',
        'choose_bigger_image' => false,
        // 'images_blacklist' => 'example.com/*',
        // 'url_blacklist' => 'example.com/*',
        'follow_canonical' => true,
        'html' => [
            'max_images' => 10,
            'external_images' => false
        ]
    ];

    public $lookupLinks = false;

    /**
     * Check whether a given URL is actually an html page 
     */
    public function isWebpage($url)
    {
        $url = filter_var($url, FILTER_VALIDATE_URL);
        if (!strlen($url)) {
            return false;
        }

        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_HEADER, 1); // get the header 
        curl_setopt($c, CURLOPT_NOBODY, 1); // and *only* get the header 
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1); // get the response as a string from curl_exec(), rather than echoing it 
        curl_setopt($c, CURLOPT_FRESH_CONNECT, 1); // don't use a cached version of the url 
        $result = curl_exec($c);

        if (!$result) {
            return false;
        }

        if (stripos($result, 'Content-type: text/html')) {
            return true;
        }

        return false;
    }

    public function isImage($url)
    {
        $url = filter_var($url, FILTER_VALIDATE_URL);
        $pattern = '!^https?://([a-z0-9\-\.\/\_]+\.(?:jpe?g|png|gif))$!Ui';
        return strlen($url) && preg_match($pattern, $url);
    }

    /**
     * Extract a title from a given piece of content
     * 
     * @param string $content 
     *					The content to get a title for
     * @param boolean $retrieveTitle
     *					Whether to pull the locations title tag down
     */
    public function extractTitle($content, $retrieveTitle = false)
    {
        if ($retrieveTitle) { } else {
            if ($this->isImage($content)) {
                return 'Image: ' . basename($content);
            }

            if ($this->isWebpage($content)) {
                return 'Website: ' . basename($content);
            }

            return DBField::create_field('Text', $content)->LimitWordCount(5, '');
        }
    }

    /**
     * Convert a single URL, assumes $url has been verified to be 
     * a real URL
     * 
     * @param string $url 
     */
    public function convertUrl($url)
    {
        $oembed = Embed::create($url, $this->oembedOptions);
        if ($oembed) {
            // @see https://github.com/oscarotero/Embed/issues/65
            $noAspectClass = !$oembed->aspectRatio ? "MicroBlogPost__Embed__NoAspect" : "";
            $content = '<div class="MicroBlogPost__Embed ' . $noAspectClass . '" style="padding-bottom: ' . $oembed->aspectRatio. '%">' . $oembed->code . '</div>';
            $link = $oembed->url;
            if (!strlen($oembed->code)) {
                $content = "[{$oembed->title}]($link)";
            }
            
            return array('Title' => $oembed->title, 'Content' => $content);
        }

        // $graph = OpenGraph::fetch($url);

        // if ($graph) {
        //     foreach ($graph as $key => $value) {
        //         $data[$key] = Varchar::create_field('Varchar', $value);
        //     }
        //     if (isset($data['url'])) {
        //         return array('Title' => $graph->Title, 'Content' => MicroPost::create()->customise($data)->renderWith('OpenGraphPost'));
        //     }
        // }

        if ($this->lookupLinks) {
            // get the post and take its <title> tag at the very least
            $service = new RestfulService($url);
            $response = $service->request();

            if ($response && $response->getStatusCode() == 200) {
                if (preg_match('/<title>(.*?)<\/title>/is', $response->getBody(), $matches)) {
                    $title = Convert::raw2xml(strip_tags(trim($matches[1])));
                    return array('Title' => $title, 'Content' => "<a href='$url'>$title</a>");
                }
            }
        }
    }

    /**
     * Analyse a post and see if there's particular content that should be extracted
     * 
     * @param string $post
     * @param string $url
     * @return type 
     */
    public function convertPostContent($post)
    {
        $content = $post->Content;

        $lines = explode("\n", $content);

        $newContent = array();
        $title = '';
        $converted = false;

        // store the converted items
        $convertedLinks = array();

        foreach ($lines as $line) {
            $url = trim($line);
            if (strlen($url) && $this->isWebpage($url)) {
                $convertedContent = $this->convertUrl($url);
                if ($convertedContent) {
                    $converted = true;
                    $line = 'CONVERTEDCONTENT:' . count($convertedLinks);
                    $convertedLinks[] = $convertedContent;
                    $title = strlen($convertedContent['Title']) ? $convertedContent['Title'] : '';
                }
            }
            $newContent[] = $line;
        }

        $newContent = implode("\n", $newContent);

        
        // $newContent = RestrictedMarkdown::create($newContent)->parse();

        // replace the converted bits
        $newContent = preg_replace_callback('/CONVERTEDCONTENT:(\d+)/is', function ($bit) use ($convertedLinks) {
            return isset($convertedLinks[$bit[1]]) ? $convertedLinks[$bit[1]]['Content'] : '';
        }, $newContent);

        if ($converted) {
            $post->IsOembed = true;
            $post->OriginalContent = $post->Content;
            $post->Content = $newContent;
            $post->Title = $title;
        }

        return $post;
    }
}
