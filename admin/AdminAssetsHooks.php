<?php
/**
 * Admin styles/scripts for settings UI and diagnostics.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Registers `admin_enqueue_scripts` for plugin screens.
 */
final class AdminAssetsHooks {

	public const HANDLE_SCRIPT = 'mp-scc-admin';
	public const HANDLE_STYLE  = 'mp-scc-admin';

	public static function register() {
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ), 20 );

		/**
		 * Fires when admin asset hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_admin_assets_hooks_registered' );
	}

	/**
	 * Enqueue settings/diagnostics assets on plugin pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue( $hook_suffix ) {
		/**
		 * Fires before admin assets are enqueued.
		 *
		 * @param string $hook_suffix Current admin page hook.
		 */
		do_action( 'mp_sticky_custom_cart_enqueue_admin_assets', $hook_suffix );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
