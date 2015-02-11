
$Content

<input type="hidden" id="MemberDetails" data-member='$MemberDetails.ATT' />
<input type="hidden" value="$PostForm.FormAction" id="PostFormUrl" />

<% if $ContextUser %>
	<div class="uploadForm">
		<% with $UploadForm %>
		<form $FormAttributes>
			<% with FieldMap %>
			<input type="hidden" name="SecurityID" value="$SecurityID" />
			$Attachment
			<% end_with %>
			<ul id="uploadedFiles"></ul>
		</form>
		<% end_with %>
	</div>
<% end_if %>

<input type="hidden" name="timelineUpdateUrl" value="$Link(flatlist)" />

<% if $Post %>
	<div id="StatusFeed" class="autorefresh">
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

	<div id="StatusFeed" class="autorefresh">
		$Timeline
		<div class="feed-actions">
			<a href="#" class="moreposts">Load more...</a>
		</div>
	</div>

<% end_if %>
