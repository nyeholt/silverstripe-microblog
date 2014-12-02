
<input type="hidden" id="AddFriendLink" value="$Link(addfriend)" />
<% loop Items %>
<div class="friendsSearchResult">
	<a href="$Link">$FirstName $Surname</a> <input type="button" class="addFriendButton" data-id="$ID" value="Add" />
</div>
<% end_loop %>