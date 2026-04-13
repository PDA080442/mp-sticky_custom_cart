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

		self::ensure_diagnostics_capability();

		/**
		 * Fires after plugin activation (capabilities already checked).
		 */
		do_action( 'mp_sticky_custom_cart_activated' );
	}

	/**
	 * Grants {@see Constants::CAPABILITY_MANAGE_DIAGNOSTICS} to the administrator role.
	 */
	public static function ensure_diagnostics_capability() {
		$role = get_role( 'administrator' );
		if ( $role && ! $role->has_cap( Constants::CAPABILITY_MANAGE_DIAGNOSTICS ) ) {
			$role->add_cap( Constants::CAPABILITY_MANAGE_DIAGNOSTICS );
		}
	}
}
