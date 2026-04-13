#!/usr/bin/env node
/**
 * Catalog admin tab (dp): image click behavior, preview, validation, localize.
 * Run: node tests/catalog-tab-dp.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var root = path.join(__dirname, '..');
var defaults = fs.readFileSync(path.join(root, 'core', 'Config', 'UiSettingsDefaults.php'), 'utf8');
var schema = fs.readFileSync(path.join(root, 'core', 'Config', 'SettingsValidationSchema.php'), 'utf8');
var sanitizer = fs.readFileSync(path.join(root, 'admin', 'SettingsSanitizer.php'), 'utf8');
var settings = fs.readFileSync(path.join(root, 'admin', 'SettingsPage.php'), 'utf8');
var flagResolver = fs.readFileSync(path.join(root, 'frontend', 'FrontendFlagResolver.php'), 'utf8');
var js = fs.readFileSync(path.join(root, 'assets', 'js', 'frontend.js'), 'utf8');
var css = fs.readFileSync(path.join(root, 'admin', 'css', 'settings-preview.css'), 'utf8');

assert.ok(defaults.includes('image_click_behavior'), 'Defaults should define image_click_behavior');
assert.ok(schema.includes('image_click_behavior') && schema.includes('theme_default'), 'Schema should allow image_click_behavior');
assert.ok(sanitizer.includes('is_safe_css_easing_token'), 'Sanitizer should validate hover easing');
assert.ok(sanitizer.includes('catalog.hover_animation_easing'), 'Sanitizer should special-case catalog easing');
assert.ok(settings.includes('render_catalog_impact_notes'), 'Settings should show catalog impact notes');
assert.ok(settings.includes('render_catalog_card_preview'), 'Settings should render catalog card preview');
assert.ok(settings.includes('Поведение клика по миниатюре'), 'Settings should expose image click behavior control');
assert.ok(flagResolver.includes('imageClickBehavior'), 'Localized catalog should pass imageClickBehavior');
assert.ok(js.includes('imageClickBehavior') && js.includes('theme_default'), 'JS should respect imageClickBehavior');
assert.ok(css.includes('mp-scc-admin-catalog-preview'), 'Admin CSS should style catalog preview');

console.log('catalog-tab-dp: OK');
