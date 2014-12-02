
;(function ($) {
	$.entwine('microblog', function ($) {
		$('#Form_FriendSearchForm').entwine({
			onmatch: function () {
				this.ajaxForm(function (data) {
					$('.friendsSearchList').empty();
					$('.friendsSearchList').append(data);
					return false;
				})
			}
		})

		$('input.addFriendButton').entwine({
			onclick: function () {
				var params = {
					'memberType': 'PublicProfile',
					'memberID': $(this).parents('div.FriendsDashlet').find('input[name=MemberID]').val(),
					'followedType': 'PublicProfile',
					'followedID': $(this).attr('data-id')
				};

				var _this = this;
				SSWebServices.post('microBlog', 'addFriendship', params, function (data) {
					_this.parents('div.dashlet').refresh();
					if ($('div.TimelineDashlet').length) {
						$('div.TimelineDashlet').refresh();
					}
				})
			}
		})
		
		$('a.deleteFriend').entwine({
			onclick: function () {
				var params = {
					'relationshipType':		'Friendship',
					'relationshipID':		$(this).attr('data-id')
				};
				
				var _this = $(this);
				SSWebServices.post('microBlog', 'removeFriendship', params, function (data) {
					_this.parent().fadeOut();
					if ($('div.TimelineDashlet').length) {
						$('div.TimelineDashlet').refresh();
					}
				})
			}
		})
	})
	
})(jQuery);