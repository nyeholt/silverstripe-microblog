<% if Deleted %>
	<% _t('MicroPost.DELETED', '[deleted]') %>
<% else %>

	
	<% if Content %>
		<% if IsOembed %>
		$Content.Raw
		<% else_if IsImage %>
		<img src="$Content" />
		<% else %>
		$ConvertedContent.Parse(RestrictedMarkdown)
		<% end_if %>
	<% else %>
		<% if OwnerID == $CurrentMember.ID %>
		<!-- <div class="edit-placeholder"><em>Click to update</em></div> -->
		<% end_if %>
	<% end_if %>

	<% if Attachment %> 
		Download original: <a href="$Attachment.getURL" title="Download attached file" class="force-link" target="_blank">$Attachment.Name</a>
	<% end_if %>

<% end_if %>