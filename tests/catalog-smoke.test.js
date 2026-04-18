#!/usr/bin/env node
/**
 * Smoke tests for catalog URL heuristics (mirrors assets/js/frontend.js — hrefLooksLikeProductPage).
 * Run: node tests/catalog-smoke.test.js
 */
'use strict';

var assert = require('assert');

function hrefLooksLikeProductPage(href, productId, baseHref) {
	if (!href || typeof href !== 'string') {
		return false;
	}
	var trimmed = href.trim();
	if (
		!trimmed ||
		trimmed.indexOf('javascript:') === 0 ||
		trimmed.indexOf('data:') === 0 ||
		trimmed === '#'
	) {
		return false;
	}
	try {
		var u = new URL(trimmed, baseHref);
		if (u.protocol !== 'http:' && u.protocol !== 'https:') {
			return false;
		}
		var qp = u.searchParams.get('p');
		if (qp && productId > 0 && parseInt(qp, 10) === productId) {
			return true;
		}
		if (/\/product\//i.test(u.pathname) || /\/shop\//i.test(u.pathname)) {
			return true;
		}
		if (u.pathname && u.pathname !== '/' && u.pathname.toLowerCase().indexOf('add-to-cart') === -1) {
			return true;
		}
		return false;
	} catch (err) {
		return false;
	}
}

var base = 'https://example.com/shop/';

assert.strictEqual(hrefLooksLikeProductPage('/product/foo/', 0, base), true);
assert.strictEqual(hrefLooksLikeProductPage('/shop/foo/', 0, base), true);
assert.strictEqual(hrefLooksLikeProductPage('/?p=42', 42, base), true);
assert.strictEqual(hrefLooksLikeProductPage('/?p=99', 42, base), false);
assert.strictEqual(hrefLooksLikeProductPage('javascript:void(0)', 1, base), false);
assert.strictEqual(hrefLooksLikeProductPage('#', 0, base), false);

console.log('catalog-smoke: OK');
