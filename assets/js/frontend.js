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
})(window);
