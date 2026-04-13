<?php
/**
 * Admin-only bootstrap (settings UI does not require WooCommerce runtime).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Registers settings API, settings screen, assets, and tab reset handler.
 */
final class AdminModule {

	public static function register() {
		SettingsApiHooks::register();
		SettingsPage::register();
		SettingsTabResetHandler::register();
		ErrorLogPurgeHandler::register();
		ErrorLogAdminHooks::register();
		AdminAssetsHooks::register();

		/**
		 * Fires after admin module hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_admin_module_registered' );
	}

	private function __construct() {
	}
}
