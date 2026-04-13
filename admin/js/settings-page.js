/**
 * Sticky Cart settings: unsaved changes warning (beforeunload) + Styles tab live preview.
 */
(function ($) {
	'use strict';

	function parsePreviewMeta(el) {
		var raw = el.getAttribute('data-mp-scc-preview');
		if (!raw) {
			return null;
		}
		try {
			return JSON.parse(raw);
		} catch (e) {
			return null;
		}
	}

	function buildStyleFromPreviewInputs(root) {
		var inputs = (root || document).querySelectorAll('[data-mp-scc-preview]');
		var parts = [];
		for (var i = 0; i < inputs.length; i++) {
			var el = inputs[i];
			var meta = parsePreviewMeta(el);
			if (!meta || !meta.var || !meta.fmt) {
				continue;
			}
			var v = el.value;
			if (meta.fmt === 'color') {
				if (!/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/.test(String(v).trim())) {
					continue;
				}
				parts.push(meta.var + ':' + String(v).trim());
				continue;
			}
			if (meta.fmt === 'alpha') {
				var a = parseFloat(v);
				if (isNaN(a)) {
					continue;
				}
				parts.push(meta.var + ':' + a);
				continue;
			}
			if (meta.fmt === 'unit') {
				var n = parseInt(v, 10);
				if (isNaN(n)) {
					continue;
				}
				if (meta.omit_if_zero && n <= 0) {
					continue;
				}
				parts.push(meta.var + ':' + n + (meta.suffix || ''));
				continue;
			}
			if (meta.fmt === 'float') {
				var f = parseFloat(v);
				if (isNaN(f)) {
					continue;
				}
				parts.push(meta.var + ':' + f);
				continue;
			}
			if (meta.fmt === 'integer') {
				var k = parseInt(v, 10);
				if (isNaN(k)) {
					continue;
				}
				parts.push(meta.var + ':' + k);
				continue;
			}
		}
		return parts.join('');
	}

	function initStyleLivePreview() {
		var cfg =
			typeof window.mpSccAdmin !== 'undefined' && window.mpSccAdmin.stylePreview
				? window.mpSccAdmin.stylePreview
				: {};
		var id = cfg.previewId || 'mp-scc-style-live-preview';
		var throttleMs = typeof cfg.throttleMs === 'number' ? cfg.throttleMs : 100;

		var preview = document.getElementById(id);
		if (!preview) {
			return;
		}

		var baseline = preview.getAttribute('data-mp-scc-preview-baseline') || '';
		var timer = null;

		function apply() {
			preview.style.cssText = buildStyleFromPreviewInputs(document);
		}

		function schedule() {
			if (timer) {
				clearTimeout(timer);
			}
			timer = setTimeout(function () {
				timer = null;
				apply();
			}, throttleMs);
		}

		$(document).on('input change', '[data-mp-scc-preview]', schedule);

		var $mobile = $('#mp-scc-style-preview-mobile');
		if ($mobile.length) {
			$mobile.on('click', function () {
				var on = preview.classList.toggle('mp-scc-admin-sticky-preview--mobile');
				$mobile.attr('aria-pressed', on ? 'true' : 'false');
			});
		}

		var $revert = $('#mp-scc-style-preview-revert');
		if ($revert.length) {
			$revert.on('click', function () {
				preview.style.cssText = baseline;
			});
		}
	}

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

		initStyleLivePreview();
	});
})(window.jQuery);
