#!/usr/bin/env node
/**
 * Smoke checks for «Подробнее» overlay navigation (Phase 5.3).
 * Run: node tests/more-info-navigation.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var js = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'frontend.js'), 'utf8');

assert.ok(js.includes('applyMoreInfoLinkAttrs'), 'applyMoreInfoLinkAttrs should exist');
assert.ok(
	js.includes("'noopener noreferrer'") || js.includes('"noopener noreferrer"'),
	'should set noopener noreferrer with target blank'
);
assert.ok(js.includes('initCatalogOverlayKeyboard'), 'keyboard helper should exist');
assert.ok(
	js.includes('keydown.mpSccOverlayKb') && js.includes('a.mp-scc-catalog-overlay'),
	'should delegate keydown for overlay links'
);
assert.ok(
	js.includes('moreInfoNewTab') && js.includes('$existing.attr(\'href\')'),
	'should sync href after AJAX/filter refreshes'
);
assert.ok(
	fs.readFileSync(path.join(__dirname, '..', 'assets', 'css', 'frontend.css'), 'utf8').includes('touch-action: manipulation'),
	'overlay CSS should use touch-action: manipulation'
);

console.log('more-info-navigation: OK');
