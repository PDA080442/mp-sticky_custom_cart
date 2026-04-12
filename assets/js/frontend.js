/**
 * MP Sticky Custom Cart — storefront entry (data from {@see wp_localize_script} `mpSccData`).
 */
(function (window, $) {
	'use strict';

	window.mpScc = window.mpScc || {};

	var data = function () {
		return window.mpSccData || {};
	};

	/**
	 * @param {string} key Flag key from persisted flags / mpSccData.flags.
	 * @returns {boolean}
	 */
	window.mpScc.flagEnabled = function (key) {
		var f = data().flags;
		return !!(f && Object.prototype.hasOwnProperty.call(f, key) && f[key]);
	};

	/**
	 * Resolved UI label (aligned with PHP OptionResolver labels).
	 * @param {string} key
	 * @returns {string}
	 */
	window.mpScc.label = function (key) {
		var l = data().labels;
		return l && Object.prototype.hasOwnProperty.call(l, key) ? String(l[key]) : '';
	};

	/**
	 * Value of a runtime CSS variable as emitted for JS (same map as :root inline).
	 * @param {string} name Full name including `--`, e.g. `--mp-scc-sticky-z-index`.
	 * @returns {string}
	 */
	window.mpScc.cssVar = function (name) {
		var c = data().cssVars;
		return c && Object.prototype.hasOwnProperty.call(c, name) ? String(c[name]) : '';
	};

	/**
	 * admin-ajax.php URL, global nonce, and action names for jQuery.post / fetch.
	 * @returns {{ ajaxUrl: string, nonce: string, actions: Record<string, string> }}
	 */
	window.mpScc.ajaxConfig = function () {
		var d = data();
		return {
			ajaxUrl: d.ajaxUrl ? String(d.ajaxUrl) : '',
			nonce: d.nonce ? String(d.nonce) : '',
			actions: d.actions && typeof d.actions === 'object' ? d.actions : {}
		};
	};

	/**
	 * Whether Woo `wc-cart-fragments` was registered when the page was built (for branching).
	 * @returns {boolean}
	 */
	window.mpScc.hasWcCartFragments = function () {
		return !!data().wcCartFragments;
	};

	/**
	 * @param {string} actionKey Key under mpSccData.actions (e.g. `cartSnapshot`).
	 * @param {Record<string, *>} [payload]
	 */
	window.mpScc.postAjax = function (actionKey, payload) {
		var cfg = window.mpScc.ajaxConfig();
		var action = cfg.actions[actionKey];
		if (!cfg.ajaxUrl || !action) {
			return $.Deferred().reject().promise();
		}
		var body = $.extend({ action: action, _ajax_nonce: cfg.nonce }, payload || {});
		return $.post(cfg.ajaxUrl, body);
	};

	$(function () {
		/**
		 * Fires when the storefront script layer is ready (jQuery DOM ready).
		 */
		$(window.document).trigger('mpScc:ready');
	});
})(window, window.jQuery);
