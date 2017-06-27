# Microblog

## Getting Started

* Install via composer, it'll get the dependencies right!
* Alternatively, you'll need the following modules/libs too
  * "silverstripe/multivaluefield": "~2.0",
  * "silverstripe/queuedjobs": "~2.2",
  * "silverstripe/restrictedobjects": "~2.1",
  * "silverstripe/webservices": "~3.1",
  * "silverstripe/select2": "~1.0",
  * "ezyang/htmlpurifier": "dev-master#c67e4c2f7e06f89ca0eb4ce72b191144e40dc3ef"
* Run dev/build
* Go to /timeline
* Done! 

If you'd like to customise the way the timeline appears, you can create a 
`MicroBlogPage`, which allows setting various options directly from the CMS
that allow you to configure the display of posts and replies. See below for
discussion of what these options do

## Configuration 

The following options can be set either globally against the 
TimelineController via yaml, or within the CMS against the MicroBlogPage.

* **Replies** (true): Should it be possible to reply to posts at all?
* **Threaded** (false): Should reply posts be allowed to have 'sub' replies? 
  If true, any "reply" comment can in turn be replied on.
* **Voting** (true): Should voting on posts be allowed?
* **ShowReply** (true): If replies are enabled, should the 'reply' box always
  be visible, or should the user need to click a 'reply' button first?
* **Sorting** (false): Should users be able to select how the post list is
  sorted? (Note: Currently this feature has no effect if set to true.)
* **UserTitle** (false): Should users be able to specify a post title directly?
* **ShowTitlesOnly** (false): For the main post listing, should only the titles 
  of posts be displayed? If set to true, this provides for a forum like 
  interface
* **ShowTitlesInPost** (false): Should the title of a post be automatically 
  output? Useful when tied in with the ShowTitlesOnly setting


## URL string parameters

The root URL for a timeline is `timeline` - to this, you can add extra params
to change the output content, which will also flow on to posts made on that 
timeline. 

* `tags` - a comma separated list of tags to display (will also be bound on 
  new posts added)
* `target` - a Class{comma}ID representation of a dataobject to bind a post to,
  eg Page,4
* `since` - a date-time to display posts after this date
* `sort` - a field to sort by. 


## Embedding in other pages

To embed a timeline in other pages, you can either have the timeline embedded 
inline, or included via an iframe. 

**Inline**

In your page code, 

* call `TimelineController::include_microblog_requirements()`
* add `Requirements::javascript('microblog/javascript/timeline-dashlet.js');`
* In your template, output `<div class='timeline-container' data-url='$url'></div>` 
  * where `$url` is `timeline` with any parameters as above; for example, 
    `timeline?target=$ClassName,$ID` to output a list of comments for _this_ page

**Inline using shortcodes**

* Use the shortcode [microblog_timeline tags="" target=""] to automatically output 
  the above 

**IFrame**

* Create an iframe with `src="timeline?target=XX,Y"` as per above parameters. 

## Scheduled processes

* Digest emails: Users can choose to receive a digest of posts, generated 
  either weekly or daily. You must create the `MicroPostDigestJob` job, which
  will run nightly and package, then send, the digest. 

## Spam management

The module provides an integrates with Defensio to provide analysis of posts 
to automatically detect spam posts. This is tied in with a member's 
"reputation", or down-vote number, as to whether the member's posts require
spam review, or administrator review, prior to display. 

To enable the spam review, ensure you have the QueuedJobs module installed, and
have set `PostProcessJob::$api_key` in your config file. Once a user's vote 
'balance' (ie Up minus Down) is less than -20, all their posts will be routed
via Defensio. 

Note: As of March 2015, this functionality is being overhauled to better 
accomodate admin user interaction with new users and spam posts. 


## API

From code, the primary API methods can be accessed by referencing a
`MicroBlogService` object in your class. The following methods are available

* `globalFeed`: Access all the microposts in the system that a user has access to
* `getStatusUpdates`: 
* `getTimeline`: Get all the posts for a particular user, where the posters
  are _only_ from the list of that user's friends. 
* `findMember`: Looks up a member based on first name, surname or email
* `addFriendship`: Creates a friendship between two users. This ultimately 
  configures appropriate entries in the `SimpleMemberList` items that represent
  the list of followers and followees for a particular user. 
* `removeFriendship`: Removes people from friendship
* `rawPost`: Retrieves raw post content, rather than already-marked-up HTML 
  content. 
* `unreadPosts`: Gets a list of all unread posts for the _current_ logged in
  user
* `createPost`: Creates a new post in the system
* `savePost`: Saves a post 
* `deletePost`: Deletes a post
* `vote`: Vote on a post


## FAQs

**Will there be shadow-banning or similar?**

Yes! Eventually... The idea will be that a shadow-banned user will still
be able to post as normal, however their post permissions will be set so that
only they, or administrators, can see the content of these posts. 










