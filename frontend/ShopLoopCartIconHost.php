<?php
/**
 * Optional anchor in the standard WooCommerce shop loop for the catalog cart icon slot.
 *
 * JS still injects the button when the host is missing (Elementor / custom loops without this hook).
 * Output is an empty container only when {@see catalog.catalog_add_surface} is `cart_icon`.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Emits {@see woocommerce_before_shop_loop_item} markup for {@see initCatalogCartIconLayer} in frontend.js.
 */
final class ShopLoopCartIconHost {

	/**
	 * @return void
	 */
	public static function register() {
		add_action( 'woocommerce_before_shop_loop_item', array( __CLASS__, 'maybe_render_host' ), 2 );
	}

	/**
	 * Echoes an empty slot as the first hookable node inside the loop item (standard Woo loop only).
	 *
	 * @return void
	 */
	public static function maybe_render_host() {
		if ( is_admin() ) {
			return;
		}

		$catalog = OptionResolver::get_by_path( OptionResolver::get_settings(), 'catalog', array() );
		$surface = isset( $catalog['catalog_add_surface'] ) ? (string) $catalog['catalog_add_surface'] : 'image_click';
		if ( 'cart_icon' !== $surface ) {
			return;
		}

		/**
		 * Filters whether to print the cart icon host div in the shop loop.
		 *
		 * @param bool $print Default true when surface is cart_icon.
		 */
		if ( ! apply_filters( 'mp_sticky_custom_cart_print_catalog_cart_icon_host', true ) ) {
			return;
		}

		echo '<div class="' . esc_attr( 'mp-scc-catalog-cart-icon-slot mp-scc-catalog-cart-icon-slot--php' ) . '" data-mp-scc-cart-icon-slot="1" aria-hidden="true"></div>';
	}
}
