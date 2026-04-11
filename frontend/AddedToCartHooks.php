<?php
/**
 * Bridges WooCommerce add-to-cart with sticky UI updates.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * PHP `woocommerce_add_to_cart` and JS event hooks for fragments/sync.
 */
final class AddedToCartHooks {

	public static function register() {
		add_action( 'woocommerce_add_to_cart', array( self::class, 'on_add_to_cart' ), 10, 6 );

		add_action( 'wp_enqueue_scripts', array( self::class, 'register_script_data' ), 25 );

		/**
		 * Fires when WooCommerce add-to-cart listeners are registered.
		 */
		do_action( 'mp_sticky_custom_cart_added_to_cart_hooks_registered' );
	}

	/**
	 * Server-side hook after a product is added to the cart.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $product_id    Product ID.
	 * @param int    $quantity      Quantity.
	 * @param int    $variation_id  Variation ID.
	 * @param array  $variation     Variation data.
	 * @param array  $cart_item_data Extra cart data.
	 */
	public static function on_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		/**
		 * Fires when WooCommerce has added an item (for sticky cart sync).
		 *
		 * @param string $cart_item_key Cart item key.
		 * @param int    $product_id    Product ID.
		 * @param int    $quantity      Quantity added.
		 */
		do_action( 'mp_sticky_custom_cart_woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity );
	}

	/**
	 * Placeholder for localized listeners (full script in assets task).
	 */
	public static function register_script_data() {
		/**
		 * Fires when front scripts should subscribe to Woo `added_to_cart` JS event.
		 */
		do_action( 'mp_sticky_custom_cart_register_added_to_cart_script' );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
