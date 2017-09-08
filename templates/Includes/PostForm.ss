<form $FormAttributes >
	$HiddenFields
	<% with FieldMap %>

	<% if $Title %>
	<label class="postform-label" for="Form_PostForm_Title">Title</label>
	$Title
	<% end_if %>
	<label class="postform-label" for="Form_PostForm_Content">Post content</label>
	$Content
	
	$Up.Actions
	
	<% if $Options.EnableUploads %>
	<button type="button" class="upload-trigger" name="uploadTrigger">Attach files</button>
	<% end_if %>

	<% if $LoggedInUsers %>
	
	<button type="button" class="specific-users">Post to</button>
	<div class="post-specific-users" style="display:none">
		$DisableReplies
		<label for="Form_PostForm_DisableReplies">$DisableReplies.Title</label>
		<h4>Post to</h4>
		<% if $PublicUsers %>
		$PublicUsers
		<label for="Form_PostForm_PublicUsers">$PublicUsers.Title</label>
		<% end_if %>
		
		<% if $LoggedInUsers %>
		$LoggedInUsers
		<label for="Form_PostForm_LoggedInUsers">$LoggedInUsers.Title</label>
		<% end_if %>
	
		<% if $Members %>
		<label for="s2id_Form_PostForm_Members">Specific users</label>
		$Members
		<% end_if %>
		
		<% if $Groups %>
		<label for="s2id_Form_PostForm_Groups">Groups</label>
		$Groups
		<% end_if %>
		
	</div>
	<% end_if %>

	<% end_with %>
</form>