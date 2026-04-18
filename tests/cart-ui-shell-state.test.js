#!/usr/bin/env node
/**
 * Tri-state cart shell (dp.md §17.1): reducer, wiring, enqueue.
 * Run: node tests/cart-ui-shell-state.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var shell = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'cart-ui-shell-state.js'), 'utf8');
var frontend = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'frontend.js'), 'utf8');
var hooks = fs.readFileSync(path.join(__dirname, '..', 'frontend', 'FrontendAssetsHooks.php'), 'utf8');
var css = fs.readFileSync(path.join(__dirname, '..', 'assets', 'css', 'frontend.css'), 'utf8');

assert.ok(shell.includes('STATES'), 'Shell should export state enum');
assert.ok(shell.includes('ACTION.TOGGLE_C'), 'Shell should define TOGGLE_C');
assert.ok(shell.includes('onStickyPayloadApplied'), 'Shell should notify on snapshot apply');
assert.ok(shell.includes('pagehide'), 'Shell should reset on pagehide (no cross-page persistence)');
assert.ok(shell.includes('mpSccCartUiShell'), 'Shell should attach global API');
assert.ok(shell.includes('modal-open'), 'Shell Escape should defer when theme modal is open');

assert.ok(frontend.includes('mpSccCartUiShell.attachSticky'), 'Frontend should attach cart UI shell');
assert.ok(frontend.includes('ACTION.TOGGLE_C'), 'Drawer toggle should route through shell when present');
assert.ok(frontend.includes('onStickyPayloadApplied'), 'applyPayload should notify shell');

assert.ok(hooks.includes('HANDLE_CART_SHELL'), 'PHP should register cart shell script handle');
assert.ok(hooks.includes('cart-ui-shell-state.js'), 'PHP should enqueue cart-ui-shell-state.js');
assert.ok(hooks.includes("'jquery', self::HANDLE_CART_SHELL") || hooks.includes("'jquery', self::HANDLE_CART_SHELL,"), 'Frontend script should depend on cart shell');

assert.ok(css.includes('data-mp-scc-cart-shell-elevated'), 'CSS should elevate sticky z when shell is active');

console.log('cart-ui-shell-state: OK');
