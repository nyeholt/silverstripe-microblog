<% if Posts %>

<% if $QueryOffset %>
<input type="hidden" value="$QueryOffset.ATT" name="postOffset" />
<% end_if %>
<% if SortBy %>
<input type="hidden" value="$SortBy.ATT" name="timelineSort" />
<% end_if %>

<% loop Posts %>
	<div class="microPost $PostType <% if $ParentID > 0 %>hasparent<% else %>toplevel  <% if $Top.Options.ShowTitlesOnly %>collapsed-post<% end_if %> <% end_if %> <% if $isUnreadByUser %>unread<% end_if %>" 
		 data-id="$ID" data-owner="$Owner.ID" data-parent="$ParentID" id="post$ID" data-rating="$WilsonRating" data-editable="1">
		<div class="microPostContent">
			<% include BeforePostContent TimelineOptions=$Top.Options %>
			<% if $Top.Options.ShowTitlesInPost && $ParentID == 0 %>
			<h3 class="micro-post-title">
				<% if $Top.Options.ShowTitlesOnly %>
                <span class="post-expander" data-id="post$ID"></span><a href="$Link" title="Link to view the full text of $Title.ATT">$Title</a>
				<% else %>
				$Title
				<% end_if %>
			</h3>
			<% end_if %>
			
			<div class="postText">
				<% if $RenderedContent %>
				$RenderedContent.raw
				<% else %>
				<% include PostContent %>
				<% end_if %>
			</div>
			
			<% include AfterPostContent TimelineOptions=$Top.Options %>
			
			<% if not $DisableReplies %>
			<% if $ParentID == 0 || $Top.Options.Threaded %>
			<!-- note that the action is left blank and filled in with JS because otherwise the
				recursive template loses context of what to fill in, so we use our top level form -->
			<form id="replyTo$ID" method="POST" action="" class="replyForm <% if not $Top.Options.ShowReply %>hiddenreplies<% end_if %>" >
				<input type="hidden" value="$SecurityID" name="SecurityID" />
				<input type="hidden" name="ParentID" value="$ID" />
				<textarea placeholder="Add reply..." name="Content" class="expandable postContent"></textarea>
				<input type="submit" value="Reply" name="action_savepost" class="post-reply" />
			</form>
			<% end_if %>
			<% end_if %>

			<div class="postReplies">
				<% if $Top.Options.Replies %>
				<% if Posts %>
				<% include Timeline Options=$Top.Options %>
				<% end_if %>
				<% end_if %>
			</div>
		</div>
	</div>
	<% end_loop %>
<% end_if %>