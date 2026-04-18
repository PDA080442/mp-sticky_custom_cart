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
var hookRegistry = fs.readFileSync(path.join(root, 'core', 'HookRegistry.php'), 'utf8');
var js = fs.readFileSync(path.join(root, 'assets', 'js', 'frontend.js'), 'utf8');
var css = fs.readFileSync(path.join(root, 'admin', 'css', 'settings-preview.css'), 'utf8');

assert.ok(defaults.includes('image_click_behavior'), 'Defaults should define image_click_behavior');
assert.ok(defaults.includes('catalog_add_surface'), 'Defaults should define catalog_add_surface');
assert.ok(defaults.includes('catalog_cart_icon_desktop'), 'Defaults should define catalog_cart_icon_desktop');
assert.ok(defaults.includes('catalog_cart_icon_offset_top_px'), 'Defaults should define catalog_cart_icon_offset_top_px');
assert.ok(schema.includes('image_click_behavior') && schema.includes('theme_default'), 'Schema should allow image_click_behavior');
assert.ok(schema.includes('catalog_add_surface') && schema.includes('cart_icon'), 'Schema should allow catalog_add_surface');
assert.ok(schema.includes('catalog_cart_icon_desktop') && schema.includes('tap_reveal'), 'Schema should allow cart icon visibility keys');
assert.ok(
	schema.includes('catalog_cart_icon_offset_left_px') &&
		schema.includes('catalog_cart_icon_hit_size_px') &&
		schema.includes('catalog_cart_icon_glyph_size_px') &&
		schema.includes('catalog_cart_icon_transition_delay_ms') &&
		schema.includes('catalog_cart_icon_mobile_mode') &&
		schema.includes('force_visible'),
	'Schema should allow cart icon geometry + mobile mode'
);
assert.ok(sanitizer.includes('is_safe_css_easing_token'), 'Sanitizer should validate hover easing');
assert.ok(sanitizer.includes('catalog.hover_animation_easing'), 'Sanitizer should special-case catalog easing');
assert.ok(settings.includes('render_catalog_impact_notes'), 'Settings should show catalog impact notes');
assert.ok(settings.includes('render_catalog_card_preview'), 'Settings should render catalog card preview');
assert.ok(settings.includes('Поведение клика по миниатюре'), 'Settings should expose image click behavior control');
assert.ok(flagResolver.includes('imageClickBehavior'), 'Localized catalog should pass imageClickBehavior');
assert.ok(flagResolver.includes('catalogAddSurface'), 'Localized catalog should pass catalogAddSurface');
assert.ok(flagResolver.includes('catalogCartIconDesktop'), 'Localized catalog should pass catalogCartIconDesktop');
assert.ok(
	flagResolver.includes('catalogCartIconOffsetTopPx') && flagResolver.includes('catalogCartIconMobileMode'),
	'Localized catalog should pass cart icon geometry + mobile mode'
);
assert.ok(hookRegistry.includes('ShopLoopCartIconHost'), 'HookRegistry should register ShopLoopCartIconHost');
assert.ok(js.includes('imageClickBehavior') && js.includes('theme_default'), 'JS should respect imageClickBehavior');
assert.ok(js.includes('catalogAddSurface') && js.includes('cart_icon'), 'JS should handle catalogAddSurface');
assert.ok(js.includes('mp-scc-catalog-cart-icon-slot') && js.includes('attachCatalogCartIconPointerGuards'), 'JS should use cart icon slot + pointer guards');
assert.ok(js.includes('applyCatalogCartIconMobileModeAttr') && js.includes('data-mp-scc-cart-icon-mobile-mode'), 'JS should apply mobile cart icon mode on html');
assert.ok(
	js.includes('resolveCatalogImageFromClickTarget') && js.includes('cart_icon') && js.includes('return null'),
	'JS should skip image resolution when cart_icon (defense in depth)'
);
assert.ok(css.includes('mp-scc-admin-catalog-preview'), 'Admin CSS should style catalog preview');

console.log('catalog-tab-dp: OK');
