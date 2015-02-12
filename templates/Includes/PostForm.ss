<form $FormAttributes >
	$HiddenFields
	<% with FieldMap %>

	<% if $Top.Options.UserTitle %>
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
		$LoggedInUsers
		<label for="Form_PostForm_LoggedInUsers">Logged in users</label>
	
		<label for="s2id_Form_PostForm_Members">Specific users</label>
		$Members
	
		<label for="s2id_Form_PostForm_Groups">Groups</label>
		$Groups
	</div>
	<% end_if %>

	<% end_with %>
</form>