#!/usr/bin/env node
/**
 * Sticky visibility: body class, layout reserve, empty state hooks, QA doc.
 * Run: node tests/sticky-visibility.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var css = fs.readFileSync(path.join(__dirname, '..', 'assets', 'css', 'frontend.css'), 'utf8');
var js = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'frontend.js'), 'utf8');
var vis = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'StickyCartVisibility.php'), 'utf8');
var hooks = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'StickyCartRenderHooks.php'), 'utf8');
var contract = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'CssVariablesContract.php'), 'utf8');
var renderer = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'StickyCartRenderer.php'), 'utf8');
var qa = fs.readFileSync(path.join(__dirname, '..', 'docs', 'qa-sticky-visibility.md'), 'utf8');

assert.ok(vis.includes('should_render_sticky'), 'StickyCartVisibility should expose should_render_sticky');
assert.ok(!vis.includes('$cart->is_empty'), 'Visibility must not call cart->is_empty()');
assert.ok(hooks.includes('wp_body_open'), 'Sticky root should prefer wp_body_open so fixed positioning is viewport-anchored');
assert.ok(hooks.includes('mp-scc-sticky-active'), 'body_class should add mp-scc-sticky-active');
assert.ok(hooks.includes('mp-scc-sticky-layout-tristate'), 'body_class should add tristate layout when flag on');
assert.ok(hooks.includes('KEY_STICKY_TRISTATE_ENABLED'), 'body_class should consult tristate flag');
assert.ok(hooks.includes('StickyCartVisibility::should_render_sticky'), 'body_class should use visibility helper');
assert.ok(contract.includes('sticky-layout-reserve'), 'CssVariablesContract should emit sticky-layout-reserve');
assert.ok(contract.includes('sticky-fab-layout-reserve'), 'CssVariablesContract should emit FAB layout reserve');
assert.ok(css.includes('body.mp-scc-sticky-active'), 'CSS should reserve space for fixed bar');
assert.ok(css.includes('mp-scc-sticky-layout-tristate'), 'CSS should reserve space for tristate FAB');
assert.ok(css.includes('mp-scc-sticky--tristate'), 'CSS should style tristate floating bar');
assert.ok(css.includes('--mp-scc-sticky-layout-reserve'), 'CSS should use layout reserve variable');
assert.ok(css.includes('safe-area-inset-bottom'), 'CSS should mention safe-area for body reserve');
assert.ok(css.includes('orientation: landscape') && css.includes('max-height: 500px'), 'CSS should tune landscape short viewports');
assert.ok(css.includes('mp-scc-sticky-suppress'), 'CSS should document modal suppress class');
assert.ok(js.includes('mp-scc-sticky--empty'), 'JS should toggle empty class on sticky root');
assert.ok(renderer.includes('data-mp-scc-cart-empty'), 'Renderer should expose cart empty data attribute');
assert.ok(renderer.includes('data-mp-scc-sticky-tristate'), 'Renderer should expose tristate data attribute');
assert.ok(renderer.includes('mp-scc-sticky--tristate'), 'Renderer should add tristate class when flag on');
assert.ok(qa.includes('mp_sticky_custom_cart_should_render_sticky'), 'QA doc should mention visibility filter');
assert.ok(qa.includes('CLS'), 'QA doc should mention CLS / layout reserve');

console.log('sticky-visibility: OK');
