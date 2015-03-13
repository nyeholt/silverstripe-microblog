<table  style="font-family: Arial; font-size: 12px">
	<tr style="padding-bottom: 10px;">
		<td colspan="3">
			<p>
			Hi $Member.FirstName,
			</p>
			
			<p>
			The following posts have been made in your network 
			</p>
			
			<br/><br/>
			
		</td>
	</tr>
	
	<tr style="padding-bottom: 10px;">
		<td>
		</td>
		<td width="60%">
			
		</td>
		<td>
		</td>
	</tr>
	

	<% loop $Posts %>
	<tr style="padding-bottom: 10px;">
		<td valign="top">
			<div class="gravatarImage">
				<img src="http://www.gravatar.com/avatar/{$Owner.gravatarHash}.jpg" />
			</div>
			<em>
			<% if $Owner.ID == $Top.Member.ID %>
			Me
			<% else %>
			$Owner.FirstName
			<% end_if %>
			</em>
		</td>
		<td>
			<% if Title %>
			<h3  style="font-family: Arial; font-size: 16px">$Title</h3>
			<% end_if %>
			
			$RenderedContent.raw

			<p>
				<a href="$Link">View online</a>
			</p>
			<br/>
			<br/>
		</td>
		<td>
		</td>
	</tr>
	<% end_loop %>
</table>

<p style="font-family: Arial; font-size: 12px">
	To stop receiving these emails, please <a href="?">login</a> and change your digest settings.
</p>