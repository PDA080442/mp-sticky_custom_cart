# ADR 0001: Catalog loop add surface — image click vs cart icon

## Status

Accepted (implemented in 0.1.25).

## Context

The plugin historically added simple products from the shop loop by **capturing clicks on the product thumbnail** (window capture phase) and calling the same AJAX action as other flows (`addSimpleProduct`). That tied UX to “click the photo,” which conflicts with themes that wrap the image in a permalink, with accessibility expectations, and with the product goal of a **visible, explicit control** (cart icon) instead of an implicit image hit target.

The AJAX endpoint and server-side validation stay unchanged; only the **storefront trigger** and delegated selectors differ.

## Decision

1. Introduce settings key `catalog.catalog_add_surface` with values:
   - `image_click` — legacy: image (and related) hit targets + optional desktop ATC hit layer; behavior still gated by `catalog.image_click_behavior` and feature flag `product_image_add_to_cart`.
   - `cart_icon` — inject a `button.mp-scc-catalog-cart-icon-btn` on each eligible loop card (simple product, resolved ID), positioned over the first image box (top-left). Thumbnail clicks are **not** intercepted by the plugin; theme/Woo link behavior applies to the image.

2. Keep **one** shared client function: `executeCatalogLoopAddSimpleAjax` → `postAjax('addSimpleProduct', …)` (same as before).

3. When `catalog_add_surface === 'cart_icon'`, **do not** register the invisible desktop ATC hit layer (`initCatalogAtcHitLayer` cleanup), to avoid stacking two add affordances on the same image area.

3b. The storefront `window` capture listener handles `cart_icon` **before** the ATC hit and image-resolution paths; helpers `resolveCatalogImageFromClickTarget` / `catalogImageMatchesConfiguredSelector` also no-op when surface is `cart_icon` (defense in depth).

4. Optional client diagnostics: on successful add, emit `catalog_loop_add_success` with `surface` in the payload (`image` | `cart_icon` | `atc_hit`) when `diagnostics.client_error_logging` is enabled.

## Consequences

- Merchants can switch surface without code changes.
- Filters such as `mp_sticky_custom_cart_catalog_image_click_selector` apply to image mode only; cart icon mode does not consult the image click selector for add.
- `ShopLoopAddToCartWrapper` (wrap loop item) remains independent: it affects HTML structure of the loop; both surfaces still resolve `product_id` from the card root via existing JS helpers.

## References

- `assets/js/frontend.js` — `initCatalogCartIconLayer`, `handleCatalogCartIconClick`, `initCatalogImageAddToCart`, `executeCatalogLoopAddSimpleAjax`
- `frontend/ShopLoopCartIconHost.php` — optional empty slot in the standard Woo loop (`woocommerce_before_shop_loop_item`, filter `mp_sticky_custom_cart_print_catalog_cart_icon_host`)
- `frontend/FrontendFlagResolver.php` — `catalogAddSurface`, `catalogCartIconDesktop`, `catalogCartIconTouch`
- `docs/catalog-loop-add-baseline.md` — behavior matrix
