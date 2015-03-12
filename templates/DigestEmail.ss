<p
	Hi $Member.Title
</p>

<p>
The following posts have been made in your network 
</p>

<% loop $Posts %>
<% if Title %>
<h3>$Title</h3>
<% end_if %>
<em> by 
<% if $Owner.ID == $Top.Member.ID %>
Me
<% else %>
$Owner.FirstName
<% end_if %>
</em>
<br/>
$RenderedContent.raw

<p>
	<a href="$Link">View online</a>
</p>

<hr>

<% end_loop %>