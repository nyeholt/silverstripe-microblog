<% require javascript(framework/javascript/jquery-ondemand/jquery.ondemand.js) %>
<% require javascript(microblog/javascript/timeline-commenter.js) %>
<% require css(microblog/css/commenter.css) %>

<a href="{$BaseHref}timeline<% if $Targeted %>?target=$ClassName,$ID<% end_if %>" class="comment-list-trigger" data-target='$ClassName,$ID' title="Show timeline comments" data-tooltip aria-haspopup="true">
	<span class="typcn typcn-messages"></span>
	<span class="comment-count comment-count-0"></span>
	<span class="visually-hidden">Comments</span>
</a>

<!--
To make use of a dialog for displaying the output, please add

<div id="comments-modal-foundation" class="reveal-modal xlarge" data-reveal></div>

or 

<div id="comments-modal-ui" class="reveal-modal xlarge" data-reveal></div>

to your  Page.ss template, along with either jquery-ui or foundation
-->