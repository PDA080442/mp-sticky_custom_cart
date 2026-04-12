<?php
/**
 * AJAX actions for cart snapshot and mutations.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Registers `wp_ajax_*` / `wp_ajax_nopriv_*` handlers (logic in controller task).
 */
final class AjaxEndpointsHooks {

	public static function register() {
		add_action( 'wp_ajax_' . Constants::AJAX_ACTION_CART_SNAPSHOT, array( self::class, 'stub_snapshot' ) );
		add_action( 'wp_ajax_nopriv_' . Constants::AJAX_ACTION_CART_SNAPSHOT, array( self::class, 'stub_snapshot' ) );

		/**
		 * Fires when AJAX endpoint hooks are registered — attach real handlers here.
		 */
		do_action( 'mp_sticky_custom_cart_ajax_endpoints_registered' );
	}

	/**
	 * Temporary 403 until {@see CartAjaxControllerInterface} is implemented.
	 */
	public static function stub_snapshot() {
		wp_die( '', '', array( 'response' => 403 ) );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
