<?php
/**
 * Wires feature hook groups after WooCommerce is available.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

use MpStickyCustomCart\Admin\AdminAssetsHooks;
use MpStickyCustomCart\Admin\SettingsApiHooks;
use MpStickyCustomCart\Frontend\AddedToCartHooks;
use MpStickyCustomCart\Frontend\AjaxEndpointsHooks;
use MpStickyCustomCart\Frontend\FrontendAssetsHooks;
use MpStickyCustomCart\Frontend\StickyCartRenderHooks;

defined( 'ABSPATH' ) || exit;

/**
 * Delegates to module-specific registrars (stubs extended in later tasks).
 */
final class HookRegistry {

	/**
	 * Register all WooCommerce-dependent hooks.
	 */
	public static function register() {
		FrontendAssetsHooks::register();
		AdminAssetsHooks::register();
		StickyCartRenderHooks::register();
		AjaxEndpointsHooks::register();
		SettingsApiHooks::register();
		ErrorLoggingHooks::register();
		AddedToCartHooks::register();

		/**
		 * Fires after hook groups are registered (WooCommerce active).
		 */
		do_action( 'mp_sticky_custom_cart_ready' );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
