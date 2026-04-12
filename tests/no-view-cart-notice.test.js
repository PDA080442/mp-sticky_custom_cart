#!/usr/bin/env node
/**
 * Add-to-cart notice: strip "View cart" link — filters, settings, HookRegistry.
 * Run: node tests/no-view-cart-notice.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var hooks = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'AddToCartMessageHooks.php'), 'utf8');
var registry = fs.readFileSync(path.join(__dirname, '..', 'core', 'HookRegistry.php'), 'utf8');
var defaults = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'UiSettingsDefaults.php'), 'utf8');
var schema = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'SettingsValidationSchema.php'), 'utf8');
var settingsPage = fs.readFileSync(path.join(__dirname, '..', 'admin', 'SettingsPage.php'), 'utf8');

assert.ok(hooks.includes('wc_add_to_cart_message_html'), 'Should filter wc_add_to_cart_message_html');
assert.ok(
	hooks.includes('woocommerce_add_to_cart_message_html'),
	'Should register legacy woocommerce_add_to_cart_message_html for compatibility'
);
assert.ok(hooks.includes('wc-forward'), 'Should strip wc-forward anchor as fallback');
assert.ok(hooks.includes('strip_cart_anchor_html'), 'Should define strip_cart_anchor_html');
assert.ok(hooks.includes('notices.remove_view_cart_link'), 'Should read notices.remove_view_cart_link setting');
assert.ok(hooks.includes('mp_sticky_custom_cart_strip_view_cart_from_notice'), 'Should expose opt-out filter');
assert.ok(registry.includes('AddToCartMessageHooks::register'), 'HookRegistry should register AddToCartMessageHooks');
assert.ok(defaults.includes('remove_view_cart_link'), 'UiSettingsDefaults should default remove_view_cart_link');
assert.ok(schema.includes('notices') && schema.includes('remove_view_cart_link'), 'Schema should define notices.remove_view_cart_link');
assert.ok(settingsPage.includes("'notices'") && settingsPage.includes('remove_view_cart_link'), 'Settings UI should expose notices.remove_view_cart_link');

console.log('no-view-cart-notice: OK');
