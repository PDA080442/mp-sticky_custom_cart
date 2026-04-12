#!/usr/bin/env node
/**
 * Drawer line snapshot + client row template.
 * Run: node tests/drawer-line.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var php = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'AjaxEndpointsHooks.php'), 'utf8');
var js = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'frontend.js'), 'utf8');
var css = fs.readFileSync(path.join(__dirname, '..', 'assets', 'css', 'frontend.css'), 'utf8');
var labels = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'UiLabelsDefaults.php'), 'utf8');

assert.ok(php.includes("'thumbnail_html'"), 'Snapshot should include thumbnail_html');
assert.ok(php.includes("'line_price_html'"), 'Snapshot should include line_price_html');
assert.ok(php.includes("'stock_notice'"), 'Snapshot should include stock_notice');
assert.ok(php.includes("'snapshot_line_id'"), 'Snapshot should include snapshot_line_id');
assert.ok(php.includes('build_cart_line_stock_notice'), 'PHP should build stock notices');
assert.ok(php.includes('mp_sticky_custom_cart_cart_line_snapshot'), 'Line snapshot should be filterable');
assert.ok(js.includes('mp-scc-line__thumb'), 'JS should render thumb wrapper');
assert.ok(js.includes('data-mp-scc-line-remove'), 'JS should wire remove control');
assert.ok(js.includes('removeLine'), 'JS should define removeLine');
assert.ok(js.includes('data-mp-scc-snapshot-line-id'), 'JS should expose stable snapshot line id');
assert.ok(css.includes('.mp-scc-line__thumb'), 'CSS should style drawer thumb');
assert.ok(css.includes('-webkit-line-clamp'), 'CSS should clamp long titles');
assert.ok(labels.includes('KEY_DRAWER_REMOVE_LINE'), 'Labels should include drawer remove key');

console.log('drawer-line: OK');
