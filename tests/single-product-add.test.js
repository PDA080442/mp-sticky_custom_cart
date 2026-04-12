#!/usr/bin/env node
/**
 * Single product add-to-cart: form hook, added_to_cart feedback, YITH quick view, labels.
 * Run: node tests/single-product-add.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var js = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'frontend.js'), 'utf8');
var labels = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'UiLabelsDefaults.php'), 'utf8');
var schema = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'SettingsValidationSchema.php'), 'utf8');

assert.ok(js.includes('initSingleProductAddToCart'), 'JS should init single product add integration');
assert.ok(js.includes("submit.mpSccSingleCart', 'form.cart'"), 'JS should hook form.cart submit on single product');
assert.ok(js.includes('added_to_cart.mpSccSingle'), 'JS should listen for added_to_cart for single success feedback');
assert.ok(js.includes('shouldShowSingleProductAddFeedback'), 'JS should detect single page vs quick view');
assert.ok(js.includes('showStickyInlineFeedback'), 'JS should show sticky feedback on single add success');
assert.ok(js.includes('yith_added_to_cart'), 'JS should listen for YITH added_to_cart for sticky sync');
assert.ok(js.includes('single_add_success'), 'JS should use single_add_success label key');
assert.ok(labels.includes('KEY_SINGLE_ADD_SUCCESS'), 'PHP labels should define single_add_success');
assert.ok(schema.includes('single_add_success'), 'Validation schema should allow single_add_success');

console.log('single-product-add: OK');
