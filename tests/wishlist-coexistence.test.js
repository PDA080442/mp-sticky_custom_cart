#!/usr/bin/env node
/**
 * Smoke checks for wishlist + overlay coexistence (Phase 6.x).
 * Run: node tests/wishlist-coexistence.test.js
 */
'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

var root = path.join(__dirname, '..');
var css = fs.readFileSync(path.join(root, 'assets', 'css', 'frontend.css'), 'utf8');
var js = fs.readFileSync(path.join(root, 'assets', 'js', 'frontend.js'), 'utf8');
var contract = fs.readFileSync(path.join(root, 'core', 'Config', 'CssVariablesContract.php'), 'utf8');

assert.ok(css.includes('--mp-scc-catalog-overlay-z-index'), 'overlay should use CSS var for z-index');
assert.ok(css.includes('--mp-scc-wishlist-icon-z-index'), 'wishlist layer should use CSS var');
assert.ok(css.includes('mp-scc-wishlist-integration-on'), 'wishlist rules should depend on body class');
assert.ok(js.includes('initWishlistIntegrationBodyClass'), 'JS should expose initWishlistIntegrationBodyClass');
assert.ok(js.includes('mp-scc-wishlist-integration-on'), 'JS should set body class');
assert.ok(contract.includes('catalog_overlay_z_index') && contract.includes('heart_icon_z_index'), 'contract should map z-index settings');

// Pre-hydration fallback (XStore/Liquid): YITH ships `<div.yith-wcwl-add-to-wishlist> > <a>` before JS wraps it in `> div > a`.
// The theme's circle rule only targets the hydrated shape, so we need to restyle the direct anchor so the heart looks correct on first paint.
assert.ok(
	/\.yith-wcwl-add-to-wishlist\s*>\s*a\b/.test(css),
	'pre-hydration fallback should target .yith-wcwl-add-to-wishlist > a directly'
);
assert.ok(
	css.includes('--mp-scc-wishlist-heart-bg') &&
		css.includes('--mp-scc-wishlist-heart-color') &&
		css.includes('--mp-scc-wishlist-heart-size'),
	'fallback should expose heart color/size tokens for theme customizers'
);
assert.ok(
	/\.yith-wcwl-add-to-wishlist\s*>\s*a:hover/.test(css),
	'fallback should define :hover state that mirrors the theme circle'
);

// Case B ("Related products") places a plain-text label inside the anchor — the circle MUST
// collapse text nodes (font-size: 0 + overflow: hidden) so the label can't leak outside the circle.
assert.ok(
	/\.yith-wcwl-add-to-wishlist\s*>\s*a[^{}]*\{[^{}]*font-size:\s*0/.test(css),
	'fallback should collapse text-only YITH labels via font-size: 0'
);
assert.ok(
	/\.yith-wcwl-add-to-wishlist\s*>\s*a[^{}]*\{[^{}]*overflow:\s*hidden/.test(css),
	'fallback should clip overflowing text labels inside the circle'
);

// Case A restores glyph size on any nested <i> (descendant, not direct-child) so wrapped markup
// like <a><span><i/></span></a> from some YITH builds still renders the FontAwesome heart.
assert.ok(
	/\.yith-wcwl-add-to-wishlist\s*>\s*a\s+i[^{}>]*\{[^{}]*font-size:\s*var\(--mp-scc-wishlist-heart-glyph-size/.test(css),
	'fallback should restore font-size on any <i> descendant of the anchor'
);

// Case B injects a FontAwesome heart via `::before` when the anchor has no <i> descendant.
// We relax `:has(> i)` to `:has(i)` so wrapped markup doesn't stack two hearts on top of each other.
assert.ok(
	/\.yith-wcwl-add-to-wishlist\s*>\s*a:not\(:has\(i\)\)::before/.test(css),
	'fallback should suppress the pseudo heart via :has(i), not just :has(> i)'
);
assert.ok(
	/content:\s*"\\f004"/.test(css),
	'fallback pseudo should use the FontAwesome heart codepoint (\\f004)'
);

// Circle must use `border-radius: 50%` (not `5em`) because `em` resolves against the element's
// own font-size and we zero it out to hide stray YITH text labels ("Добавить в избранное").
assert.ok(
	/\.yith-wcwl-add-to-wishlist\s*>\s*a[^{}]*\{[^{}]*border-radius:\s*50%/.test(css),
	'fallback circle should use border-radius: 50% so font-size: 0 does not collapse 5em to 0'
);
assert.ok(
	!/\.yith-wcwl-add-to-wishlist\s*>\s*a[^{}]*\{[^{}]*border-radius:\s*5em/.test(css),
	'fallback must not fall back to border-radius: 5em (breaks under font-size: 0)'
);

console.log('wishlist-coexistence: OK');
