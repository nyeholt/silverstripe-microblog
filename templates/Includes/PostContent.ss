<% if Deleted %>
	<% _t('MicroPost.DELETED', '[deleted]') %>
<% else %>

	<% if Attachment %> 
		<% if $Attachment.ClassName == 'Image' || $Attachment.ClassName == 'CdnImage' %>
			<a href="$Attachment.getURL" target="_blank" title="Download image">$Attachment.MaxWidth(1024)</a>
		<% else %>
		<a href="$Attachment.getURL" title="Download attached file">$Attachment.Title</a>
		<% end_if %>
	<% end_if %>
	<% if Content %>
		<% if IsOembed %>
		$Content.Raw
		<% else_if IsImage %>
		<img src="$Content" />
		<% else %>
		$Content.Parse(RestrictedMarkdown)
		<% end_if %>
	<% else %>
		<% if OwnerID == $CurrentMember.ID %>
		<!-- <div class="edit-placeholder"><em>Click to update</em></div> -->
		<% end_if %>
	<% end_if %>

<% end_if %>