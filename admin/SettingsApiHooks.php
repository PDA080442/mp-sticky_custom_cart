<?php
/**
 * Settings API registration for plugin options pages.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Admin;

use MpStickyCustomCart\Core\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Registers `admin_init` settings sections/fields (UI built in admin phase).
 */
final class SettingsApiHooks {

	public static function register() {
		add_action( 'admin_init', array( self::class, 'register_settings' ) );

		/**
		 * Fires when settings API hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_settings_api_hooks_registered' );
	}

	/**
	 * Register option groups (sanitizers added with fields later).
	 */
	public static function register_settings() {
		register_setting(
			Constants::SLUG . '_settings',
			Constants::OPTION_SETTINGS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_settings_stub' ),
				'default'           => array(),
			)
		);

		register_setting(
			Constants::SLUG . '_settings',
			Constants::OPTION_FEATURE_FLAGS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_flags_stub' ),
				'default'           => array(),
			)
		);

		/**
		 * Fires after base settings are registered.
		 */
		do_action( 'mp_sticky_custom_cart_register_settings' );
	}

	/**
	 * @param mixed $value Raw value.
	 * @return array<string, mixed>
	 */
	public static function sanitize_settings_stub( $value ) {
		return is_array( $value ) ? $value : array();
	}

	/**
	 * @param mixed $value Raw value.
	 * @return array<string, bool>
	 */
	public static function sanitize_flags_stub( $value ) {
		return is_array( $value ) ? $value : array();
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
