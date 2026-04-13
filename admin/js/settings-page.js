/**
 * Sticky Cart settings: unsaved changes warning (beforeunload).
 */
(function ($) {
	'use strict';

	$(function () {
		var $form = $('.mp-scc-settings .mp-scc-settings-form');
		if (!$form.length) {
			return;
		}

		var initial = $form.serialize();

		function markDirty() {
			var dirty = $form.serialize() !== initial;
			$form.toggleClass('mp-scc-form-dirty', dirty);
		}

		$form.on('change input', ':input', function () {
			markDirty();
		});

		$form.on('submit', function () {
			$(window).off('beforeunload.mpSccAdmin');
		});

		$(window).on('beforeunload.mpSccAdmin', function (e) {
			if (!$form.hasClass('mp-scc-form-dirty')) {
				return;
			}
			var msg =
				typeof window.mpSccAdmin !== 'undefined' && window.mpSccAdmin.beforeUnload
					? String(window.mpSccAdmin.beforeUnload)
					: '';
			if (!msg) {
				return;
			}
			e.preventDefault();
			e.returnValue = msg;
			return msg;
		});
	});
})(window.jQuery);
