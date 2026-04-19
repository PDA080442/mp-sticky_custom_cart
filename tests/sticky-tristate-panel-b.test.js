#!/usr/bin/env node
/**
 * Tri-state panel B (dp §17.3): markup, reducer A→B→C, CSS vars, admin fields.
 * Run: node tests/sticky-tristate-panel-b.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var shell = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'cart-ui-shell-state.js'), 'utf8');
var renderer = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'StickyCartRenderer.php'), 'utf8');
var css = fs.readFileSync(path.join(__dirname, '..', 'assets', 'css', 'frontend.css'), 'utf8');
var defaults = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'UiSettingsDefaults.php'), 'utf8');
var schema = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'SettingsValidationSchema.php'), 'utf8');
var contract = fs.readFileSync(path.join(__dirname, '..', 'core', 'Config', 'CssVariablesContract.php'), 'utf8');
var admin = fs.readFileSync(path.join(__dirname, '..', 'admin', 'SettingsPage.php'), 'utf8');
var hooks = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'StickyCartRenderHooks.php'), 'utf8');
var dyn = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'DynamicStylesProvider.php'), 'utf8');

assert.ok(renderer.includes('data-mp-scc-shell-panel-b'), 'Renderer should output panel B host');
assert.ok(renderer.includes('mp-scc-shell-panel-b__'), 'Renderer should include panel B structure');
assert.ok(renderer.includes('mp-scc-shell-panel-b__icon-btn'), 'Panel B should expose icon action slots');
assert.ok(renderer.includes('mp-scc-drawer--tristate-c'), 'Renderer should flag tri-state drawer C layout');
assert.ok(renderer.includes('mp-scc-drawer-c__scroll'), 'Renderer should wrap line items in scroll host');
assert.ok(renderer.includes('tristate_icon_actions_toolbar_markup'), 'Renderer should share B/C icon toolbar');
assert.ok(renderer.includes('data-mp-scc-clear-label'), 'Icon clear should expose label for a11y restore');
assert.ok(renderer.includes('data-mp-scc-checkout-label'), 'Icon checkout should expose label for a11y restore');
assert.ok(renderer.includes('mp-scc-tristate-action-svg'), 'Inline SVGs should use shared tristate glyph class');
assert.ok(renderer.includes('show_sticky_bar_summary'), 'Renderer should gate legacy bar strip when tristate+drawer');

assert.ok(shell.includes('return STATES.B') && shell.includes('ACTION.TOGGLE_C'), 'Shell reducer should open B from A when panel exists');
assert.ok(shell.includes('_syncPanelBDom'), 'Shell should sync panel B visibility');
assert.ok(shell.includes('_onPointerDownCapture'), 'Shell should dismiss panel B on outside pointer');
assert.ok(shell.includes('data-mp-scc-sticky-tristate'), 'Shell should honor PHP tristate data attribute on root');

assert.ok(css.includes('mp-scc-shell-panel-b'), 'CSS should style panel B');
assert.ok(css.includes('--mp-scc-tristate-panel-b-max-height'), 'CSS should consume B max-height token');
assert.ok(css.includes('--mp-scc-tristate-panel-c-width'), 'CSS should consume drawer C width token');
assert.ok(css.includes('--mp-scc-tristate-action-icon-hit'), 'CSS should consume tri-state icon hit token');
assert.ok(css.includes('--mp-scc-tristate-action-icon-glyph'), 'CSS should consume tri-state icon glyph token');
assert.ok(css.includes('--mp-scc-tristate-dock-inset-right'), 'CSS should consume tri-state dock inset token');
assert.ok(css.includes('--mp-scc-tristate-z-panel-b'), 'CSS should layer panel B via z-index token');
assert.ok(css.includes('--mp-scc-tristate-column-shadow-alpha'), 'CSS should consume tri-state shadow alpha token');
assert.ok(css.includes('mp-scc-drawer-c__scroll'), 'CSS should style scrollable lines region');

assert.ok(defaults.includes('tristate_panel_b_max_height_px'), 'Defaults should define panel B metrics');
assert.ok(defaults.includes('tristate_panel_c_width_px'), 'Defaults should define panel C width');
assert.ok(defaults.includes('tristate_action_icon_hit_px'), 'Defaults should define tri-state icon hit size');
assert.ok(defaults.includes('tristate_action_icon_glyph_px'), 'Defaults should define tri-state icon glyph size');
assert.ok(defaults.includes('tristate_dock_inset_right_px'), 'Defaults should define tri-state dock inset');
assert.ok(defaults.includes('tristate_mobile_layout_preset'), 'Defaults should define tri-state mobile preset');
assert.ok(schema.includes('tristate_panel_b_max_height_px'), 'Schema should validate panel B metrics');
assert.ok(schema.includes('tristate_panel_c_width_px'), 'Schema should validate panel C width');
assert.ok(schema.includes('tristate_action_icon_hit_px'), 'Schema should validate tri-state icon hit');
assert.ok(schema.includes('tristate_mobile_layout_preset'), 'Schema should validate tri-state mobile preset');
assert.ok(schema.includes('clear_cart_in_progress'), 'Schema should allow clear_cart_in_progress label');
assert.ok(contract.includes('tristate-panel-b-max-height'), 'CssVariablesContract should map panel B vars');
assert.ok(contract.includes('tristate-panel-c-width'), 'CssVariablesContract should map panel C width');
assert.ok(contract.includes('tristate-action-icon-hit'), 'CssVariablesContract should map tri-state icon hit var');
assert.ok(contract.includes('tristate-dock-inset-right'), 'CssVariablesContract should map tri-state dock inset');
assert.ok(dyn.includes('print_footer_tristate_responsive_layer_css'), 'DynamicStylesProvider should inject tri-state responsive CSS');
assert.ok(hooks.includes('mp-scc-tristate-preset--'), 'Body class should expose tri-state mobile preset');

assert.ok(admin.includes('tristate_panel_b_max_height_px'), 'Settings UI should expose panel B fields');
assert.ok(admin.includes('tristate_panel_c_width_px'), 'Settings UI should expose drawer C width');
assert.ok(admin.includes('tristate_action_icon_hit_px'), 'Settings UI should expose tri-state icon hit');
assert.ok(admin.includes('tristate_mobile_layout_preset'), 'Settings UI should expose tri-state mobile preset');

console.log('sticky-tristate-panel-b: OK');
