# Microblog module

An alternative for providing blog-like functionality, following the direction
of Reddit/Twitter/Facebook, but also allowing forum-like structures. 

For full instructions, please see docs/en/index.md - or

```
composer require silverstripe/microblog
```


A summary of features

* Markdown parsing of content
* Auto-inspection of URLs for OpenGraph / OEmbed ability
* Drag/drop file upload
* @mentions of users
* Threaded replies
* Edit posts in-place
* Can be used for comment/discussion threads on individual pages (timeline?target=Page,11)
* Can be embedded in other pages using an iframe (timeline?embed=1)
* Notifications (with the [silverstripe-australia/notifications](https://packagist.org/packages/silverstripe-australia/notifications) module installed)
* Can be configured to resemble a forum, or left as a twitter / facebook 
  style feed of posts
* Create isolated blogs using the MicroBlogPage that allows direct configuring
  of how posts will appear / can be interacted with
* /timeline provides a site-wide twitter-like stream of posts


