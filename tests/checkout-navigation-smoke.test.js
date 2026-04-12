#!/usr/bin/env node
/**
 * Checkout navigation smoke: sticky → Woo checkout URL, empty guard, server snapshot, query merge.
 * Run: node tests/checkout-navigation-smoke.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var root = path.join(__dirname, '..');
var renderer = fs.readFileSync(path.join(root, 'frontend', 'StickyCartRenderer.php'), 'utf8');
var js = fs.readFileSync(path.join(root, 'assets', 'js', 'frontend.js'), 'utf8');
var ajax = fs.readFileSync(path.join(root, 'frontend', 'AjaxEndpointsHooks.php'), 'utf8');
var preserve = fs.readFileSync(path.join(root, 'core', 'CheckoutQueryPreserve.php'), 'utf8');
var qa = fs.readFileSync(path.join(root, 'docs', 'qa-checkout-navigation.md'), 'utf8');

assert.ok(renderer.includes('wc_get_checkout_url'), 'Sticky should use Woo checkout URL');
assert.ok(renderer.includes('data-mp-scc-checkout-base'), 'Sticky should expose checkout base for JS merge');
assert.ok(renderer.includes("$empty ? '#' : esc_url( $checkout_url )"), 'Empty cart should use # href until cart has lines');
assert.ok(js.includes('syncCheckoutState'), 'JS should sync checkout link when cart empties/fills');
assert.ok(js.includes('mergeUrlWithLocationQuery'), 'JS should merge allowed query keys onto checkout href');
assert.ok(js.includes('mp-scc-checkout--disabled') && js.includes('e.preventDefault()'), 'Disabled checkout should block navigation');
assert.ok(js.includes('scheduleRefreshFromWooEvent'), 'JS should debounce refresh after Woo cart DOM events');
assert.ok(ajax.includes('build_cart_snapshot_payload'), 'AJAX should build cart snapshot from WC cart');
assert.ok(ajax.includes('snapshot_ts'), 'Snapshot payload should include snapshot_ts for freshness');
assert.ok(preserve.includes('merge_request_into_url'), 'Checkout URL should support marketing query merge');
assert.ok(qa.includes('wc_get_checkout_url'), 'QA doc should mention wc_get_checkout_url');

console.log('checkout-navigation-smoke: OK');
