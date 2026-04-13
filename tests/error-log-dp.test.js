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

console.log('error-log-dp: OK');
