<% if CurrentMember %>
	<input type="hidden" value="$Owner.Profile.ID" name="MemberID" />
	<% if $CurrentMember.ID != $OwnerID %>
		<!-- add the user we're looking at as a friend -->
		
	<% else %>
		$FriendSearchForm
		
		<div class="friendsSearchList">
			
		</div>
	<% end_if %>
	
	<!-- list of this user's friends -->
	<% loop Owner.Friends %>
	<div class="userFriend">
		<a class="deleteFriend ui-icon ui-icon-close" data-id="$ID">remove</a>
		<a href="$Other.Link">
			<img src="http://www.gravatar.com/avatar/{$Other.gravatarHash}.jpg?s=24" />
			$Other.Username 
		</a>
	</div>
	<% end_loop %>
<% else %>
	Please login
<% end_if %>
