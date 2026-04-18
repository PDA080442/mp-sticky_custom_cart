#!/usr/bin/env node
/**
 * Sticky checkout link: Woo URL, empty-state UI, query preservation, accessibility hooks.
 * Run: node tests/sticky-checkout.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var js = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'frontend.js'), 'utf8');
var renderer = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'StickyCartRenderer.php'), 'utf8');
var localize = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'FrontendFlagResolver.php'), 'utf8');
var preserve = fs.readFileSync(path.join(__dirname, '..', 'core', 'CheckoutQueryPreserve.php'), 'utf8');

assert.ok(renderer.includes('wc_get_checkout_url'), 'Renderer should use WooCommerce checkout URL');
assert.ok(renderer.includes('data-mp-scc-checkout-base'), 'Renderer should expose base checkout URL for JS');
assert.ok(renderer.includes('mp-scc-checkout--disabled'), 'Renderer should mark disabled state when cart empty');
assert.ok(renderer.includes('data-mp-scc-checkout-aria-disabled'), 'Renderer should expose disabled aria text');
assert.ok(renderer.includes('data-mp-scc-checkout-label'), 'Renderer should expose checkout label for icon a11y');
assert.ok(js.includes('mergeUrlWithLocationQuery'), 'JS should merge page query onto checkout');
assert.ok(js.includes('syncCheckoutState'), 'JS should sync checkout link when cart snapshot updates');
assert.ok(js.includes('syncStickyActions'), 'JS should sync checkout + clear + drawer empty state');
assert.ok(js.includes('clear_cart_in_progress'), 'JS should use loading label for clear-cart');
assert.ok(js.includes('data().checkoutPreserveQueryKeys'), 'mergeUrl should read allowed keys from mpSccData');
assert.ok(localize.includes('checkoutPreserveQueryKeys'), 'Localized data should list preserved query keys');
assert.ok(preserve.includes('merge_request_into_url'), 'PHP should merge allowed query params into checkout URL');

console.log('sticky-checkout: OK');
