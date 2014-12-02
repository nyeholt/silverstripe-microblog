
<% if Posts %>
<% loop Posts %>
<div class="microPost <% if $ParentID > 0 %><% else %>toplevel<% end_if %>" data-id="$ID" data-owner="$Owner.ID" data-parent="$ParentID" id="post$ID" data-rating="$WilsonRating" data-sortby="$Top.SortBy" data-editable="1">
		<div class="postImage">
			<a href="$Owner.Link"><img src="http://www.gravatar.com/avatar/{$Owner.gravatarHash}.jpg?s=40" /></a>
		</div>
		
		<div class="microPostContent">
			<div class="postText">
			<% include PostContent %>
			</div>
			
			<p class="postOptions">
				<span class="upCount">$Up</span>|<span class="downCount">$Down</span>
				<a href="#" class="vote" data-dir="1" data-id="$ID">Up</a>
				<a href="#" class="vote" data-dir="-1" data-id="$ID">Down</a>
				&middot;
				<% if Top.ShowReplies %>
					<a href="$Link">permalink</a>
					&middot;
					<a href="#" class="replyToPost">reply</a>
					<% if Deleted %>
					<% else %>
						<% if checkPerm('Delete') %>
						&middot;
						<a href="#" class="deletePost">delete</a>
						<% end_if %>
					<% end_if %>
				<% else %>
				<a href="$Link">$NumReplies replies</a>
				<% end_if %>
				
				<abbr class="timeago postTime" title="$Created" data-created="$Created">$Created.Nice</abbr> 
				<% if $isEdited %><span class="edited-mark" title="Edited at $LastEdited">*</span><% end_if %>
				
				by 
				
				<% if $Owner.ID == $CurrentMember.ID %>
				Me
				<% else %>
				<a href="$Owner.Link">$Owner.Username</a>
				<% end_if %>
			</p>
			<!-- note that the action is left blank and filled in with JS because otherwise the
				recursive template loses context of what to fill in, so we use our top level form -->
			<form method="POST" action="" class="replyForm">
				<input type="hidden" value="$SecurityID" name="SecurityID" />
				<input type="hidden" name="ParentID" value="$ID" />
				<textarea placeholder="Add reply..." name="Content" class="expandable postContent preview"></textarea>
				<input type="submit" value="Reply" name="action_savepost" />
			</form>
			
			<div class="postReplies">
				<% if Top.ShowReplies %>
				<% if Posts %>
				<% include Timeline ShowReplies=$Top.ShowReplies %>
				<% end_if %>
				<% end_if %>
			</div>
		</div>
	</div>
	<% end_loop %>
<% end_if %>