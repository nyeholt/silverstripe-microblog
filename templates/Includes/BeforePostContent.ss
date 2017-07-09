			<div class="postOptions $UserVote">
				<% if $TimelineOptions.Voting %>
                <% if $ContextUser.ID %>
				<a href="#" class="vote <% if $UserVote == 'downvote' %>not-voted<% end_if %><% if $UserVote == 'upvote' %>voted<% end_if %>" data-dir="1" data-id="$ID">Up</a>
				<a href="#" class="vote <% if $UserVote == 'downvote' %>voted<% end_if %><% if $UserVote == 'upvote' %>not-voted<% end_if %>" data-dir="-1" data-id="$ID">Down</a>
                <% end_if %>
				<span class="upCount">$Up</span><span class='vote-separator'>|</span><span class="downCount">$Down</span>
				<% end_if %>

				<% if $ParentID == 0 %>
				<a href="$Link">permalink</a>
				&middot;
				<% end_if %>

				<% if not $TimelineOptions.ShowReply %>
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
					<a href="#" class="hidePost">hide</a>
					&middot;
					<a href="#" class="deletePost">delete</a>
					<% end_if %>
				<% end_if %>
			</div>