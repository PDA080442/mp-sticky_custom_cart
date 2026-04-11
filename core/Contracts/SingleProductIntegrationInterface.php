<?php
/**
 * Single product template: sync add-to-cart with sticky cart and variation guards.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks into single product forms and WooCommerce events.
 */
interface SingleProductIntegrationInterface {

	/**
	 * Register scripts and WooCommerce event listeners for the product page.
	 */
	public function register();

	/**
	 * Whether integration runs on this product view.
	 */
	public function is_enabled_for_request();

	/**
	 * Client-readable message when a variable product needs a variation (from labels/settings).
	 *
	 * @return string
	 */
	public function get_variation_required_message();
}
