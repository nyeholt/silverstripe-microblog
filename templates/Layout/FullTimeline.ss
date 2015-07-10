
$Content

<div class="timeline-box">

	<input type="hidden" id="MemberDetails" data-member='$MemberDetails.ATT' />
	<input type="hidden" value="$PostForm.FormAction" id="PostFormUrl" />
	
	

	<% if $ContextUser %>
		<div class="uploadForm">
			<% with $UploadForm %>
			<form $FormAttributes>
				<% with FieldMap %>
				$Attachment
				<% end_with %>
				<ul id="uploadedFiles"></ul>
				$HiddenFields
			</form>
			<% end_with %>
		</div>
	<% end_if %>


	<% if $Post %>
		<input type="hidden" name="timelineUpdateUrl" value="$Link(flatlist)/$Post" />
	
		<div class="StatusFeed autorefresh">
			$Timeline
		</div>
	<% else %>

		<input type="hidden" name="timelineUpdateUrl" value="$Link(flatlist)" />
		
		<% if $ContextUser %>
			<div class="postForm span8">
			<% with PostForm %>
			<% include PostForm %>
			<% end_with %>
			</div>
		<% end_if %>

		<div class="StatusFeed autorefresh">
			$Timeline
			<div class="feed-actions">
				<a href="#" class="moreposts">Load more...</a>
			</div>
		</div>

	<% end_if %>
</div>