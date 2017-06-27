<% if Deleted %>
	<% _t('MicroPost.DELETED', '[deleted]') %>
<% else %>

	
	<% if Content %>
		<% if IsOembed %>
		$Content.Raw
		<% else_if IsImage %>
		<img src="$Content" />
		<% else %>
        <div class="js-convert-markdown">$ConvertedContent.Parse(PostFormatter)</div>
		<% end_if %>
	<% else %>
		<% if OwnerID == $CurrentMember.ID %>
		<!-- <div class="edit-placeholder"><em>Click to update</em></div> -->
		<% end_if %>
	<% end_if %>

	<% if Attachment %> 
		Download original: <a href="$Attachment.getURL" title="Download attached file" class="force-link" target="_blank">$Attachment.Name</a>
	<% end_if %>

	
	<% if not $ParentID && $PostTarget.Link %>
	<span class="post-target-link" style="display: none">
	Posted on: <a href="$PostTarget.Link">$PostTarget.Title</a>
	</span>
	<% end_if %>
<% end_if %>