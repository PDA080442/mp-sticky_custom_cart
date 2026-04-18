# Catalog loop add — behavior baseline (regression)

This document fixes **expected behavior** for QA and migrations. Server: WooCommerce loop; client: `assets/js/frontend.js` + `mpSccData.catalog`.

## Feature flag `product_image_add_to_cart` (JS key `product_image_add_to_cart`)

| Flag | Effect |
|------|--------|
| Off | No plugin loop add: no image capture path, no cart icon injection, no desktop ATC hit layer. Theme/Woo buttons may still work on their own. |
| On | Loop add allowed **if** AJAX `addSimpleProduct` is configured and the relevant surface / `image_click_behavior` permits it. |

## Setting `catalog.image_click_behavior`

| Value | Meaning |
|-------|---------|
| `add_to_cart` | Plugin may handle loop add (subject to surface + flag). |
| `theme_default` | Plugin does **not** attach image-click handling. Cart icon mode is **unaffected** by this key for the icon button (icon still works when flag is on and surface is `cart_icon`). |

## Setting `catalog.catalog_add_surface`

| Value | Thumbnail | Cart icon | Desktop ATC hit layer |
|-------|-----------|-----------|------------------------|
| `image_click` | Intercepted when `image_click_behavior === 'add_to_cart'` and flag on (capture on `window`). | N/A | Active when `image_click_behavior === 'add_to_cart'`, flag on, desktop breakpoint. |
| `cart_icon` | **Not** intercepted by plugin (theme links apply). | Slot + button: standard Woo loop may include a PHP host (`ShopLoopCartIconHost` → `woocommerce_before_shop_loop_item`); otherwise JS injects the slot. Simple products only; same AJAX as image path. | **Disabled** (avoid duplicate add zones). |

## Cart icon visibility (`catalog_cart_icon_desktop` / `catalog_cart_icon_touch`)

- **Desktop branch** (CSS: `min-width: 769px` and `pointer: fine`): `hover` hides the icon until the card is hovered, focused (`:focus-within`), or has `.mp-scc-card--hover-intent`; `always` keeps it visible.
- **Touch / narrow branch** (CSS: `max-width: 768px` or `pointer: coarse`): `always` keeps the icon visible; `tap_reveal` hides until the card gets class `mp-scc-catalog-cart-icon--revealed-touch` (first `pointerup` on the card that is not on a link/button/control — see `initCatalogCartIconTouchReveal`).
- **Stacking**: slot `z-index` is `catalog_overlay_z_index + 1`; with wishlist integration, `wishlist_icon_z_index - 1` so the heart stays above.
- **Pointer guards**: `mousedown` / `touchstart` on the icon use capture `stopPropagation` so the click is not handled as a thumbnail/navigation hit.

## Telemetry (optional)

When **Diagnostics → client logging** is enabled, successful loop adds may log `catalog_loop_add_success` with `detail` containing `surface=image|cart_icon|atc_hit`. Stock guardrails may log `catalog_image_out_of_stock` with `context` describing the path.

## Wrap loop item (`catalog.wrap_loop_item_add_to_cart`)

PHP: `Frontend\ShopLoopAddToCartWrapper` only on standard Woo hooks. Neither surface changes that contract; JS still finds the card via `cardRootSelector` / fallbacks and resolves `product_id` from the card DOM.
