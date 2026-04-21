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

	public const HANDLE_SCRIPT      = 'mp-scc-frontend';
	public const HANDLE_CART_SHELL  = 'mp-scc-cart-ui-shell';
	public const HANDLE_STYLE       = 'mp-scc-frontend';

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
			self::HANDLE_CART_SHELL,
			PluginPaths::url( 'assets/js/cart-ui-shell-state.js' ),
			array(),
			PluginPaths::asset_file_version( 'assets/js/cart-ui-shell-state.js' ),
			true
		);

		$script_deps = array( 'jquery', self::HANDLE_CART_SHELL );
		if ( wp_script_is( 'wc-cart-fragments', 'registered' ) ) {
			$script_deps[] = 'wc-cart-fragments';
		}

		wp_enqueue_script(
			self::HANDLE_SCRIPT,
			PluginPaths::url( 'assets/js/frontend.js' ),
			$script_deps,
			PluginPaths::asset_file_version( 'assets/js/frontend.js' ),
			true
		);

		wp_enqueue_style(
			self::HANDLE_STYLE,
			PluginPaths::url( 'assets/css/frontend.css' ),
			array( DynamicStylesProvider::STYLE_HANDLE ),
			PluginPaths::asset_file_version( 'assets/css/frontend.css' ),
			'all'
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
