<?php
/**
 * Front styles/scripts for sticky cart and catalog integration.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\PluginPaths;

defined( 'ABSPATH' ) || exit;

/**
 * Registers `wp_enqueue_scripts` (implementation filled in asset pipeline task).
 */
final class FrontendAssetsHooks {

	public const HANDLE_SCRIPT = 'mp-scc-frontend';
	public const HANDLE_STYLE  = 'mp-scc-frontend';

	public static function register() {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ), 20 );

		/**
		 * Fires when frontend asset hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_frontend_assets_hooks_registered' );
	}

	/**
	 * Enqueue or register storefront assets.
	 */
	public static function enqueue() {
		wp_enqueue_script(
			self::HANDLE_SCRIPT,
			PluginPaths::url( 'assets/js/frontend.js' ),
			array(),
			MP_STICKY_CUSTOM_CART_ASSET_VERSION,
			true
		);

		FrontendFlagResolver::localize( self::HANDLE_SCRIPT );

		/**
		 * Fires before frontend assets are enqueued.
		 */
		do_action( 'mp_sticky_custom_cart_enqueue_frontend_assets' );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
