#!/usr/bin/env node
/**
 * Error log storage + admin API (dp 11.1).
 * Run: node tests/error-log-dp.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var root = path.join(__dirname, '..');
var svc = fs.readFileSync(path.join(root, 'core', 'ErrorLogService.php'), 'utf8');
var hooks = fs.readFileSync(path.join(root, 'admin', 'ErrorLogAdminHooks.php'), 'utf8');
var purge = fs.readFileSync(path.join(root, 'admin', 'ErrorLogPurgeHandler.php'), 'utf8');
var constants = fs.readFileSync(path.join(root, 'core', 'Constants.php'), 'utf8');
var ajax = fs.readFileSync(path.join(root, 'frontend', 'AjaxEndpointsHooks.php'), 'utf8');
var defaults = fs.readFileSync(path.join(root, 'core', 'Config', 'UiSettingsDefaults.php'), 'utf8');
var schema = fs.readFileSync(path.join(root, 'core', 'Config', 'SettingsValidationSchema.php'), 'utf8');
var settingsPage = fs.readFileSync(path.join(root, 'admin', 'SettingsPage.php'), 'utf8');
var adminModule = fs.readFileSync(path.join(root, 'admin', 'AdminModule.php'), 'utf8');
var errorLoggingHooks = fs.readFileSync(path.join(root, 'core', 'ErrorLoggingHooks.php'), 'utf8');
var frontendJs = fs.readFileSync(path.join(root, 'assets', 'js', 'frontend.js'), 'utf8');
var frontendFlagResolver = fs.readFileSync(path.join(root, 'frontend', 'FrontendFlagResolver.php'), 'utf8');
var diagnosticsAccess = fs.readFileSync(path.join(root, 'admin', 'DiagnosticsAccess.php'), 'utf8');
var adminAssets = fs.readFileSync(path.join(root, 'admin', 'AdminAssetsHooks.php'), 'utf8');
var errorLogPanelJs = fs.readFileSync(path.join(root, 'admin', 'js', 'error-log-panel.js'), 'utf8');
var activator = fs.readFileSync(path.join(root, 'core', 'Activator.php'), 'utf8');
var pluginBoot = fs.readFileSync(path.join(root, 'core', 'Plugin.php'), 'utf8');

assert.ok(svc.includes('implements LoggingServiceInterface'), 'ErrorLogService should implement LoggingServiceInterface');
assert.ok(svc.includes('function prune') || svc.includes('private function prune'), 'ErrorLogService should prune logs');
assert.ok(svc.includes('ip_hash'), 'ErrorLogService should store ip hash not raw IP');
assert.ok(hooks.includes('AJAX_ACTION_ADMIN_GET_ERROR_LOGS'), 'Admin hooks should register get-logs action');
assert.ok(purge.includes('ADMIN_POST_PURGE_ERROR_LOG'), 'Purge handler should match constant');
assert.ok(constants.includes('AJAX_ACTION_ADMIN_GET_ERROR_LOGS'), 'Constants should define admin log AJAX action');
assert.ok(ajax.includes('ErrorLogService::instance()->log'), 'Ajax endpoints should use ErrorLogService');
assert.ok(defaults.includes('log_max_entries'), 'Defaults should include log_max_entries');
assert.ok(schema.includes('log_max_bytes'), 'Schema should validate log_max_bytes');
assert.ok(settingsPage.includes('render_error_log_panel'), 'Settings should render error log panel');
assert.ok(adminModule.includes('ErrorLogAdminHooks::register'), 'Admin module should register error log hooks');

assert.ok(errorLoggingHooks.includes('handle_client_log_batch'), 'ErrorLoggingHooks should ingest batched client diagnostics (dp 11.2)');
assert.ok(errorLoggingHooks.includes("'batch'"), 'ErrorLoggingHooks should accept batch POST field');
assert.ok(frontendJs.includes('initClientDiagnostics'), 'frontend should register client diagnostics (dp 11.2)');
assert.ok(frontendJs.includes('unhandledrejection'), 'frontend should listen for unhandledrejection (dp 11.2)');
assert.ok(frontendJs.includes('queueClientDiagnostic'), 'frontend should buffer client diagnostics (dp 11.2)');
assert.ok(frontendJs.includes('shouldSuppressClientGlobalErrorMessage'), 'frontend should filter known third-party JS noise from client log');
assert.ok(frontendJs.includes('inline preloader'), 'frontend should suppress common null.style noise from client log');
assert.ok(frontendFlagResolver.includes("'clientLogging'"), 'FrontendFlagResolver should expose clientLogging (dp 11.2)');

assert.ok(constants.includes('CAPABILITY_MANAGE_DIAGNOSTICS'), 'Constants should define diagnostics capability (dp 11.3)');
assert.ok(constants.includes('AJAX_ACTION_ADMIN_EXPORT_ERROR_LOGS'), 'Constants should define export AJAX action (dp 11.3)');
assert.ok(diagnosticsAccess.includes('mp_sticky_custom_cart_diagnostics_capability'), 'DiagnosticsAccess should expose filterable capability (dp 11.3)');
assert.ok(hooks.includes('handle_export_logs'), 'ErrorLogAdminHooks should register export handler (dp 11.3)');
assert.ok(hooks.includes('DiagnosticsAccess::can_manage'), 'ErrorLogAdminHooks should check diagnostics capability (dp 11.3)');
assert.ok(purge.includes('DiagnosticsAccess::can_manage'), 'Purge handler should use diagnostics capability (dp 11.3)');
assert.ok(settingsPage.includes('mp-scc-error-log-root'), 'Settings should render interactive error log root (dp 11.3)');
assert.ok(adminAssets.includes('error-log-panel.js'), 'Admin assets should enqueue error log panel script (dp 11.3)');
assert.ok(errorLogPanelJs.includes('mp-scc-error-log-drawer'), 'Error log script should implement detail drawer (dp 11.3)');
assert.ok(svc.includes('query_with_meta'), 'ErrorLogService should support filtered pagination (dp 11.3)');
assert.ok(activator.includes('ensure_diagnostics_capability'), 'Activator should grant diagnostics capability (dp 11.3)');
assert.ok(pluginBoot.includes('OPTION_DIAG_CAP_BOOT'), 'Plugin bootstrap should run diagnostics cap migration (dp 11.3)');

console.log('error-log-dp: OK');
