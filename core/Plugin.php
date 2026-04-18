<?php
/**
 * Plugin bootstrap and entry point.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

use MpStickyCustomCart\Admin\AdminModule;

defined( 'ABSPATH' ) || exit;

/**
 * Registers hooks and loads the plugin runtime.
 */
final class Plugin {

	/**
	 * Register activation/deactivation and boot the plugin on `plugins_loaded`.
	 */
	public static function register() {
		register_activation_hook( MP_STICKY_CUSTOM_CART_FILE, array( Activator::class, 'activate' ) );
		register_deactivation_hook( MP_STICKY_CUSTOM_CART_FILE, array( Deactivator::class, 'deactivate' ) );

		OptionMigrationHandler::register();

		WooCommerceGate::register();

		add_action( 'plugins_loaded', array( self::class, 'init' ), 10 );
	}

	/**
	 * Load text domain and wire runtime modules (extended in later tasks).
	 */
	public static function init() {
		load_plugin_textdomain(
			Constants::TEXT_DOMAIN,
			false,
			dirname( MP_STICKY_CUSTOM_CART_BASENAME ) . '/languages'
		);

		if ( ! get_option( Constants::OPTION_DIAG_CAP_BOOT ) ) {
			Activator::ensure_diagnostics_capability();
			update_option( Constants::OPTION_DIAG_CAP_BOOT, '1', false );
		}

		if ( is_admin() ) {
			AdminModule::register();
		}

		/**
		 * Fires after the plugin has bootstrapped (text domain loaded).
		 */
		do_action( 'mp_sticky_custom_cart_init' );
	}
}
