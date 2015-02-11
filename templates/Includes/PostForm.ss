<form $FormAttributes >
	<% with FieldMap %>

	<% if $Top.Options.UserTitle %>
	<label class="postform-label" for="Form_PostForm_Title">Title</label>
	$Title
	<% end_if %>
	<label class="postform-label" for="Form_PostForm_Content">Post content</label>
	$Content
	<input type="hidden" name="SecurityID" value="$SecurityID" />
	$Up.Actions
	<input type="button" name="uploadTrigger" value="Upload" />

	<% if $PostTarget %>
	<p>Include</p>
	$PostTarget
	<% end_if %>
	
	<% if $LoggedInUsers %>
	<h4>Post to</h4>
	
	$LoggedInUsers
	<label for="Form_PostForm_LoggedInUsers">Logged in users</label>
	
	<label for="s2id_Form_PostForm_Members">Specific users</label>
	$Members
	
	<label for="s2id_Form_PostForm_Groups">Groups</label>
	$Groups
	
	<% end_if %>

	<% end_with %>
</form>