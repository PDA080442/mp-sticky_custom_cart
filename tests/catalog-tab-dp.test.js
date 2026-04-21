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
var dynamicStyles = fs.readFileSync(path.join(root, 'frontend', 'DynamicStylesProvider.php'), 'utf8');
var cssContract = fs.readFileSync(path.join(root, 'core', 'Config', 'CssVariablesContract.php'), 'utf8');
var js = fs.readFileSync(path.join(root, 'assets', 'js', 'frontend.js'), 'utf8');
var css = fs.readFileSync(path.join(root, 'admin', 'css', 'settings-preview.css'), 'utf8');
var cartPresets = fs.readFileSync(path.join(root, 'core', 'CatalogCartIconPresets.php'), 'utf8');

assert.ok(defaults.includes('image_click_behavior'), 'Defaults should define image_click_behavior');
assert.ok(defaults.includes('catalog_add_surface'), 'Defaults should define catalog_add_surface');
assert.ok(defaults.includes('catalog_cart_icon_desktop'), 'Defaults should define catalog_cart_icon_desktop');
assert.ok(defaults.includes('catalog_cart_icon_offset_top_px'), 'Defaults should define catalog_cart_icon_offset_top_px');
assert.ok(defaults.includes('catalog_cart_icon_appearance_preset'), 'Defaults should define catalog_cart_icon_appearance_preset');
assert.ok(defaults.includes('catalog_cart_icon_color'), 'Defaults should define catalog_cart_icon_color');
assert.ok(defaults.includes('catalog_cart_icon_bg_color'), 'Defaults should define catalog_cart_icon_bg_color');
assert.ok(defaults.includes('catalog_cart_icon_preset'), 'Defaults should define catalog_cart_icon_preset');
assert.ok(defaults.includes('catalog_cart_icon_stroke_width'), 'Defaults should define catalog_cart_icon_stroke_width');
assert.ok(defaults.includes('catalog_cart_icon_bg_border_radius_px'), 'Defaults should define catalog_cart_icon_bg_border_radius_px');
assert.ok(defaults.includes('catalog_cart_icon_inner_padding_px'), 'Defaults should define catalog_cart_icon_inner_padding_px');
assert.ok(schema.includes('image_click_behavior') && schema.includes('theme_default'), 'Schema should allow image_click_behavior');
assert.ok(schema.includes('catalog_add_surface') && schema.includes('cart_icon'), 'Schema should allow catalog_add_surface');
assert.ok(schema.includes('catalog_cart_icon_desktop') && schema.includes('tap_reveal'), 'Schema should allow cart icon visibility keys');
assert.ok(
	schema.includes('catalog_cart_icon_offset_left_px') &&
		schema.includes('catalog_cart_icon_hit_size_px') &&
		schema.includes('catalog_cart_icon_glyph_size_px') &&
		schema.includes('catalog_cart_icon_transition_delay_ms') &&
		schema.includes('catalog_cart_icon_stroke_width') &&
		schema.includes('catalog_cart_icon_mobile_mode') &&
		schema.includes('force_visible'),
	'Schema should allow cart icon geometry + mobile mode'
);
assert.ok(schema.includes('catalog_cart_icon_color'), 'Schema should allow catalog_cart_icon_color');
assert.ok(schema.includes('catalog_cart_icon_bg_border_radius_px'), 'Schema should allow catalog cart icon bg border radius');
assert.ok(schema.includes('catalog_cart_icon_inner_padding_px'), 'Schema should allow catalog cart icon inner padding');
assert.ok(schema.includes('catalog_cart_icon_appearance_preset') && schema.includes('white_cart_black_bg'), 'Schema should allow appearance preset');
assert.ok(
	schema.includes('catalog_cart_icon_bg_alpha_percent') && schema.includes('catalog_cart_icon_preset'),
	'Schema should allow catalog cart icon bg + preset'
);
assert.ok(!sanitizer.includes('CatalogCartIconMedia'), 'Sanitizer should not reference removed media helper');
assert.ok(cartPresets.includes("'outline_bold'") && cartPresets.includes('STROKE_PLACEHOLDER'), 'Cart icon presets should define variants + stroke placeholder');
assert.ok(sanitizer.includes('is_safe_css_easing_token'), 'Sanitizer should validate hover easing');
assert.ok(sanitizer.includes('catalog.hover_animation_easing'), 'Sanitizer should special-case catalog easing');
assert.ok(settings.includes('render_catalog_impact_notes'), 'Settings should show catalog impact notes');
assert.ok(settings.includes('render_catalog_card_preview'), 'Settings should render catalog card preview');
assert.ok(settings.includes('catalog_cart_icon_appearance_preset'), 'Settings should expose catalog cart icon appearance preset');
assert.ok(settings.includes('catalog_cart_icon_stroke_width'), 'Settings should expose catalog cart icon stroke width');
assert.ok(settings.includes('catalog_cart_icon_bg_border_radius_px'), 'Settings should expose catalog cart icon bg border radius');
assert.ok(settings.includes('catalog_cart_icon_inner_padding_px'), 'Settings should expose catalog cart icon inner padding');
assert.ok(settings.includes('field_catalog_cart_icon_preset_grid'), 'Settings should render cart icon preset grid');
assert.ok(settings.includes('Поведение клика по миниатюре'), 'Settings should expose image click behavior control');
assert.ok(flagResolver.includes('imageClickBehavior'), 'Localized catalog should pass imageClickBehavior');
assert.ok(flagResolver.includes('catalogAddSurface'), 'Localized catalog should pass catalogAddSurface');
assert.ok(flagResolver.includes('catalogCartIconDesktop'), 'Localized catalog should pass catalogCartIconDesktop');
assert.ok(
	flagResolver.includes('catalogCartIconOffsetTopPx') && flagResolver.includes('catalogCartIconMobileMode'),
	'Localized catalog should pass cart icon geometry + mobile mode'
);
assert.ok(
	flagResolver.includes('catalogCartIconPreset') && flagResolver.includes('catalogCartIconPresetInners'),
	'Localized catalog should pass cart icon preset + inner SVG map'
);
assert.ok(flagResolver.includes('catalogCartIconStrokeWidth'), 'Localized catalog should pass cart icon stroke width');
assert.ok(hookRegistry.includes('ShopLoopCartIconHost'), 'HookRegistry should register ShopLoopCartIconHost');
assert.ok(
	dynamicStyles.includes('enqueue_catalog_cart_icon_vars_on_main_stylesheet'),
	'DynamicStylesProvider should duplicate catalog cart icon vars on main stylesheet'
);
assert.ok(
	dynamicStyles.includes('print_footer_catalog_cart_icon_late_style'),
	'DynamicStylesProvider should print late footer style for catalog cart icon'
);
assert.ok(js.includes('imageClickBehavior') && js.includes('theme_default'), 'JS should respect imageClickBehavior');
assert.ok(js.includes('catalogAddSurface') && js.includes('cart_icon'), 'JS should handle catalogAddSurface');
assert.ok(js.includes('mp-scc-catalog-cart-icon-slot') && js.includes('attachCatalogCartIconPointerGuards'), 'JS should use cart icon slot + pointer guards');
assert.ok(js.includes('applyCatalogCartIconMobileModeAttr') && js.includes('data-mp-scc-cart-icon-mobile-mode'), 'JS should apply mobile cart icon mode on html');
assert.ok(js.includes('catalogCartIconPresetInners') && js.includes('__MP_SCC_SW__'), 'JS should build cart SVG from preset inners + stroke');
assert.ok(js.includes('catalogCartIconStrokeWidth'), 'JS should use catalogCartIconStrokeWidth for built-in SVG');
assert.ok(
	js.includes('--mp-scc-catalog-cart-icon-border-radius') && js.includes('border-radius'),
	'JS should stamp catalog cart icon border radius from css vars'
);
assert.ok(
	js.includes('--mp-scc-catalog-cart-icon-color-hover'),
	'JS should apply inverted icon color on cart button hover'
);
assert.ok(js.includes('--mp-scc-catalog-cart-icon-inner-padding'), 'JS should stamp catalog cart icon inner padding');
assert.ok(js.includes('applyCatalogCartIconCssVarsFromPayload'), 'JS should apply catalog cart icon CSS vars from payload');
assert.ok(js.includes('stampOneCatalogCartIconButton'), 'JS should stamp cart icon button colors from payload');
assert.ok(
	js.includes('resolveCatalogImageFromClickTarget') && js.includes('cart_icon') && js.includes('return null'),
	'JS should skip image resolution when cart_icon (defense in depth)'
);
assert.ok(css.includes('mp-scc-admin-catalog-preview'), 'Admin CSS should style catalog preview');
assert.ok(
	css.includes('--mp-scc-catalog-cart-icon-color'),
	'Admin catalog preview should use catalog cart icon color variable'
);
assert.ok(css.includes('mp-scc-cart-icon-preset-grid'), 'Admin CSS should style cart icon preset grid');
assert.ok(
	cssContract.includes('catalog-cart-icon-border-radius') &&
		cssContract.includes('catalog_cart_icon_bg_border_radius_px'),
	'CssVariablesContract should map bg border radius to --mp-scc-catalog-cart-icon-border-radius'
);
assert.ok(
	cssContract.includes('catalog-cart-icon-inner-padding') &&
		cssContract.includes('catalog_cart_icon_inner_padding_px'),
	'CssVariablesContract should map inner padding to --mp-scc-catalog-cart-icon-inner-padding'
);

console.log('catalog-tab-dp: OK');
