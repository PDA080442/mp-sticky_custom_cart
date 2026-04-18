/**
 * Smoke: config export/import wiring.
 * Run: node tests/config-import-export.test.js
 */

const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const constants = fs.readFileSync(path.join(root, 'core', 'Constants.php'), 'utf8');
const handler = fs.readFileSync(path.join(root, 'admin', 'ConfigImportExportHandler.php'), 'utf8');
const sanitizer = fs.readFileSync(path.join(root, 'admin', 'SettingsSanitizer.php'), 'utf8');

const assert = require('assert');

assert.ok(constants.includes("ADMIN_POST_EXPORT_CONFIG = 'mp_scc_export_config'"), 'Constants should define export action');
assert.ok(constants.includes("ADMIN_POST_IMPORT_CONFIG = 'mp_scc_import_config'"), 'Constants should define import action');
assert.ok(constants.includes('CONFIG_IMPORT_MAX_BYTES'), 'Constants should cap import size');
assert.ok(handler.includes('sanitize_settings_for_import'), 'Handler should use settings import sanitizer');
assert.ok(handler.includes('sanitize_feature_flags_for_import'), 'Handler should use flags import sanitizer');
assert.ok(sanitizer.includes('function sanitize_settings_for_import'), 'SettingsSanitizer should expose import merge');
assert.ok(sanitizer.includes('function sanitize_feature_flags_for_import'), 'SettingsSanitizer should expose flags import');

console.log('config-import-export: OK');
