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
var ajaxHooks = fs.readFileSync(path.join(root, 'frontend', 'AjaxEndpointsHooks.php'), 'utf8');
var constants = fs.readFileSync(path.join(root, 'core', 'Constants.php'), 'utf8');

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

// Hub/Liquid: wishlist lives in `.ld-sp-btns` which is opacity:0 until hover + display:none on narrow phones.
assert.ok(
	css.includes('.ld-sp-btns') &&
		css.includes('opacity: 1 !important') &&
		css.includes('display: flex !important') &&
		/@media \(max-width: 768px\), \(pointer: coarse\)/.test(css),
	'mobile wishlist fix should force .ld-sp-btns visible under coarse pointer / narrow viewport'
);

// YITH catalog UX: in-list colors + card state marker + JS toggle + server remove endpoint.
assert.ok(
	css.includes('data-mp-scc-wishlist') &&
		css.includes('--mp-scc-wishlist-heart-in-list-bg') &&
		css.includes('--mp-scc-wishlist-heart-in-list-color'),
	'CSS should style in-wishlist heart via data-mp-scc-wishlist and in-list CSS vars'
);
assert.ok(
	contract.includes('wishlist_ui.heart_in_wishlist_bg_color') &&
		contract.includes('wishlist_ui.heart_in_wishlist_icon_color'),
	'CssVariablesContract should map wishlist_ui in-list colors to CSS custom properties'
);
assert.ok(
	js.includes('initYithWishlistCatalogBehavior') &&
		js.includes('syncWishlistStateAttrOnCard') &&
		js.includes('scanWishlistStateOnAllCatalogCards') &&
		js.includes('.postAjax(\'yithRemoveFromWishlist\''),
	'frontend.js should implement YITH catalog state sync and remove AJAX'
);
assert.ok(
	js.includes('ensureSvgHeartInsideYithContainer') && js.includes('mp-scc-wl-glyph'),
	'frontend.js should inject inline-SVG heart into managed YITH anchors'
);
assert.ok(
	/svg\.mp-scc-wl-glyph/.test(css),
	'CSS should style the JS-injected SVG heart (mp-scc-wl-glyph)'
);
assert.ok(
	constants.includes('AJAX_ACTION_YITH_REMOVE_FROM_WISHLIST') &&
		constants.includes('mp_scc_yith_remove_from_wishlist'),
	'Constants should declare YITH remove AJAX action name'
);
assert.ok(
	ajaxHooks.includes('handle_yith_remove_from_wishlist') &&
		ajaxHooks.includes('yith_wcwl_items'),
	'AjaxEndpointsHooks should register YITH table remove handler'
);

console.log('wishlist-coexistence: OK');
