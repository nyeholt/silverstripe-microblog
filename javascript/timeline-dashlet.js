;(function ($){
	$(function () {
		$('div.timeline-container').entwine({
			onmatch: function () {
				var container = this;
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