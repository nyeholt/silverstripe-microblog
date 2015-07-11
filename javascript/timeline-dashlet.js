;(function ($){
	$(function () {
		$('div.timeline-container').entwine({
			onmatch: function () {
				var container = this;
				var parentForm = $(container).parents('form');
				if (parentForm.length) {
					container.insertAfter(parentForm);
				}
				// load up the timeline
				var timeline = $(this).attr('data-url');
				$.get(timeline).success(function (data) {
					container.html(data);
				}).error(function (data) {
					alert("Timeline failed to load");
				});
			}
		})
	});
})(jQuery);