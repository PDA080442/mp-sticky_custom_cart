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
assert.ok(js.includes('mp-scc-sticky-summary--loading'), 'JS should toggle loading class on summary');
assert.ok(js.includes('wc_fragments_loaded'), 'JS should listen for wc_fragments_loaded');
assert.ok(js.includes('snapshot_ts'), 'JS should handle snapshot_ts');
assert.ok(php.includes("'snapshot_ts'"), 'PHP payload should include snapshot_ts');

console.log('sticky-summary: OK');
