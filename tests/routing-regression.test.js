#!/usr/bin/env node
/**
 * Routing regression pack: cart redirect vs checkout, HookRegistry wiring, visibility gates.
 * Complements tests/cart-route-redirect.test.js and tests/external-cart-links.test.js.
 * Run: node tests/routing-regression.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var root = path.join(__dirname, '..');
var redirect = fs.readFileSync(path.join(root, 'frontend', 'CartRouteRedirectHooks.php'), 'utf8');
var registry = fs.readFileSync(path.join(root, 'core', 'HookRegistry.php'), 'utf8');
var visibility = fs.readFileSync(path.join(root, 'frontend', 'StickyCartVisibility.php'), 'utf8');
var renderer = fs.readFileSync(path.join(root, 'frontend', 'StickyCartRenderer.php'), 'utf8');
var wooGate = fs.readFileSync(path.join(root, 'core', 'WooCommerceGate.php'), 'utf8');

assert.ok(redirect.includes('template_redirect'), 'Cart redirect should hook template_redirect');
assert.ok(redirect.includes('is_checkout()') && redirect.includes('return'), 'Cart redirect must not run on checkout');
assert.ok(redirect.includes('is_cart()'), 'Cart redirect should gate on is_cart');
assert.ok(redirect.includes('wp_safe_redirect'), 'Cart redirect should use wp_safe_redirect');
assert.ok(registry.includes('CartRouteRedirectHooks::register'), 'HookRegistry should register cart route redirect');
assert.ok(registry.includes('StickyCartRenderHooks::register'), 'HookRegistry should register sticky render hooks');
assert.ok(visibility.includes('WC()->cart'), 'Visibility should require WC cart object');
assert.ok(renderer.includes('wc_get_checkout_url'), 'Renderer checkout link must not reuse cart redirect home URL');
assert.ok(wooGate.includes('HookRegistry::register'), 'WooCommerceGate should register HookRegistry when WC active');

console.log('routing-regression: OK');
