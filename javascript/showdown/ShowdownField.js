Behaviour.register({
	'#Form_EditForm' : {
		initialize : function() {
			this.observeMethod('PageLoaded', this.adminPageHandler);
			this.adminPageHandler();
		},
		adminPageHandler : function() {
			// Place your custom code here.
			(function($){
				 // build the converter
				 var converter = new Showdown.converter();
				 var UPDATE_INTERVAL = 150; // msec

				 var timerID = 0;
				 var previewFunction = function(e, elt) {
					 // console.log('clearing tid=', timerID);
					 clearTimeout(timerID);
				 	 timerID = setTimeout(function() {
						 //console.log('running tid=', timerID);
						 //var prev = $(elt).siblings('iframe.preview');
						 var prev = $('div.preview', $(elt).parents('div.showdown'));
						 // console.log(e, 'prev=', prev, 'conv=',converter);
						 var text = elt.value;
						 text = converter.makeHtml(text);
						 // console.log('prev=', prev, 'text=', text);
						 // prev = $(prev[0].contentDocument.body);
						 //console.log(text);
						 prev.children('div.content').html(text);
						 //prev.resizable({handles: 's'});
					 }, UPDATE_INTERVAL);
				 }
				 var prev = $('div.showdown div.preview');
				 //$('.showdown textarea').resizable({handles: 's'});
				 $('.showdown textarea').keyup(function(e) { return previewFunction(e, this); });
				 $('.showdown textarea').keyup();
			 })(jQuery);
		}
	}
});
