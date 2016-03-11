<?php

/**
 * Generate google sitemap for all posts
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class GeneratePostSitemapJob extends AbstractQueuedJob
{
    private static $regenerate_time = 43200;

    public function __construct()
    {
        if (!$this->totalSteps) {
            $this->toProcess = $this->getProcessIds();
            $this->currentStep = 0;
            $this->totalSteps = count($this->toProcess);
        }
    }

    /**
     * Sitemap job is going to run for a while...
     */
    public function getJobType()
    {
        return QueuedJob::QUEUED;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return _t('GeneratePostSitemapJob.REGENERATE', 'Regenerate Google sitemap .xml file for posts');
    }

    /**
     * Return a signature for this queued job
     * 
     * For the generate sitemap job, we only ever want one instance running, so just use the class name
     * 
     * @return String
     */
    public function getSignature()
    {
        return md5(get_class($this));
    }

    /**
     * Note that this is duplicated for backwards compatibility purposes...
     */
    public function setup()
    {
        parent::setup();
        increase_time_limit_to();

        $restart = $this->currentStep == 0;

        if (!$this->tempFile || !file_exists($this->tempFile)) {
            $tmpfile = tempnam(getTempFolder(), 'postsitemap');
            if (file_exists($tmpfile)) {
                $this->tempFile = $tmpfile;
            }
            $restart = true;
        }

        if ($restart) {
            $this->toProcess = $this->getProcessIds();
        }
    }
    
    protected function getProcessIds()
    {
        $list = DataList::create('MicroPost');
        $list = $list->filter(array(
            'PermSource.PublicAccess' => 1,
            'ParentID'    => 0,
            'Up:GreaterThan'    => 0,
        ));
        return $list->column('ID');
    }

    /**
     * On any restart, make sure to check that our temporary file is being created still. 
     */
    public function prepareForRestart()
    {
        parent::prepareForRestart();
        // if the file we've been building is missing, lets fix it up
        if (!$this->tempFile || !file_exists($this->tempFile)) {
            $tmpfile = tempnam(getTempFolder(), 'postsitemap');
            if (file_exists($tmpfile)) {
                $this->tempFile = $tmpfile;
            }
            $this->currentStep = 0;
            $this->toProcess = $this->getProcessIds();
        }
    }

    public function process()
    {
        if (!$this->tempFile) {
            throw new Exception("Temporary sitemap file has not been set");
        }
        
        if (!file_exists($this->tempFile)) {
            throw new Exception("Temporary file $this->tempFile has been deleted!");
        }

        $remainingChildren = $this->toProcess;

        // if there's no more, we're done!
        if (!count($remainingChildren)) {
            $this->completeJob();
            $this->isComplete = true;
            return;
        }
        
        // lets process our first item - note that we take it off the list of things left to do
        $ID = array_shift($remainingChildren);

        $post = DataList::create('MicroPost')->byID($ID);

        if (!$post || !$post->exists()) {
            $this->addMessage("Post ID #$ID could not be found, skipping");
        }

        if ($post) {
            $created = $post->dbObject('Created');
            $now = new SS_Datetime();
            $now->value = date('Y-m-d H:i:s');
            $timediff = $now->format('U') - $created->format('U');

            $period = $timediff;

            if ($period > 60*60*24*7) { // > 1 week
                $post->ChangeFreq='weekly';
            } else {
                $post->ChangeFreq='daily';
            }

            // do the generation of the file in a temporary location
            $content = $post->renderWith('SitemapEntry');

            $fp = fopen($this->tempFile, "a");
            if (!$fp) {
                throw new Exception("Could not open $this->tempFile for writing");
            }
            fputs($fp, $content, strlen($content));
            fclose($fp);
        }

        // and now we store the new list of remaining children
        $this->toProcess = $remainingChildren;
        $this->currentStep++;

        if (!count($remainingChildren)) {
            $this->completeJob();
            $this->isComplete = true;
            return;
        }
    }

    /**
     * Outputs the completed file to the site's webroot
     */
    protected function completeJob()
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?>'.
                    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $content .= file_get_contents($this->tempFile);
        $content .= '</urlset>';

        $sitemap = Director::baseFolder() .'/sitemap.xml';
        
        file_put_contents($sitemap, $content);

        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }

        $nextgeneration = new GeneratePostSitemapJob();
        singleton('QueuedJobService')->queueJob($nextgeneration, date('Y-m-d H:i:s', time() + self::$regenerate_time));
    }
}
