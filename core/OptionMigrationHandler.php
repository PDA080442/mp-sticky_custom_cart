<?php
/**
 * Bumps stored option schema when the plugin version advances.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

use MpStickyCustomCart\Core\Config\FeatureFlagsDefaults;
use MpStickyCustomCart\Core\Config\UiLabelsDefaults;
use MpStickyCustomCart\Core\Config\UiSettingsDefaults;

defined( 'ABSPATH' ) || exit;

/**
 * Registers migrations keyed by the first plugin version that requires them.
 */
final class OptionMigrationHandler {

	/**
	 * Register early on `plugins_loaded` (before OptionResolver consumers).
	 */
	public static function register() {
		add_action( 'plugins_loaded', array( self::class, 'maybe_run' ), 5 );
	}

	/**
	 * Run pending migrations and align {@see Constants::OPTION_DB_VERSION} with the plugin.
	 */
	public static function maybe_run() {
		$stored = get_option( Constants::OPTION_DB_VERSION, '0' );
		if ( ! is_string( $stored ) || '' === $stored ) {
			$stored = '0';
		}

		$target = MP_STICKY_CUSTOM_CART_VERSION;

		if ( version_compare( $stored, $target, '>=' ) ) {
			return;
		}

		$current = $stored;

		foreach ( self::get_migrations() as $version => $callback ) {
			if ( version_compare( $current, $version, '<' ) ) {
				call_user_func( $callback );
				$current = $version;
				update_option( Constants::OPTION_DB_VERSION, $version, true );
			}
		}

		if ( version_compare( $current, $target, '<' ) ) {
			update_option( Constants::OPTION_DB_VERSION, $target, true );
		}

		OptionResolver::flush_cache();
	}

	/**
	 * Ordered migration steps (semver keys).
	 *
	 * @return array<string, callable>
	 */
	private static function get_migrations() {
		return array(
			'0.1.0' => array( self::class, 'migrate_to_0_1_0' ),
		);
	}

	/**
	 * Initial persisted options and labels subtree.
	 */
	private static function migrate_to_0_1_0() {
		$settings = get_option( Constants::OPTION_SETTINGS, null );
		if ( ! is_array( $settings ) ) {
			$bundle            = UiSettingsDefaults::get();
			$bundle['labels'] = UiLabelsDefaults::get_raw();
			update_option( Constants::OPTION_SETTINGS, $bundle, false );
		} else {
			$defaults = UiSettingsDefaults::get();
			$dirty    = false;
			if ( ! isset( $settings['labels'] ) || ! is_array( $settings['labels'] ) ) {
				$settings['labels'] = UiLabelsDefaults::get_raw();
				$dirty              = true;
			}
			foreach ( array( 'styles', 'diagnostics' ) as $block ) {
				if ( ! isset( $settings[ $block ] ) || ! is_array( $settings[ $block ] ) ) {
					if ( isset( $defaults[ $block ] ) && is_array( $defaults[ $block ] ) ) {
						$settings[ $block ] = $defaults[ $block ];
						$dirty              = true;
					}
				}
			}
			if ( isset( $settings['sticky_cart'], $defaults['sticky_cart'] ) && is_array( $settings['sticky_cart'] ) && is_array( $defaults['sticky_cart'] ) ) {
				foreach ( $defaults['sticky_cart'] as $sk => $sv ) {
					if ( ! array_key_exists( $sk, $settings['sticky_cart'] ) ) {
						$settings['sticky_cart'][ $sk ] = $sv;
						$dirty                          = true;
					}
				}
			}
			if ( $dirty ) {
				update_option( Constants::OPTION_SETTINGS, $settings, false );
			}
		}

		$flags = get_option( Constants::OPTION_FEATURE_FLAGS, null );
		if ( ! is_array( $flags ) ) {
			update_option( Constants::OPTION_FEATURE_FLAGS, FeatureFlagsDefaults::get(), false );
		}

		/**
		 * Fires after the 0.1.0 option migration.
		 */
		do_action( 'mp_sticky_custom_cart_migrated_0_1_0' );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
