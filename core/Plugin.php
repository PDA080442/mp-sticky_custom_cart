<?php
/**
 * Plugin bootstrap and entry point.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

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

		/**
		 * Fires after the plugin has bootstrapped (text domain loaded).
		 */
		do_action( 'mp_sticky_custom_cart_init' );
	}
}
