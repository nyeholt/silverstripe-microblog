
$Content

<div class="timeline-box">

	<input type="hidden" id="MemberDetails" data-member='$MemberDetails.ATT' />
	<input type="hidden" value="$PostForm.FormAction" id="PostFormUrl" />
	
	<% if $Post %>
		<input type="hidden" name="timelineUpdateUrl" value="$Link(flatlist)/$Post" />
	
		<div class="StatusFeed autorefresh">
			$Timeline
		</div>
	<% else %>

		<input type="hidden" name="timelineUpdateUrl" value="$Link(flatlist)" />
        
		<% if $ContextUser.ID > 0 %>
			<% if $Options.ShowPostForm %>
            <div class="postForm span8">
			<% with PostForm %>
			<% include PostForm Options=$Top.Options %>
			<% end_with %>
			</div>
            <% end_if %>
		
			<% if $Top.Options.EnableUploads %>
			<% with UploadForm %>
			<form $FormAttributes >
			$HiddenFields
			<% with FieldMap %>
			$Attachment.FieldHolder
			<% end_with %>
			</form>
			<% end_with %>
			<% end_if %>
	
		<% end_if %>

		<div class="StatusFeed autorefresh">
			$Timeline
			<div class="feed-actions">
				<a href="#" class="moreposts">Load more...</a>
			</div>
		</div>

	<% end_if %>
</div>