<?php
/**
 * AJAX endpoints for cart mutations and snapshots.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Registers `wp_ajax_*` / `wp_ajax_nopriv_*` handlers and parses JSON bodies.
 */
interface CartAjaxControllerInterface {

	/**
	 * Register all cart-related AJAX actions and nonces.
	 */
	public function register();

	/**
	 * Verify nonce + capability rules for a given action name.
	 *
	 * @param string $action Arbitrary action id (e.g. add_to_cart).
	 */
	public function verify_request( $action );
}
