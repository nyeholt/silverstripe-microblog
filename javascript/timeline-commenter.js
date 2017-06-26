;(function ($){
	
	var checkCommentCount = function (trigger) {
		var props = {};
		var endpoint = 'jsonservice/microBlog/unreadPosts';
		
		if (trigger.attr('data-target')) {
			props.filter = {'Target': trigger.attr('data-target')};
			endpoint = 'jsonservice/microBlog/globalFeed';
		} 
		
		$.get(endpoint, props).success(function (posts) {
			if (posts.response && posts.response.items) {
				var count = posts.response.items.length;
				if (count > 0) {
					var counter = trigger.find('.comment-count');
					counter.removeClass('comment-count-0').text(posts.response.items.length);
				}
			}
		});
	};
	
	$(function () {
		$.entwine('microblog', function ($) {
			$('a.comment-list-trigger').entwine({
				onmatch: function () {
					checkCommentCount(this);
					var _this = this;

					setInterval(function () {
						checkCommentCount(_this);
					}, 60*1000);
				}
			})
		});

		$(document).on('click', 'a.comment-list-trigger', function (e) {
			e.preventDefault();

			var me = $(this);
			setTimeout(function () {
				var counter = me.find('.comment-count');
				counter.addClass('comment-count-0').text('0');
			}, 1000);

			var url = $(this).attr('href');

			if (e.shiftKey) {
				

				var width = $(window).width() / 3;
				var height = $(window).height();

				var options = 
					"all=no," +
					"titlebar=no," + 
					"scrollbars=yes," + 
					"chrome=yes," +
					"toolbar=no," +
					"dialog=no," +
					"resizable=yes," + 
					"modal=no," +
					"dependent=yes," +
					"width="+width+"px," +
					"height="+height+"px";

				var sep = '?'; 
				if (url.indexOf('?') > 0) {
					sep = '&';
				}
				url += sep + 'popup=1';
				var comments = window.open(url, 'comments', options);
				comments.focus();
			} else {
				var foundationModal = $('#comments-modal-foundation');
				if (foundationModal.length > 0) {
					foundationModal.foundation('reveal', 'open', {
						url: url,
						dataFilter: function(data, type){
							data = '<a class="close-reveal-modal">&times;</a><div class="modal-heading">Comments</div>' + data;
							return data;
						}
					});
				}
				
				var jqueryModal = $('#comments-modal-ui');
				if (jqueryModal.length > 0) {
					alert("Not implemented yet! Sorry... :(");
				}
			}

			return false;
		});
		
		
	});
})(jQuery);
