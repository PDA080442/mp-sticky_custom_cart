#!/usr/bin/env node
/**
 * Woo cart DOM events → debounced snapshot refresh, fallback, failure burst logging.
 * Run: node tests/woo-sync-events.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var js = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'frontend.js'), 'utf8');

assert.ok(js.includes('scheduleRefreshFromWooEvent'), 'Sticky should debounce Woo-driven refreshes');
assert.ok(js.includes('added_to_cart'), 'Sticky should subscribe to added_to_cart');
assert.ok(js.includes('wc_fragments_refreshed'), 'Sticky should subscribe to wc_fragments_refreshed');
assert.ok(js.includes('wc_fragments_loaded'), 'Sticky should subscribe to wc_fragments_loaded');
assert.ok(js.includes('updated_wc_div'), 'Sticky should subscribe to updated_wc_div (theme/Divi fragments)');
assert.ok(js.includes('_armWooSyncFallback'), 'Sticky should arm fallback snapshot after Woo event');
assert.ok(js.includes('WOO_CART_SYNC_FALLBACK_MS'), 'Sticky should use fallback delay constant');
assert.ok(js.includes('WOO_CART_EVENT_DEBOUNCE_MS'), 'Sticky should debounce Woo event cascade');
assert.ok(js.includes('_lastSnapshotAppliedAt'), 'Sticky should track last successful snapshot apply time');
assert.ok(js.includes('woo_cart_snapshot_sync_fail_burst'), 'Sticky should log snapshot failure bursts');
assert.ok(js.includes('_recordSnapshotSyncFailure'), 'Sticky should record snapshot AJAX/response failures');

console.log('woo-sync-events: OK');
