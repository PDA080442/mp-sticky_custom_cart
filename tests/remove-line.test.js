#!/usr/bin/env node
/**
 * Remove cart line: dedicated AJAX, logging, client state.
 * Run: node tests/remove-line.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var php = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'AjaxEndpointsHooks.php'), 'utf8');
var js = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'frontend.js'), 'utf8');
var css = fs.readFileSync(path.join(__dirname, '..', 'assets', 'css', 'frontend.css'), 'utf8');
var constants = fs.readFileSync(path.join(__dirname, '..', 'core', 'Constants.php'), 'utf8');
var flags = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'FrontendFlagResolver.php'), 'utf8');

assert.ok(constants.includes('AJAX_ACTION_REMOVE_CART_LINE'), 'Constants should define remove cart line action');
assert.ok(php.includes('handle_remove_cart_line'), 'PHP should register remove cart line handler');
assert.ok(php.includes('log_remove_line_failure'), 'PHP should log remove line failures');
assert.ok(php.includes('remove_cart_line'), 'Log type should be remove_cart_line');
assert.ok(flags.includes('removeCartLine'), 'Localized actions should include removeCartLine');
assert.ok(js.includes('commitRemoveLine'), 'JS should use commitRemoveLine');
assert.ok(js.includes('removeCartLine'), 'JS should call removeCartLine action');
assert.ok(js.includes('line_removed'), 'JS should show line_removed feedback');
assert.ok(js.includes('clearLineRemovingState'), 'JS should clear per-line removing state on error');
assert.ok(js.includes('mp-scc-line--removing'), 'JS should toggle removing class');
assert.ok(css.includes('mp-scc-line--removing'), 'CSS should style removing state');

console.log('remove-line: OK');
