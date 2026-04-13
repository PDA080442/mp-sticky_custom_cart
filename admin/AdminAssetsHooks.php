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
	public const HANDLE_SETTINGS_SHELL  = 'mp-scc-admin-settings-shell';
	public const HANDLE_ERROR_LOG     = 'mp-scc-error-log-panel';

	public static function register() {
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ), 20 );
		add_filter( 'admin_body_class', array( self::class, 'admin_body_class' ) );

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

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_style(
			self::HANDLE_SETTINGS_SHELL,
			PluginPaths::url( 'admin/css/settings-page-shell.css' ),
			array(),
			MP_STICKY_CUSTOM_CART_ASSET_VERSION
		);

		wp_enqueue_style(
			self::HANDLE_STYLE,
			PluginPaths::url( 'admin/css/settings-preview.css' ),
			array( 'dashicons', 'wp-color-picker', self::HANDLE_SETTINGS_SHELL ),
			MP_STICKY_CUSTOM_CART_ASSET_VERSION
		);

		wp_enqueue_script(
			self::HANDLE_SETTINGS_PAGE,
			PluginPaths::url( 'admin/js/settings-page.js' ),
			array( 'jquery', 'wp-color-picker' ),
			MP_STICKY_CUSTOM_CART_ASSET_VERSION,
			true
		);

		wp_enqueue_style(
			self::HANDLE_ERROR_LOG,
			PluginPaths::url( 'admin/css/error-log-panel.css' ),
			array(),
			MP_STICKY_CUSTOM_CART_ASSET_VERSION
		);

		wp_enqueue_script(
			self::HANDLE_ERROR_LOG,
			PluginPaths::url( 'admin/js/error-log-panel.js' ),
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

		wp_localize_script(
			self::HANDLE_ERROR_LOG,
			'mpSccErrorLog',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'action'       => Constants::AJAX_ACTION_ADMIN_GET_ERROR_LOGS,
				'exportAction' => Constants::AJAX_ACTION_ADMIN_EXPORT_ERROR_LOGS,
				'nonce'        => wp_create_nonce( Constants::NONCE_ADMIN_ERROR_LOG ),
				'enabled'      => DiagnosticsAccess::can_manage(),
				'i18n'         => array(
					'loading'     => __( 'Загрузка…', 'mp-sticky-custom-cart' ),
					'empty'       => __( 'Записей нет.', 'mp-sticky-custom-cart' ),
					'loadError'   => __( 'Не удалось загрузить журнал.', 'mp-sticky-custom-cart' ),
					'pagination'  => __( 'Страница %1$s из %2$s, всего записей: %3$s', 'mp-sticky-custom-cart' ),
					'drawerTitle' => __( 'Полная запись', 'mp-sticky-custom-cart' ),
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
	 * Marks the plugin settings screen for shell CSS (background, layout).
	 *
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public static function admin_body_class( $classes ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! isset( $screen->id ) ) {
			return $classes;
		}
		if ( false !== strpos( (string) $screen->id, Constants::SLUG ) ) {
			return trim( $classes . ' mp-scc-settings-screen' );
		}
		return $classes;
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
