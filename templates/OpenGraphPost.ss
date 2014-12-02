
<div class="openGraphPost">
	<% if image %>
	<a href="$url" target="_blank" class="openGraphImage">
	<img src="$image" />
	</a>
	<% end_if %>
	
	<div class="openGraphPostContent">
		<% if title %>
		<p class="ogTitle"><a href="$url" target="_blank">$title</a></p>
		<% end_if %>

		<% if description %>
		<p>$description</p>
		<% end_if %>
	</div>
</div>