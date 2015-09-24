			<div class="postOptions $UserVote">
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