/**
 * MP Sticky Custom Cart — storefront entry (extended in later tasks).
 */
(function (window) {
	'use strict';

	window.mpScc = window.mpScc || {};

	/**
	 * @param {string} key Flag key from FeatureFlagsDefaults / mpSccData.flags.
	 * @returns {boolean}
	 */
	window.mpScc.flagEnabled = function (key) {
		var f = window.mpSccData && window.mpSccData.flags;
		return !!(f && Object.prototype.hasOwnProperty.call(f, key) && f[key]);
	};

	/**
	 * Resolved UI label (same as PHP {@see OptionResolver::get_label}).
	 * @param {string} key
	 * @returns {string}
	 */
	window.mpScc.label = function (key) {
		var l = window.mpSccData && window.mpSccData.labels;
		return l && Object.prototype.hasOwnProperty.call(l, key) ? String(l[key]) : '';
	};
})(window);
