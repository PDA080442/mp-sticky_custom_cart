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
assert.ok(renderer.includes('mp-scc-drawer-c__top'), 'Renderer should wrap drawer C header (collapse + metrics)');
assert.ok(renderer.includes('data-mp-scc-shell-dismiss'), 'Renderer should expose tri-state dismiss controls');
assert.ok(renderer.includes('tristate_icon_actions_toolbar_markup'), 'Renderer should share B/C icon toolbar');
assert.ok(renderer.includes('data-mp-scc-clear-label'), 'Icon clear should expose label for a11y restore');
assert.ok(renderer.includes('data-mp-scc-checkout-label'), 'Icon checkout should expose label for a11y restore');
assert.ok(renderer.includes('mp-scc-tristate-action-svg'), 'Inline SVGs should use shared tristate glyph class');
assert.ok(renderer.includes('data-mp-scc-toggle-c'), 'Panel B/C should expose explicit control to toggle C');
assert.ok(renderer.includes('data-mp-scc-toggle-c-mode'), 'Toggle control should carry explicit open/close mode');
assert.ok(renderer.includes('mp-scc-tristate-action-svg--chevron-up'), 'Panel B control should use chevron-up icon');
assert.ok(renderer.includes('mp-scc-tristate-action-svg--chevron-down'), 'Panel C control should use chevron-down icon');
assert.ok(renderer.includes('mp-scc-drawer-toggle-badge'), 'State A FAB should render line-count badge');
assert.ok(renderer.includes('data-mp-scc-cart-line-count'), 'Renderer should tag line-count slots for JS sync');
assert.ok(
	renderer.includes('data-mp-scc-cart-qty-count') && renderer.includes('Товаров'),
	'Renderer should show total pieces (Товаров) separate from line count'
);
assert.ok(renderer.includes('show_sticky_bar_summary'), 'Renderer should gate legacy bar strip when tristate+drawer');

assert.ok(shell.includes('return STATES.B') && shell.includes('ACTION.TOGGLE_C'), 'Shell reducer should open B from A when panel exists');
assert.ok(shell.includes('ACTION.OPEN_C') && shell.includes('ACTION.CLOSE_C'), 'Shell reducer should support explicit OPEN_C/CLOSE_C actions');
assert.ok(shell.includes('_syncPanelBDom'), 'Shell should sync panel B visibility');
assert.ok(!shell.includes('_onPointerDownCapture'), 'Shell should not close panel B on outside pointer');
assert.ok(shell.includes('data-mp-scc-sticky-tristate'), 'Shell should honor PHP tristate data attribute on root');

assert.ok(css.includes('.mp-scc-drawer[hidden]') && css.includes('display: none !important'), 'CSS should enforce display:none on drawer[hidden] to prevent display:flex override');
assert.ok(css.includes('.mp-scc-shell-panel-b[hidden]') && css.includes('display: none !important'), 'CSS should enforce display:none on panel-b[hidden] to prevent author CSS override');
assert.ok(css.includes('mp-scc-shell-panel-b'), 'CSS should style panel B');
assert.ok(css.includes('--mp-scc-tristate-panel-b-max-height'), 'CSS should consume B max-height token');
assert.ok(css.includes('--mp-scc-tristate-panel-c-width'), 'CSS should consume drawer C width token');
assert.ok(css.includes('--mp-scc-tristate-panel-c-height'), 'CSS should consume drawer C height token');
assert.ok(css.includes('--mp-scc-tristate-action-icon-hit'), 'CSS should consume tri-state icon hit token');
assert.ok(css.includes('--mp-scc-tristate-action-icon-glyph'), 'CSS should consume tri-state icon glyph token');
assert.ok(css.includes('--mp-scc-tristate-dock-inset-right'), 'CSS should consume tri-state dock inset token');
assert.ok(css.includes('--mp-scc-tristate-dock-inset-left'), 'CSS should consume tri-state dock inset-left token');
assert.ok(css.includes('--mp-scc-tristate-dock-inset-top'), 'CSS should consume tri-state dock inset-top token');
assert.ok(css.includes('--mp-scc-tristate-state-a-dock-inset-right'), 'CSS should consume tri-state state A dock inset');
assert.ok(css.includes('--mp-scc-tristate-z-panel-b'), 'CSS should layer panel B via z-index token');
assert.ok(css.includes('--mp-scc-tristate-column-shadow-alpha'), 'CSS should consume tri-state shadow alpha token');
assert.ok(css.includes('mp-scc-drawer-c__scroll'), 'CSS should style scrollable lines region');
assert.ok(css.includes('--mp-scc-tristate-actions-toolbar-padding-top'), 'CSS should consume tri-state actions toolbar padding vars');
assert.ok(css.includes('--mp-scc-tristate-dismiss-hit'), 'CSS should consume tri-state dismiss hit var');
assert.ok(css.includes('mp-scc-drawer-toggle-badge') && css.includes('#e53935'), 'CSS should style FAB count badge (red with white text)');
assert.ok(css.includes('--mp-scc-tristate-fab-badge-size'), 'CSS should consume FAB badge size token');
assert.ok(css.includes('--mp-scc-tristate-fab-badge-bg'), 'CSS should consume FAB badge bg color token');
assert.ok(css.includes('--mp-scc-tristate-fab-badge-text'), 'CSS should consume FAB badge text color token');

assert.ok(defaults.includes('tristate_panel_b_max_height_px'), 'Defaults should define panel B metrics');
assert.ok(defaults.includes('tristate_panel_c_width_px'), 'Defaults should define panel C width');
assert.ok(defaults.includes('tristate_panel_c_height_px'), 'Defaults should define panel C height');
assert.ok(defaults.includes('tristate_action_icon_hit_px'), 'Defaults should define tri-state icon hit size');
assert.ok(defaults.includes('tristate_action_icon_glyph_px'), 'Defaults should define tri-state icon glyph size');
assert.ok(defaults.includes('tristate_dock_inset_right_px'), 'Defaults should define tri-state dock inset');
assert.ok(defaults.includes('tristate_dock_inset_left_px'), 'Defaults should define tri-state dock inset-left');
assert.ok(defaults.includes('tristate_dock_inset_top_px'), 'Defaults should define tri-state dock inset-top');
assert.ok(defaults.includes('tristate_state_a_dock_inset_right_px'), 'Defaults should define tri-state state A dock inset');
assert.ok(defaults.includes('tristate_fab_badge_size_px'), 'Defaults should define FAB badge size');
assert.ok(defaults.includes('tristate_fab_badge_bg_color'), 'Defaults should define FAB badge background color');
assert.ok(defaults.includes('tristate_actions_toolbar_padding_top_px'), 'Defaults should define tri-state toolbar padding');
assert.ok(defaults.includes('tristate_dismiss_hit_px'), 'Defaults should define tri-state dismiss hit');
assert.ok(defaults.includes('tristate_mobile_layout_preset'), 'Defaults should define tri-state mobile preset');
assert.ok(schema.includes('tristate_panel_b_max_height_px'), 'Schema should validate panel B metrics');
assert.ok(schema.includes('tristate_panel_c_width_px'), 'Schema should validate panel C width');
assert.ok(schema.includes('tristate_panel_c_height_px'), 'Schema should validate panel C height');
assert.ok(schema.includes('tristate_custom_css_global'), 'Schema should validate custom tri-state CSS');
assert.ok(schema.includes('tristate_action_icon_hit_px'), 'Schema should validate tri-state icon hit');
assert.ok(schema.includes('tristate_mobile_layout_preset'), 'Schema should validate tri-state mobile preset');
assert.ok(schema.includes('tristate_dock_inset_left_px'), 'Schema should validate tri-state dock inset-left');
assert.ok(schema.includes('tristate_dock_inset_top_px'), 'Schema should validate tri-state dock inset-top');
assert.ok(schema.includes('tristate_state_a_dock_inset_right_px'), 'Schema should validate tri-state state A dock inset');
assert.ok(schema.includes('tristate_fab_badge_size_px'), 'Schema should validate FAB badge size');
assert.ok(schema.includes('tristate_fab_badge_bg_color'), 'Schema should validate FAB badge colors');
assert.ok(schema.includes('tristate_actions_toolbar_padding_top_px'), 'Schema should validate tri-state toolbar padding');
assert.ok(schema.includes('tristate_dismiss_hit_px'), 'Schema should validate tri-state dismiss hit');
assert.ok(schema.includes('clear_cart_in_progress'), 'Schema should allow clear_cart_in_progress label');
assert.ok(contract.includes('tristate-panel-b-max-height'), 'CssVariablesContract should map panel B vars');
assert.ok(contract.includes('tristate-panel-c-width'), 'CssVariablesContract should map panel C width');
assert.ok(contract.includes('tristate-panel-c-height'), 'CssVariablesContract should map panel C height');
assert.ok(contract.includes('tristate-action-icon-hit'), 'CssVariablesContract should map tri-state icon hit var');
assert.ok(contract.includes('tristate-dock-inset-right'), 'CssVariablesContract should map tri-state dock inset');
assert.ok(contract.includes('tristate-dock-inset-left'), 'CssVariablesContract should map tri-state dock inset-left');
assert.ok(contract.includes('tristate-dock-inset-top'), 'CssVariablesContract should map tri-state dock inset-top');
assert.ok(contract.includes('tristate-state-a-dock-inset-right'), 'CssVariablesContract should map state A dock inset');
assert.ok(contract.includes('tristate-fab-badge-size'), 'CssVariablesContract should map FAB badge size');
assert.ok(contract.includes('tristate-fab-badge-bg'), 'CssVariablesContract should map FAB badge bg color');
assert.ok(contract.includes('tristate-actions-toolbar-padding-top'), 'CssVariablesContract should map tri-state toolbar padding');
assert.ok(contract.includes('tristate-dismiss-hit'), 'CssVariablesContract should map tri-state dismiss vars');
assert.ok(dyn.includes('print_footer_tristate_responsive_layer_css'), 'DynamicStylesProvider should inject tri-state responsive CSS');
assert.ok(dyn.includes('print_footer_tristate_custom_css'), 'DynamicStylesProvider should print advanced tri-state custom CSS');
assert.ok(dyn.includes('tristate-dock-inset-left'), 'Tri-state responsive CSS should use dock inset-left');
assert.ok(hooks.includes('mp-scc-tristate-preset--'), 'Body class should expose tri-state mobile preset');

assert.ok(admin.includes('tristate_panel_b_max_height_px'), 'Settings UI should expose panel B fields');
assert.ok(admin.includes('tristate_panel_c_width_px'), 'Settings UI should expose drawer C width');
assert.ok(admin.includes('tristate_panel_c_height_px'), 'Settings UI should expose drawer C height');
assert.ok(admin.includes('tristate_custom_css_state_a'), 'Settings UI should expose advanced state A CSS');
assert.ok(admin.includes('tristate_action_icon_hit_px'), 'Settings UI should expose tri-state icon hit');
assert.ok(admin.includes('tristate_mobile_layout_preset'), 'Settings UI should expose tri-state mobile preset');
assert.ok(admin.includes('tristate_state_a_dock_inset_right_px'), 'Settings UI should expose tri-state state A dock inset');
assert.ok(admin.includes('tristate_fab_badge_size_px'), 'Settings UI should expose FAB badge sizing');
assert.ok(admin.includes('tristate_fab_badge_bg_color'), 'Settings UI should expose FAB badge colors');
assert.ok(admin.includes('tristate_actions_toolbar_padding_top_px'), 'Settings UI should expose tri-state toolbar padding');
assert.ok(admin.includes('tristate_dismiss_hit_px'), 'Settings UI should expose tri-state dismiss styling');

console.log('sticky-tristate-panel-b: OK');
