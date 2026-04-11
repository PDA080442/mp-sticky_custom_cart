<?php
/**
 * Plugin activation callback.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Runs once when the plugin is activated.
 */
final class Activator {

	/**
	 * Activation hook handler.
	 */
	public static function activate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		/**
		 * Fires after plugin activation (capabilities already checked).
		 */
		do_action( 'mp_sticky_custom_cart_activated' );
	}
}
