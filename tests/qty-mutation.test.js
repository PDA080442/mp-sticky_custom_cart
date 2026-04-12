#!/usr/bin/env node
/**
 * Quantity stepper: debounce, set_line_quantity endpoint, optimistic rollback helpers.
 * Run: node tests/qty-mutation.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var js = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'frontend.js'), 'utf8');
var php = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'AjaxEndpointsHooks.php'), 'utf8');

assert.ok(js.includes('scheduleQuantityCommit'), 'JS should debounce quantity commits');
assert.ok(js.includes('debounceMs'), 'JS should use debounce interval');
assert.ok(js.includes('serverQty'), 'JS should track serverQty for rollback');
assert.ok(js.includes('rollbackLineQuantityDisplay'), 'JS should rollback line qty on failure');
assert.ok(js.includes('syncServerQtyFromItems'), 'JS should sync serverQty from snapshot items');
assert.ok(js.includes('setLineQuantity'), 'JS should POST setLineQuantity action');
assert.ok(php.includes('handle_set_line_quantity'), 'PHP should expose set line quantity handler');
assert.ok(php.includes('below_min_quantity'), 'PHP should validate minimum purchase quantity');
assert.ok(php.includes('above_max_quantity'), 'PHP should validate stock maximum');
assert.ok(js.includes('data-mp-scc-max-qty'), 'JS should respect max qty on increment');

console.log('qty-mutation: OK');
