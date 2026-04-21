#!/usr/bin/env node
/**
 * Smoke checks for sticky cart summary (line count, snapshot, reconcile).
 * Run: node tests/sticky-summary.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var js = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'frontend.js'), 'utf8');
var php = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'AjaxEndpointsHooks.php'), 'utf8');

assert.ok(js.includes('line_count') && js.includes('scheduleReconcile'), 'JS should reconcile and use line_count');
assert.ok(
	js.includes('data-mp-scc-cart-line-count') && js.includes('data-mp-scc-cart-qty-count'),
	'JS should bind line count vs total qty DOM hooks'
);
assert.ok(js.includes('mp-scc-sticky-summary--loading'), 'JS should toggle loading class on summary');
assert.ok(js.includes('wc_fragments_loaded'), 'JS should listen for wc_fragments_loaded');
assert.ok(js.includes('scheduleRefreshFromWooEvent'), 'JS should debounce Woo cart event refreshes');
assert.ok(js.includes('snapshot_ts'), 'JS should handle snapshot_ts');
assert.ok(php.includes("'snapshot_ts'"), 'PHP payload should include snapshot_ts');
assert.ok(js.includes("postAjax('clearCart'"), 'JS should call clearCart AJAX');
assert.ok(js.includes('data-mp-scc-clear-cart'), 'JS should bind clear cart control');
assert.ok(php.includes('handle_clear_cart'), 'PHP should expose clear cart handler');

var flagPhp = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'FrontendFlagResolver.php'), 'utf8');
assert.ok(flagPhp.includes("'clearCart'"), 'Localized actions should include clearCart');

console.log('sticky-summary: OK');
