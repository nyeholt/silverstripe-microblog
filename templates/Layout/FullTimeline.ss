
$Content

<div class="timeline-box">

	<input type="hidden" id="MemberDetails" data-member='$MemberDetails.ATT' />
	<input type="hidden" value="$PostForm.FormAction" id="PostFormUrl" />
	<input type="hidden" name="timelineUpdateUrl" value="$Link(flatlist)" />

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
		<div class="StatusFeed" class="autorefresh">
			$Timeline
		</div>
	<% else %>

		<% if $ContextUser %>
			<div class="postForm span8">
			<% with PostForm %>
			<% include PostForm %>
			<% end_with %>
			</div>
		<% end_if %>

		<div class="StatusFeed" class="autorefresh">
			$Timeline
			<div class="feed-actions">
				<a href="#" class="moreposts">Load more...</a>
			</div>
		</div>

	<% end_if %>
</div>