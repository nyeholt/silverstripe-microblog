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
	<input type="button" name="uploadTrigger" value="Upload" />

	<% if $LoggedInUsers %>
	
	<input type="button" class="specific-users" value="Post to" />
	<div class="post-specific-users" style="display:none">
		<% if $PublicUsers %>
		$PublicUsers
		<label for="Form_PostForm_PublicUsers">Public users</label>
		<% end_if %>
		
		<% if $LoggedInUsers %>
		$LoggedInUsers
		<label for="Form_PostForm_LoggedInUsers">Logged in users</label>
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