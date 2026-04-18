#!/usr/bin/env node
/**
 * Sticky "everywhere" regression: visibility contract, filter, no template gate, QA docs.
 * Run: node tests/sticky-everywhere.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var vis = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'StickyCartVisibility.php'), 'utf8');
var hooks = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'StickyCartRenderHooks.php'), 'utf8');
var qaEverywhere = fs.readFileSync(path.join(__dirname, '..', 'docs', 'qa-sticky-everywhere.md'), 'utf8');
var qaVisibility = fs.readFileSync(path.join(__dirname, '..', 'docs', 'qa-sticky-visibility.md'), 'utf8');

assert.ok(
	vis.includes("apply_filters( 'mp_sticky_custom_cart_should_render_sticky', true )"),
	'Visibility should default to true with filter mp_sticky_custom_cart_should_render_sticky'
);
assert.ok(!vis.includes('is_checkout()'), 'Visibility should not hard-exclude checkout (use filter if needed)');
assert.ok(!vis.includes('is_product()'), 'Visibility should not gate on product template');
assert.ok(!vis.includes('is_shop()'), 'Visibility should not gate on shop template');
assert.ok(hooks.includes('mp_sticky_custom_cart_render_sticky_cart'), 'Footer hook should fire sticky render action');
assert.ok(qaEverywhere.includes('is_checkout'), 'qa-sticky-everywhere should document checkout case');
assert.ok(qaEverywhere.includes('is_account'), 'qa-sticky-everywhere should document account case');
assert.ok(qaEverywhere.includes('is_product') || qaEverywhere.includes('Single product'), 'qa-sticky-everywhere should cover single product');
assert.ok(/блог|запис|post/i.test(qaEverywhere), 'qa-sticky-everywhere should cover blog');
assert.ok(/статич|is_page/i.test(qaEverywhere), 'qa-sticky-everywhere should cover static pages');
assert.ok(/архив|is_shop|категор/i.test(qaEverywhere), 'qa-sticky-everywhere should cover product archive');
assert.ok(/кастом|конструктор|Elementor|wp_footer/i.test(qaEverywhere), 'qa-sticky-everywhere should mention custom templates');
assert.ok(qaEverywhere.includes('sticky-everywhere.test.js'), 'qa-sticky-everywhere should reference this test file');
assert.ok(qaVisibility.includes('mp_sticky_custom_cart_should_render_sticky'), 'qa-sticky-visibility should mention visibility filter');

console.log('sticky-everywhere: OK');
