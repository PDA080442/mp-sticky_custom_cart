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
assert.ok(settingsPage.includes('mp-scc-reset-tab-form'), 'Reset form should be separate from options form');
assert.ok(settingsPage.includes('help_tip_button'), 'Settings should support contextual help tooltips');
assert.ok(resetHandler.includes("admin_post_' . self::ACTION") && resetHandler.includes('mp_scc_reset_settings_tab'), 'Reset handler should use admin-post action');
assert.ok(assets.includes('settings-page.js'), 'Admin assets should enqueue settings-page.js');
assert.ok(assets.includes('mpSccAdmin'), 'Admin script should localize beforeUnload string');
assert.ok(adminJs.includes('beforeunload.mpSccAdmin'), 'Admin JS should warn on unsaved leave');
assert.ok(adminJs.includes('mp-scc-form-dirty'), 'Admin JS should toggle dirty class');

console.log('admin-settings-ux: OK');
