<% require javascript(framework/javascript/jquery-ondemand/jquery.ondemand.js) %>
<a href="{$BaseHref}timeline<% if $Targeted %>?target=$ClassName,$ID<% end_if %>" class="comment-list-trigger" title="Comments ($ContextUser.UnreadPosts.count unread)">
	<span class="typcn typcn-messages"></span>
	<% if $ContextUser.UnreadPosts.count %>
	<span class="comment-count comment-count-$ContextUser.UnreadPosts.count" >$ContextUser.UnreadPosts.count</span>
	<% else %>
	<span class="comment-count comment-count-0"></span>
	<% end_if %>
	<span class="visually-hidden">Comments</span>
</a>