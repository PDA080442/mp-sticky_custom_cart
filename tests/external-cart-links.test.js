#!/usr/bin/env node
/**
 * External cart URL: marketing preserve, add-to-cart hit counter, checkout guard.
 * Run: node tests/external-cart-links.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var hooks = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'CartRouteRedirectHooks.php'), 'utf8');
var constants = fs.readFileSync(path.join(__dirname, '..', 'core', 'Constants.php'), 'utf8');
var defaults = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'UiSettingsDefaults.php'), 'utf8');
var schema = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'SettingsValidationSchema.php'), 'utf8');
var preserve = fs.readFileSync(path.join(__dirname, '..', 'core', 'CheckoutQueryPreserve.php'), 'utf8');
var qa = fs.readFileSync(path.join(__dirname, '..', 'docs', 'qa-external-cart-links.md'), 'utf8');
var settings = fs.readFileSync(path.join(__dirname, '..', 'admin', 'SettingsPage.php'), 'utf8');

assert.ok(hooks.includes('CheckoutQueryPreserve::merge_request_into_url'), 'Should merge marketing params onto redirect');
assert.ok(hooks.includes('mp_sticky_custom_cart_cart_redirect_final_url'), 'Should expose final URL filter');
assert.ok(hooks.includes('$_GET') && hooks.includes("'add-to-cart'"), 'Should detect add-to-cart query');
assert.ok(hooks.includes('is_checkout()'), 'Should exclude checkout template');
assert.ok(hooks.includes('OPTION_EXTERNAL_CART_LINK_HITS'), 'Should bump external cart link hit option');
assert.ok(constants.includes('OPTION_EXTERNAL_CART_LINK_HITS'), 'Constants should define hit counter option');
assert.ok(defaults.includes('preserve_marketing_params_on_redirect'), 'Defaults should include marketing preserve');
assert.ok(defaults.includes('track_external_cart_link_hits'), 'Defaults should include hit tracking flag');
assert.ok(schema.includes('preserve_marketing_params_on_redirect'), 'Schema should validate marketing preserve');
assert.ok(preserve.includes('merge_request_into_url'), 'CheckoutQueryPreserve should merge GET onto URL');
assert.ok(settings.includes('preserve_marketing_params_on_redirect'), 'Settings UI should expose marketing preserve');
assert.ok(qa.includes('add-to-cart'), 'QA doc should mention add-to-cart');

console.log('external-cart-links: OK');
