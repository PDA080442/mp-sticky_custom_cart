#!/usr/bin/env node
/**
 * Drawer empty state: markup, labels, JS sync, admin preview, QA doc.
 * Run: node tests/drawer-empty.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var js = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'frontend.js'), 'utf8');
var renderer = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'StickyCartRenderer.php'), 'utf8');
var labels = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'UiLabelsDefaults.php'), 'utf8');
var settingsPage = fs.readFileSync(path.join(__dirname, '..', 'admin', 'SettingsPage.php'), 'utf8');
var schema = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'SettingsValidationSchema.php'), 'utf8');
var adminHooks = fs.readFileSync(path.join(__dirname, '..', 'admin', 'AdminAssetsHooks.php'), 'utf8');
var qa = fs.readFileSync(path.join(__dirname, '..', 'docs', 'qa-drawer-empty.md'), 'utf8');

assert.ok(labels.includes('KEY_DRAWER_EMPTY_HINT'), 'Labels should define drawer_empty_hint key');
assert.ok(renderer.includes('mp-scc-drawer-empty-title'), 'Renderer should use empty title class');
assert.ok(renderer.includes('mp-scc-drawer-empty-hint'), 'Renderer should render hint line');
assert.ok(renderer.includes('mp-scc-drawer-empty-icon'), 'Renderer should include empty visual icon');
assert.ok(renderer.includes('data-mp-scc-clear-aria-disabled'), 'Renderer should expose clear disabled aria text');
assert.ok(renderer.includes('aria-hidden="true"'), 'Renderer should hide item list from AT when empty');
assert.ok(js.includes('syncStickyActions'), 'JS should sync clear + drawer classes via syncStickyActions');
assert.ok(js.includes('mp-scc-drawer--empty'), 'JS should toggle drawer empty class');
assert.ok(js.includes('mp-scc-clear-cart--disabled'), 'JS should toggle clear button disabled class');
assert.ok(schema.includes('drawer_empty_hint'), 'Validation schema should allow drawer_empty_hint');
assert.ok(settingsPage.includes('render_drawer_empty_preview'), 'Settings should render drawer empty preview');
assert.ok(settingsPage.includes('drawer_empty_hint'), 'Settings should expose drawer_empty_hint field');
assert.ok(adminHooks.includes('settings-preview.css'), 'Admin should enqueue settings preview CSS');
assert.ok(qa.includes('Пустая корзина'), 'QA doc should describe empty cart scenarios');

console.log('drawer-empty: OK');
