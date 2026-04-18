#!/usr/bin/env node
/**
 * QA smoke checks for catalog overlay hover animation (Phase 5.2).
 * Run: node tests/catalog-hover-animation.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var root = path.join(__dirname, '..');
var cssPath = path.join(root, 'assets', 'css', 'frontend.css');
var contractPath = path.join(root, 'core', 'Config', 'CssVariablesContract.php');

var css = fs.readFileSync(cssPath, 'utf8');
var contract = fs.readFileSync(contractPath, 'utf8');

assert.ok(
	css.includes('--mp-scc-catalog-hover-duration-ms'),
	'frontend.css should use --mp-scc-catalog-hover-duration-ms for motion duration'
);
assert.ok(
	css.includes('--mp-scc-catalog-hover-easing'),
	'frontend.css should use --mp-scc-catalog-hover-easing'
);
assert.ok(
	css.includes('--mp-scc-catalog-hover-slide-y'),
	'frontend.css should use --mp-scc-catalog-hover-slide-y for slide offset'
);
assert.ok(
	css.includes('--mp-scc-catalog-hover-hide-delay-ms'),
	'frontend.css should use --mp-scc-catalog-hover-hide-delay-ms for fade-out delay'
);
assert.ok(
	css.includes('transition-delay') && css.includes('prefers-reduced-motion'),
	'frontend.css should tie delays and include reduced-motion handling'
);
assert.ok(
	contract.includes('catalog.hover_animation_duration_ms') &&
		contract.includes('catalog.hover_slide_offset_px') &&
		contract.includes('catalog.hover_hide_delay_ms'),
	'CssVariablesContract should map catalog hover settings to CSS variables'
);

console.log('catalog-hover-animation QA: OK');
