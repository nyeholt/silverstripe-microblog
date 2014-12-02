
window.Microblog = window.Microblog || {};
Microblog.Member = {};

;(function($) {
	
	Microblog.log = function (msg) {
		if (console && console.log) {
			msg = Date.now().toString('yyyy-MM-dd HH:mm:ss') + ': ' + msg;
			console.log(msg);
		}
	}

	Microblog.track = function (category, action, label, value) {
		if (typeof(_gaq) != 'undefined') {
			_gaq.push(['_trackEvent', category, action, label, parseInt(value)]);
		}
	}

	$(function () {
		$('input#MemberDetails').entwine({
			onmatch: function () {
				var member = $('#MemberDetails').data('member');
				if (member) {
					Microblog.Member = member;
				}
			}
		})

		if ($('input[name=Search]').length) {
			var curVal = $('input[name=Search]').val();
			$('input[name=Search]').focus(function () {
				if ($(this).val() == curVal) {
					$(this).val('');
				}
			})
		}

		$('form.ajaxsubmitted').entwine({
			onmatch: function () {
				$(this).ajaxForm({
					success: function (data, status, jqxhr, form) {
						form.html($(data).html());
						form.find('input[type=submit]').effect('highlight')
					}
				});
			}
		})

		$('form.dashletreload').entwine({
			onmatch: function () {
				$(this).ajaxForm({
					success: function (data, status, jqxhr, form) {
						$(form).parents('div.dashlet').refresh();
					}
				})
			}
		})
	})
})(jQuery);