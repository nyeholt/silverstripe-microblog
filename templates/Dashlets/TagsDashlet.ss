
<% if Tags %>
<ul class="post-tag-list">
<% loop Tags %>
<li><a href="$Link">$Title</a> ($Number)</li>
<% end_loop %>
</ul>
<% end_if %>