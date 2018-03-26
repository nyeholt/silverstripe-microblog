<style type="text/css">
	.post-target-link {display: none; }
</style>
<table  style="font-family: Arial; font-size: 12px">
    <tr style="padding-bottom: 10px;">
		<td width="140px" style="min-width: 140px">&nbsp;
		</td>
		<td width="" style="min-width: 400px;">
		</td>
		<td>
		</td>
	</tr>
	

	<tr style="padding-bottom: 10px;">
		<td colspan="3">
			<p>
			Hi $Member.FirstName,
			</p>
			
			<p>
			Here's the latest updates from your network
			</p>
			
			<br/><br/>
			
		</td>
	</tr>
	
	
	<% loop $Posts %>
	<% if PostType == 'notice-post' %>
	<tr style="padding-bottom: 10px;">
        <td></td>
		<td valign="top" colspan="2">
			$ConvertedContent.parse(RestrictedMarkdown).RAW
			<br/>
		</td>
	</tr>
	<% else %>
	<tr style="padding-bottom: 10px;">
		<td valign="top">
			<p>
			<em>
			<% if $Owner.ID == $Top.Member.ID %>
			Me
			<% else %>
			$Owner.FirstName
			<% end_if %>
			</em>
                <br/>
			at $Created.Nice
			</p>
		</td>
		<td>
			
			$RenderedContent.raw

			<p>
				<a href="$Link">View online</a><% if $NumReplies %>, with $NumReplies replies<% end_if %>
			</p>
			<br/>
			<br/>
		</td>
		<td>
		</td>
	</tr>
	<% end_if %>
	<% end_loop %>
</table>

<p style="font-family: Arial; font-size: 12px">
	To stop receiving these emails, please <a href="?">login</a> and change your digest settings.
</p>