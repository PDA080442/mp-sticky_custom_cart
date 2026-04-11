<?php
/**
 * Settings API registration for plugin options pages.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Admin;

use MpStickyCustomCart\Core\Config\FeatureFlagsDefaults;
use MpStickyCustomCart\Core\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Registers options, sanitizers, and defaults for the Settings API.
 */
final class SettingsApiHooks {

	public static function register() {
		add_action( 'admin_init', array( self::class, 'register_settings' ) );

		/**
		 * Fires when settings API hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_settings_api_hooks_registered' );
	}

	public static function register_settings() {
		register_setting(
			Constants::SLUG . '_settings',
			Constants::OPTION_SETTINGS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( SettingsSanitizer::class, 'sanitize_settings' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);

		register_setting(
			Constants::SLUG . '_settings',
			Constants::OPTION_FEATURE_FLAGS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( SettingsSanitizer::class, 'sanitize_feature_flags' ),
				'default'           => FeatureFlagsDefaults::get(),
				'show_in_rest'      => false,
			)
		);

		/**
		 * Fires after base settings are registered.
		 */
		do_action( 'mp_sticky_custom_cart_register_settings' );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
