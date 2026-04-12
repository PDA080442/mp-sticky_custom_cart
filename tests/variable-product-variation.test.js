#!/usr/bin/env node
/**
 * Variable product: variation guard, variation_required label, highlight class.
 * Run: node tests/variable-product-variation.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var js = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'frontend.js'), 'utf8');
var css = fs.readFileSync(path.join(__dirname, '..', 'assets', 'css', 'frontend.css'), 'utf8');
var labels = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'UiLabelsDefaults.php'), 'utf8');

assert.ok(js.includes('initVariableProductVariationGuard'), 'JS should register variation guard');
assert.ok(js.includes('getVariationIdFromForm'), 'JS should read variation_id from form');
assert.ok(js.includes('addEventListener') && js.includes("'click'") && js.includes('true'), 'Guard should use capture-phase click');
assert.ok(
	js.includes('single_add_to_cart_button') && js.includes('form.variations_form'),
	'Guard should target Woo variable add button inside variations_form'
);
assert.ok(js.includes('stopImmediatePropagation'), 'Guard should block other handlers when no variation');
assert.ok(js.includes('variation_required'), 'Guard should use variation_required label');
assert.ok(js.includes('found_variation') && js.includes('reset_data'), 'Guard should clear highlight on Woo variation events');
assert.ok(css.includes('.mp-scc-variation--error'), 'CSS should style variation error highlight');
assert.ok(labels.includes('KEY_VARIATION_REQUIRED'), 'Labels should expose variation_required for settings');

console.log('variable-product-variation: OK');
