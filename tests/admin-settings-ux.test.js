#!/usr/bin/env node
/**
 * Admin settings UX: tabs, notices, reset tab, unsaved script, Plugin bootstrap.
 * Run: node tests/admin-settings-ux.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var root = path.join(__dirname, '..');
var plugin = fs.readFileSync(path.join(root, 'core', 'Plugin.php'), 'utf8');
var adminModule = fs.readFileSync(path.join(root, 'admin', 'AdminModule.php'), 'utf8');
var settingsPage = fs.readFileSync(path.join(root, 'admin', 'SettingsPage.php'), 'utf8');
var resetHandler = fs.readFileSync(path.join(root, 'admin', 'SettingsTabResetHandler.php'), 'utf8');
var sanitizer = fs.readFileSync(path.join(root, 'admin', 'SettingsSanitizer.php'), 'utf8');
var assets = fs.readFileSync(path.join(root, 'admin', 'AdminAssetsHooks.php'), 'utf8');
var adminJs = fs.readFileSync(path.join(root, 'admin', 'js', 'settings-page.js'), 'utf8');
var registry = fs.readFileSync(path.join(root, 'core', 'HookRegistry.php'), 'utf8');

assert.ok(plugin.includes('AdminModule::register'), 'Plugin should bootstrap AdminModule in admin');
assert.ok(adminModule.includes('SettingsPage::register'), 'AdminModule should register SettingsPage');
assert.ok(adminModule.includes('SettingsTabResetHandler::register'), 'AdminModule should register tab reset handler');
assert.ok(!registry.includes('SettingsPage::register'), 'HookRegistry should not duplicate SettingsPage (admin loads without WC)');
assert.ok(settingsPage.includes('tab_intro_descriptions'), 'Settings page should define tab intro copy');
assert.ok(settingsPage.includes('render_admin_notices'), 'Settings page should render save/reset notices');
assert.ok(settingsPage.includes('preserve_settings_tab_in_redirect'), 'Settings should preserve tab on options.php redirect');
assert.ok(settingsPage.includes('mp_scc_active_tab'), 'Form should post active tab for redirect merge');
assert.ok(settingsPage.includes('mp-scc-settings-form'), 'Main form should have class for dirty-state script');
assert.ok(settingsPage.includes('mp-scc-style-live-preview'), 'Styles tab should render live preview anchor');
assert.ok(settingsPage.includes('data-mp-scc-preview-baseline'), 'Sticky preview should store baseline for revert');
assert.ok(settingsPage.includes('mp-scc-reset-tab-form'), 'Reset form should be separate from options form');
	assert.ok(
		settingsPage.includes('ERROR_LOG_PURGE_FORM_ID') &&
			settingsPage.includes("'form'") &&
			settingsPage.includes('self::ERROR_LOG_PURGE_FORM_ID'),
		'Error log purge must use external form + form= on submit (no nested form inside options.php)'
	);
assert.ok(settingsPage.includes('help_tip_button'), 'Settings should support contextual help tooltips');
assert.ok(resetHandler.includes("admin_post_' . self::ACTION") && resetHandler.includes('mp_scc_reset_settings_tab'), 'Reset handler should use admin-post action');
assert.ok(resetHandler.includes('reset_styles_tab_appearance'), 'Reset handler should reset styles tab appearance keys');
assert.ok(sanitizer.includes('Tabbed UI'), 'Sanitizer should skip missing posted fields for tabbed settings');
assert.ok(assets.includes('settings-page.js'), 'Admin assets should enqueue settings-page.js');
assert.ok(assets.includes('mpSccAdmin'), 'Admin script should localize beforeUnload string');
assert.ok(adminJs.includes('beforeunload.mpSccAdmin'), 'Admin JS should warn on unsaved leave');
assert.ok(adminJs.includes('mp-scc-form-dirty'), 'Admin JS should toggle dirty class');
assert.ok(adminJs.includes('initStyleLivePreview'), 'Admin JS should define style live preview');
assert.ok(adminJs.includes('data-mp-scc-preview'), 'Admin JS should bind preview from data-mp-scc-preview');
assert.ok(adminJs.includes('mp-scc-style-live-preview'), 'Admin JS should target style preview id');
assert.ok(assets.includes('stylePreview'), 'Admin assets should localize stylePreview config');
assert.ok(assets.includes('wp-color-picker'), 'Admin assets should load WP color picker for palette UX');
assert.ok(adminJs.includes('wpColorPicker'), 'Admin JS should init wpColorPicker on palette fields');
assert.ok(adminJs.includes('typography_scale'), 'Admin JS should handle typography scale preview');
assert.ok(adminJs.includes('font_family'), 'Admin JS should handle font_family preview');
assert.ok(adminJs.includes('initHelpTipPopovers'), 'Admin JS should open help tips on click');
assert.ok(adminJs.includes('mp-scc-help-popover'), 'Admin JS should render help popover');
assert.ok(settingsPage.includes('font_family_preset'), 'Styles tab should expose font_family_preset');
assert.ok(settingsPage.includes('render_font_family_examples_line'), 'Settings should show font stack examples helper');
assert.ok(settingsPage.includes('Montserrat, sans-serif'), 'Settings should document Montserrat example');
assert.ok(settingsPage.includes('CQ 91–93'), 'Cart tab should document cq 91-93 mapping for visibility policy');
assert.ok(settingsPage.includes('tristate_custom_css_global'), 'Cart tab should expose advanced tri-state custom CSS fields');
assert.ok(settingsPage.includes('mp-scc-tristate-css-cheatsheet'), 'Cart tab should expose tri-state CSS selector cheatsheet');
assert.ok(
	settingsPage.includes('visibility_show_on_all_templates') &&
		settingsPage.includes('visibility_excluded_urls') &&
		settingsPage.includes('field_textarea'),
	'Cart tab should expose visibility policy toggle + URL exclusions textarea'
);

console.log('admin-settings-ux: OK');
