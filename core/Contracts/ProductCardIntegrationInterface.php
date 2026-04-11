<?php
/**
 * Catalog product card: image add-to-cart, overlay, wishlist coexistence.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks into shop/archive loops and enqueues card-level behavior.
 */
interface ProductCardIntegrationInterface {

	/**
	 * Register `wp_enqueue_scripts`, loop hooks, and delegated click handlers.
	 */
	public function register();

	/**
	 * Whether this integration should run on the current screen.
	 */
	public function is_enabled_for_request();

	/**
	 * Theme-specific selector for the product image node (may come from settings).
	 *
	 * @return string CSS selector.
	 */
	public function get_image_selector();
}
