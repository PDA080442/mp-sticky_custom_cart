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
			'0.1.0'  => array( self::class, 'migrate_to_0_1_0' ),
			'0.1.52' => array( self::class, 'migrate_to_0_1_52_tristate_dock_insets' ),
			'0.1.53' => array( self::class, 'migrate_to_0_1_53_tristate_state_a_dock_insets' ),
			'0.1.68' => array( self::class, 'migrate_to_0_1_68_restore_sticky_hide_when_empty_default' ),
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
			if ( isset( $settings['diagnostics'], $defaults['diagnostics'] ) && is_array( $settings['diagnostics'] ) && is_array( $defaults['diagnostics'] ) ) {
				foreach ( $defaults['diagnostics'] as $dk => $dv ) {
					if ( ! array_key_exists( $dk, $settings['diagnostics'] ) ) {
						$settings['diagnostics'][ $dk ] = $dv;
						$dirty                          = true;
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
			if ( isset( $settings['catalog'], $defaults['catalog'] ) && is_array( $settings['catalog'] ) && is_array( $defaults['catalog'] ) ) {
				foreach ( $defaults['catalog'] as $ck => $cv ) {
					if ( ! array_key_exists( $ck, $settings['catalog'] ) ) {
						$settings['catalog'][ $ck ] = $cv;
						$dirty                      = true;
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
	 * Per-side tri-state dock insets: legacy installs only had right + bottom (left mirrored right in CSS).
	 */
	private static function migrate_to_0_1_52_tristate_dock_insets() {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		if ( ! is_array( $settings ) || ! isset( $settings['sticky_cart'] ) || ! is_array( $settings['sticky_cart'] ) ) {
			return;
		}
		$sc    = &$settings['sticky_cart'];
		$dirty = false;
		$right = isset( $sc['tristate_dock_inset_right_px'] ) ? (int) $sc['tristate_dock_inset_right_px'] : 32;
		if ( ! array_key_exists( 'tristate_dock_inset_left_px', $sc ) ) {
			$sc['tristate_dock_inset_left_px'] = $right;
			$dirty                             = true;
		}
		if ( ! array_key_exists( 'tristate_dock_inset_top_px', $sc ) ) {
			$sc['tristate_dock_inset_top_px'] = 0;
			$dirty                            = true;
		}
		if ( $dirty ) {
			update_option( Constants::OPTION_SETTINGS, $settings, false );
		}
	}

	/**
	 * Tri-state state A (FAB-only) dock insets: seed from column insets so upgrades keep the same look.
	 */
	private static function migrate_to_0_1_53_tristate_state_a_dock_insets() {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		if ( ! is_array( $settings ) || ! isset( $settings['sticky_cart'] ) || ! is_array( $settings['sticky_cart'] ) ) {
			return;
		}
		$sc    = &$settings['sticky_cart'];
		$dirty = false;
		$dock_r = isset( $sc['tristate_dock_inset_right_px'] ) ? (int) $sc['tristate_dock_inset_right_px'] : 32;
		$dock_l = array_key_exists( 'tristate_dock_inset_left_px', $sc ) ? (int) $sc['tristate_dock_inset_left_px'] : $dock_r;
		if ( ! array_key_exists( 'tristate_state_a_dock_inset_top_px', $sc ) ) {
			$sc['tristate_state_a_dock_inset_top_px'] = isset( $sc['tristate_dock_inset_top_px'] ) ? (int) $sc['tristate_dock_inset_top_px'] : 0;
			$dirty                                    = true;
		}
		if ( ! array_key_exists( 'tristate_state_a_dock_inset_right_px', $sc ) ) {
			$sc['tristate_state_a_dock_inset_right_px'] = $dock_r;
			$dirty                                      = true;
		}
		if ( ! array_key_exists( 'tristate_state_a_dock_inset_bottom_px', $sc ) ) {
			$sc['tristate_state_a_dock_inset_bottom_px'] = isset( $sc['tristate_dock_inset_bottom_px'] ) ? (int) $sc['tristate_dock_inset_bottom_px'] : 32;
			$dirty                                         = true;
		}
		if ( ! array_key_exists( 'tristate_state_a_dock_inset_left_px', $sc ) ) {
			$sc['tristate_state_a_dock_inset_left_px'] = $dock_l;
			$dirty                                     = true;
		}
		if ( $dirty ) {
			update_option( Constants::OPTION_SETTINGS, $settings, false );
		}
	}

	/**
	 * Reverts mistaken 0.1.67 default: empty-cart shell must stay off until first add (tri-state FAB + bar).
	 *
	 * @see FeatureFlagsDefaults::KEY_STICKY_HIDE_WHEN_EMPTY_ENABLED
	 */
	private static function migrate_to_0_1_68_restore_sticky_hide_when_empty_default() {
		$flags = get_option( Constants::OPTION_FEATURE_FLAGS, array() );
		if ( ! is_array( $flags ) ) {
			$flags = array();
		}
		$flags[ FeatureFlagsDefaults::KEY_STICKY_HIDE_WHEN_EMPTY_ENABLED ] = true;
		update_option( Constants::OPTION_FEATURE_FLAGS, $flags, false );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
