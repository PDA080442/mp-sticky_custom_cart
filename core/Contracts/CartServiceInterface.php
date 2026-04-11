<?php
/**
 * Frontend-oriented cart operations backed by WooCommerce.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Abstraction over {@see \WC_Cart} for AJAX and sticky UI consumers.
 */
interface CartServiceInterface {

	/**
	 * Whether WooCommerce cart is available and usable.
	 */
	public function is_available();

	/**
	 * Build a serializable cart snapshot for JS (lines, subtotals, counts).
	 *
	 * @return array<string, mixed>
	 */
	public function get_snapshot();

	/**
	 * Add a simple product by ID (catalog image click flow).
	 *
	 * @param int $product_id Product ID.
	 * @param int $quantity   Quantity to add.
	 * @return array<string, mixed>|\WP_Error Snapshot on success.
	 */
	public function add_simple_product( $product_id, $quantity = 1 );

	/**
	 * Update line quantity (drawer +/-).
	 *
	 * @param string $cart_item_key WooCommerce cart item key.
	 * @param int    $quantity      New quantity (min 1 before removal).
	 * @return array<string, mixed>|\WP_Error Snapshot on success.
	 */
	public function update_line_quantity( $cart_item_key, $quantity );

	/**
	 * Remove a cart line (drawer remove).
	 *
	 * @param string $cart_item_key WooCommerce cart item key.
	 * @return array<string, mixed>|\WP_Error Snapshot on success.
	 */
	public function remove_line( $cart_item_key );

	/**
	 * Empty the cart (clear action).
	 *
	 * @return array<string, mixed>|\WP_Error Snapshot on success (possibly empty).
	 */
	public function clear();
}
