#!/usr/bin/env node
/**
 * Cart page URL redirect: template_redirect, settings schema, HookRegistry wiring.
 * Run: node tests/cart-route-redirect.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var hooks = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'CartRouteRedirectHooks.php'), 'utf8');
var registry = fs.readFileSync(path.join(__dirname, '..', 'core', 'HookRegistry.php'), 'utf8');
var defaults = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'UiSettingsDefaults.php'), 'utf8');
var schema = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'SettingsValidationSchema.php'), 'utf8');
var sanitizer = fs.readFileSync(path.join(__dirname, '..', 'admin', 'SettingsSanitizer.php'), 'utf8');
var settingsPage = fs.readFileSync(path.join(__dirname, '..', 'admin', 'SettingsPage.php'), 'utf8');
var qa = fs.readFileSync(path.join(__dirname, '..', 'docs', 'qa-cart-route.md'), 'utf8');

assert.ok(hooks.includes('template_redirect'), 'CartRouteRedirectHooks should use template_redirect');
assert.ok(hooks.includes('maybe_redirect_cart'), 'CartRouteRedirectHooks should define maybe_redirect_cart');
assert.ok(hooks.includes('is_cart()'), 'Redirect should gate on is_cart');
assert.ok(hooks.includes('wp_safe_redirect'), 'Redirect should use wp_safe_redirect');
assert.ok(hooks.includes('mp_sticky_custom_cart_cart_redirect_url'), 'Should allow filtering redirect target URL');
assert.ok(hooks.includes('page_on_front'), 'Should prevent loop when cart page is front page');
assert.ok(hooks.includes('cart_redirect'), 'Logging should tag cart_redirect');
assert.ok(registry.includes('CartRouteRedirectHooks::register'), 'HookRegistry should register cart route hooks');
assert.ok(defaults.includes("'cart_route'"), 'UiSettingsDefaults should define cart_route');
assert.ok(schema.includes('redirect_to_home'), 'Schema should include redirect_to_home');
assert.ok(schema.includes('redirect_status_code'), 'Schema should include redirect_status_code');
assert.ok(sanitizer.includes("'oneof'") && sanitizer.includes('sanitize_integer'), 'Sanitizer should support integer oneof');
assert.ok(settingsPage.includes('cart_route'), 'Settings page should expose cart_route fields');
assert.ok(qa.includes('cart'), 'QA doc should mention cart route');

console.log('cart-route-redirect: OK');
