<% if Posts %>

<% if $QueryOffset %>
<input type="hidden" value="$QueryOffset.ATT" name="postOffset" />
<% end_if %>
<% if SortBy %>
<input type="hidden" value="$SortBy.ATT" name="timelineSort" />
<% end_if %>

<% loop Posts %>
	<div class="microPost <% if $ParentID > 0 %>hasparent<% else %>toplevel<% end_if %> <% if $isUnreadByUser %>unread<% end_if %>" 
		 data-id="$ID" data-owner="$Owner.ID" data-parent="$ParentID" id="post$ID" data-rating="$WilsonRating" data-editable="1">
		<div class="microPostContent">
			<div class="postOptions">
				<% if $Top.Options.Voting %>
				<a href="#" class="vote" data-dir="1" data-id="$ID">Up</a>
				<a href="#" class="vote" data-dir="-1" data-id="$ID">Down</a>
				<span class="upCount">$Up</span><span class='vote-separator'>|</span><span class="downCount">$Down</span>
				<% end_if %>

				<% if $ParentID == 0 %>
				<a href="$Link">permalink</a>
				&middot;
				<% end_if %>

				<% if not $Top.Options.ShowReply %>
				<a href="#" class="replyToPost">reply</a>
				&middot;
				<% end_if %>

				<abbr class="timeago postTime" title="$Created" data-created="$Created">$Created.Nice</abbr> 
				<% if $isEdited %><span class="edited-mark" title="Edited at $LastEdited">*</span><% end_if %>
				
				by 
				
				<% if $Owner.ID == $ContextUser.ID %>
				Me
				<% else %>
				$Owner.FirstName
				<!--<a href="$Owner.Link">$Owner.FirstName</a>-->
				<% end_if %>
				<% if Deleted %>
				<% else %>
					<% if checkPerm('Delete') %>
					&middot;
					<a href="#" class="deletePost">delete</a>
					<% end_if %>
				<% end_if %>
			</div>
			
			<div class="postText">
			<% include PostContent %>
			</div>
			
			<% if $ParentID == 0 || $Top.Options.Threaded %>
			<!-- note that the action is left blank and filled in with JS because otherwise the
				recursive template loses context of what to fill in, so we use our top level form -->
			<form method="POST" action="" class="replyForm <% if not $Top.Options.ShowReply %>hiddenreplies<% end_if %>" >
				<input type="hidden" value="$SecurityID" name="SecurityID" />
				<input type="hidden" name="ParentID" value="$ID" />
				<textarea placeholder="Add reply..." name="Content" class="expandable postContent"></textarea>
				<input type="submit" value="Reply" name="action_savepost" />
			</form>
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