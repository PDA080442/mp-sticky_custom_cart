<?php
/**
 * Admin styles/scripts for settings UI and diagnostics.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Admin;

use MpStickyCustomCart\Core\Constants;
use MpStickyCustomCart\Core\PluginPaths;

defined( 'ABSPATH' ) || exit;

/**
 * Registers `admin_enqueue_scripts` for plugin screens.
 */
final class AdminAssetsHooks {

	public const HANDLE_SCRIPT        = 'mp-scc-admin';
	public const HANDLE_STYLE         = 'mp-scc-admin';
	public const HANDLE_SETTINGS_PAGE = 'mp-scc-admin-settings-page';

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
		$page_hook = SettingsPage::get_hook_suffix();
		if ( '' === $page_hook || $hook_suffix !== $page_hook ) {
			return;
		}

		wp_enqueue_style(
			self::HANDLE_STYLE,
			PluginPaths::url( 'admin/css/settings-preview.css' ),
			array( 'dashicons' ),
			MP_STICKY_CUSTOM_CART_ASSET_VERSION
		);

		wp_enqueue_script(
			self::HANDLE_SETTINGS_PAGE,
			PluginPaths::url( 'admin/js/settings-page.js' ),
			array( 'jquery' ),
			MP_STICKY_CUSTOM_CART_ASSET_VERSION,
			true
		);
		wp_localize_script(
			self::HANDLE_SETTINGS_PAGE,
			'mpSccAdmin',
			array(
				'beforeUnload' => __( 'Есть несохранённые изменения. Покинуть страницу?', 'mp-sticky-custom-cart' ),
				'stylePreview' => array(
					'previewId'  => 'mp-scc-style-live-preview',
					'throttleMs' => 100,
				),
				'errorLogAjax' => array(
					'url'    => admin_url( 'admin-ajax.php' ),
					'action' => Constants::AJAX_ACTION_ADMIN_GET_ERROR_LOGS,
					'nonce'  => wp_create_nonce( Constants::NONCE_ADMIN_ERROR_LOG ),
				),
			)
		);

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
