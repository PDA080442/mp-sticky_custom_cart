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

	function qsByName(name) {
		if (!name) {
			return null;
		}
		return document.querySelector('[name="' + String(name).replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"]');
	}

	function resolveFontFamilyForPreview(meta) {
		var presetEl = meta.presetName ? qsByName(meta.presetName) : null;
		var customEl = meta.customName ? qsByName(meta.customName) : null;
		var preset = presetEl ? String(presetEl.value || 'inherit') : 'inherit';
		var custom = customEl ? String(customEl.value || '').trim() : '';

		if (preset === 'custom') {
			return custom !== '' ? custom : 'inherit';
		}
		switch (preset) {
			case 'system':
				return 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
			case 'serif':
				return 'Georgia, "Times New Roman", Times, serif';
			case 'mono':
				return 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace';
			case 'inherit':
			default:
				return 'inherit';
		}
	}

	function buildStyleFromPreviewInputs(root) {
		var inputs = (root || document).querySelectorAll('[data-mp-scc-preview]');
		var parts = [];
		var i;
		var seenFont = false;
		for (i = 0; i < inputs.length; i++) {
			var el = inputs[i];
			var meta = parsePreviewMeta(el);
			if (!meta || !meta.fmt) {
				continue;
			}

			if (meta.fmt === 'font_family') {
				if (seenFont) {
					continue;
				}
				seenFont = true;
				var ff = resolveFontFamilyForPreview(meta);
				if (meta.var) {
					parts.push(meta.var + ':' + ff);
				}
				continue;
			}

			if (meta.fmt === 'typography_scale') {
				var scale = parseInt(el.value, 10);
				if (isNaN(scale)) {
					scale = 100;
				}
				scale = Math.max(70, Math.min(130, scale));
				var sumEl = meta.summaryField ? qsByName(meta.summaryField) : null;
				var btnEl = meta.buttonField ? qsByName(meta.buttonField) : null;
				var sumPx = sumEl ? parseInt(sumEl.value, 10) : 15;
				var btnPx = btnEl ? parseInt(btnEl.value, 10) : 14;
				if (isNaN(sumPx)) {
					sumPx = 15;
				}
				if (isNaN(btnPx)) {
					btnPx = 14;
				}
				var sPx = Math.max(8, Math.round((sumPx * scale) / 100));
				var bPx = Math.max(8, Math.round((btnPx * scale) / 100));
				if (meta.varSummary) {
					parts.push(meta.varSummary + ':' + sPx + 'px');
				}
				if (meta.varButton) {
					parts.push(meta.varButton + ':' + bPx + 'px');
				}
				continue;
			}

			if (!meta.var) {
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
		$(document).on('input change', '.mp-scc-font-family-custom', schedule);

		$('.mp-scc-color').each(function () {
			var $inp = $(this);
			$inp.wpColorPicker({
				change: function () {
					schedule();
				},
				clear: function () {
					schedule();
				},
			});
		});

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
		initHelpTipPopovers();
	});

	/**
	 * Click/tap opens help text in a popover (native title= is hover-only and poor on touch).
	 */
	function initHelpTipPopovers() {
		var $pop = null;
		var anchorEl = null;

		function ensurePopover() {
			if (!$pop || !$pop.length) {
				$pop = $(
					'<div id="mp-scc-help-popover" class="mp-scc-help-popover" role="tooltip" hidden></div>'
				);
				$('body').append($pop);
				$pop.on('click', function (e) {
					e.stopPropagation();
				});
			}
			return $pop;
		}

		function close() {
			anchorEl = null;
			if ($pop && $pop.length) {
				$pop.prop('hidden', true).removeClass('is-open').empty();
			}
		}

		function position($btn) {
			var el = $btn[0];
			var r = el.getBoundingClientRect();
			var $p = ensurePopover();
			var pad = 8;
			var top = r.bottom + window.scrollY + 6;
			var left = r.left + window.scrollX;
			$p.css({ top: top, left: left });
			var w = $p.outerWidth();
			var maxLeft = window.scrollX + window.innerWidth - w - pad;
			if (left > maxLeft) {
				$p.css('left', Math.max(pad + window.scrollX, maxLeft));
			}
		}

		$(document).on('click', '.mp-scc-settings .mp-scc-help-tip', function (e) {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			var $btn = $(this);
			var txt = ($btn.attr('data-mp-scc-help') || $btn.attr('aria-label') || '').trim();
			if (!txt) {
				return;
			}
			var $p = ensurePopover();
			if (anchorEl === $btn[0] && $p.hasClass('is-open')) {
				close();
				return;
			}
			anchorEl = $btn[0];
			$p.text(txt).prop('hidden', false).addClass('is-open');
			position($btn);
		});

		$(document).on('click', function () {
			close();
		});

		$(document).on('keydown', function (e) {
			if (e.key === 'Escape' || e.keyCode === 27) {
				close();
			}
		});

		$(window).on('resize scroll', function () {
			if (!anchorEl || !$pop || !$pop.length || !$pop.hasClass('is-open')) {
				return;
			}
			position($(anchorEl));
		});
	}
})(window.jQuery);
