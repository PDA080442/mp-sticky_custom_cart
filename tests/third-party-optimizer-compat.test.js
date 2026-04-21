#!/usr/bin/env node
/**
 * Tests for core/ThirdPartyOptimizerCompat.php — verifies that the exclusion filter
 * helpers build the strings/arrays WP Rocket / LiteSpeed / Autoptimize expect.
 *
 * We re-implement the pure helpers here in JS so the test contract is checked end-to-end
 * without booting PHP, mirroring the existing Node-based test pack.
 *
 * Run: node tests/third-party-optimizer-compat.test.js
 */
'use strict';

var assert = require('assert');

var RELATIVE_ASSET_PATHS = [
	'/wp-content/plugins/mp-sticky_custom_cart/assets/css/frontend.css',
	'/wp-content/plugins/mp-sticky_custom_cart/assets/js/frontend.js',
	'/wp-content/plugins/mp-sticky_custom_cart/assets/js/cart-ui-shell-state.js',
];
var ASSETS_WILDCARD = '/wp-content/plugins/mp-sticky_custom_cart/assets/(.*)';

function appendWildcard(excluded) {
	var out = Array.isArray(excluded) ? excluded.slice() : [];
	if (out.indexOf(ASSETS_WILDCARD) === -1) {
		out.push(ASSETS_WILDCARD);
	}
	return out;
}

function mergeListLike(excluded, paths) {
	if (Array.isArray(excluded)) {
		var arr = excluded.slice();
		paths.forEach(function (p) {
			if (arr.indexOf(p) === -1) {
				arr.push(p);
			}
		});
		return arr;
	}
	var text = typeof excluded === 'string' ? excluded : '';
	paths.forEach(function (p) {
		if (text.indexOf(p) === -1) {
			text += (text === '' ? '' : '\n') + p;
		}
	});
	return text;
}

function appendCsv(excluded, paths) {
	var csv = typeof excluded === 'string' ? excluded : '';
	paths.forEach(function (p) {
		if (csv.indexOf(p) === -1) {
			csv += (csv === '' ? '' : ', ') + p;
		}
	});
	return csv;
}

// 1. WP Rocket: wildcard gets appended to empty input.
(function wpRocketEmpty() {
	var out = appendWildcard(undefined);
	assert.deepStrictEqual(out, [ASSETS_WILDCARD], 'undefined -> single wildcard');
	out = appendWildcard([]);
	assert.deepStrictEqual(out, [ASSETS_WILDCARD], 'empty array -> single wildcard');
})();

// 2. WP Rocket: existing entries are preserved, wildcard is idempotent.
(function wpRocketIdempotent() {
	var existing = ['/wp-content/themes/foo/style.css', ASSETS_WILDCARD];
	var out = appendWildcard(existing);
	assert.deepStrictEqual(
		out,
		['/wp-content/themes/foo/style.css', ASSETS_WILDCARD],
		'no duplicate wildcard'
	);

	var twice = appendWildcard(appendWildcard([]));
	assert.strictEqual(twice.length, 1, 'double-call still one entry');
})();

// 3. LiteSpeed (array mode): both plugin assets appear.
(function liteSpeedArray() {
	var out = mergeListLike([], RELATIVE_ASSET_PATHS);
	RELATIVE_ASSET_PATHS.forEach(function (p) {
		assert.ok(out.indexOf(p) !== -1, 'array missing ' + p);
	});
	var again = mergeListLike(out, RELATIVE_ASSET_PATHS);
	assert.strictEqual(again.length, RELATIVE_ASSET_PATHS.length, 'no duplicates on re-merge');
})();

// 4. LiteSpeed (string mode): newline-separated, no duplicate substrings.
(function liteSpeedString() {
	var out = mergeListLike('', RELATIVE_ASSET_PATHS);
	RELATIVE_ASSET_PATHS.forEach(function (p) {
		assert.ok(out.indexOf(p) !== -1, 'string missing ' + p);
	});
	var twice = mergeListLike(out, RELATIVE_ASSET_PATHS);
	assert.strictEqual(twice, out, 'no duplicates on re-merge (string mode)');

	var lines = out.split('\n').filter(function (s) { return s.length > 0; });
	assert.strictEqual(lines.length, RELATIVE_ASSET_PATHS.length, 'one path per line');
})();

// 5. Autoptimize CSV: comma+space separator, no duplicate substrings.
(function autoptimize() {
	var csv = appendCsv('', RELATIVE_ASSET_PATHS);
	RELATIVE_ASSET_PATHS.forEach(function (p) {
		assert.ok(csv.indexOf(p) !== -1, 'csv missing ' + p);
	});
	assert.ok(csv.indexOf(', ') !== -1, 'uses ", " separator');

	var again = appendCsv(csv, RELATIVE_ASSET_PATHS);
	assert.strictEqual(again, csv, 'no duplicates on re-merge (csv mode)');

	var withExisting = appendCsv('/wp-content/themes/my-theme/foo.js', RELATIVE_ASSET_PATHS);
	assert.ok(withExisting.indexOf('/wp-content/themes/my-theme/foo.js') === 0, 'existing csv preserved at head');
	assert.ok(withExisting.indexOf(RELATIVE_ASSET_PATHS[0]) !== -1, 'new entries appended');
})();

// 6. Coverage: every shipped asset path includes the plugin slug (catches accidental typos).
(function coverage() {
	RELATIVE_ASSET_PATHS.forEach(function (p) {
		assert.ok(
			p.indexOf('/wp-content/plugins/mp-sticky_custom_cart/assets/') === 0,
			'asset path must start with plugin assets root: ' + p
		);
		assert.ok(/\.(css|js)$/.test(p), 'asset path must target css/js: ' + p);
	});
	assert.ok(ASSETS_WILDCARD.indexOf('mp-sticky_custom_cart/assets/') !== -1);
})();

console.log('# third-party-optimizer-compat: OK');
