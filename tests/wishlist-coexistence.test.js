#!/usr/bin/env node
/**
 * Smoke checks for wishlist + overlay coexistence (Phase 6.x).
 * Run: node tests/wishlist-coexistence.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var root = path.join(__dirname, '..');
var css = fs.readFileSync(path.join(root, 'assets', 'css', 'frontend.css'), 'utf8');
var js = fs.readFileSync(path.join(root, 'assets', 'js', 'frontend.js'), 'utf8');
var contract = fs.readFileSync(path.join(root, 'core', 'Config', 'CssVariablesContract.php'), 'utf8');

assert.ok(css.includes('--mp-scc-catalog-overlay-z-index'), 'overlay should use CSS var for z-index');
assert.ok(css.includes('--mp-scc-wishlist-icon-z-index'), 'wishlist layer should use CSS var');
assert.ok(css.includes('mp-scc-wishlist-integration-on'), 'wishlist rules should depend on body class');
assert.ok(js.includes('initWishlistIntegrationBodyClass'), 'JS should expose initWishlistIntegrationBodyClass');
assert.ok(js.includes('mp-scc-wishlist-integration-on'), 'JS should set body class');
assert.ok(contract.includes('catalog_overlay_z_index') && contract.includes('heart_icon_z_index'), 'contract should map z-index settings');

console.log('wishlist-coexistence: OK');
