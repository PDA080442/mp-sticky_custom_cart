<?php
/**
 * Plugin deactivation callback.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Runs once when the plugin is deactivated.
 */
final class Deactivator {

	/**
	 * Deactivation hook handler.
	 */
	public static function deactivate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		/**
		 * Fires after plugin deactivation (capabilities already checked).
		 */
		do_action( 'mp_sticky_custom_cart_deactivated' );
	}
}
