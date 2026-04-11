<?php
/**
 * Ensures WooCommerce is present before cart-related features run.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Runs after most plugins; shows an admin notice if WooCommerce is missing.
 */
final class WooCommerceGate {

	/**
	 * Late `plugins_loaded` so WooCommerce core class is available when active.
	 */
	public static function register() {
		add_action( 'plugins_loaded', array( self::class, 'boot' ), 20 );
	}

	/**
	 * Boot cart hooks or warn in wp-admin.
	 */
	public static function boot() {
		if ( ! self::is_woocommerce_active() ) {
			add_action( 'admin_notices', array( self::class, 'render_missing_wc_notice' ) );
			return;
		}

		HookRegistry::register();
	}

	/**
	 * Whether WooCommerce is loaded and usable.
	 */
	public static function is_woocommerce_active() {
		$active = class_exists( '\WooCommerce', false ) && function_exists( 'WC' );

		/**
		 * Filters whether WooCommerce is considered active for this plugin.
		 *
		 * @param bool $active Default detection result.
		 */
		return (bool) apply_filters( 'mp_sticky_custom_cart_is_woocommerce_active', $active );
	}

	/**
	 * Admin notice when the shop dependency is missing.
	 */
	public static function render_missing_wc_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo esc_html(
			sprintf(
				/* translators: %s: WooCommerce plugin name */
				__( 'MP Sticky Custom Cart requires %s to be installed and active.', 'mp-sticky-custom-cart' ),
				'WooCommerce'
			)
		);
		echo '</p></div>';
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
