
;(function ($) {
	
	var KEY = 'saved_state';
	
	var num = 1;
	
	$(function () {
		$.entwine('microblog', function ($) {
			
			$('input[name=action_savepost], input[type=button].postEditorField').entwine({
				onmatch: function () {
					$(this).click(function () {
						localStorage.removeItem(KEY);
					})
				}
			})

			$('.postContent:not(.postEditorField)').entwine({
				onmatch: function () {
					var _this = this;
						// explicit focus bind because focusin gets called twice...!
						$(this).focus(function () {
							var current = localStorage.getItem(KEY);
							if (!current || $(this).val().length) {
								return;
							}
							if (current == $(this).val()) {
								return;
							}
							$(this).val(current);
							localStorage.removeItem(KEY);
						})
					
					this._super();
				},
				onkeyup: function () {
					localStorage.setItem(KEY, $(this).val());
				}
			});

		});
	})
})(jQuery);