<?php
/**
 * Reads plugin options with defaults merged and safe fallbacks.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

use MpStickyCustomCart\Core\Config\FeatureFlagsDefaults;
use MpStickyCustomCart\Core\Config\UiLabelsDefaults;
use MpStickyCustomCart\Core\Config\UiSettingsDefaults;

defined( 'ABSPATH' ) || exit;

/**
 * Stateless accessors; safe if options are missing or corrupted.
 */
final class OptionResolver {

	/**
	 * Cached merged settings (request-local).
	 *
	 * @var array<string, mixed>|null
	 */
	private static $settings_cache = null;

	/**
	 * Cached merged flags.
	 *
	 * @var array<string, bool>|null
	 */
	private static $flags_cache = null;

	/**
	 * Cached merged labels.
	 *
	 * @var array<string, string>|null
	 */
	private static $labels_cache = null;

	/**
	 * Full settings tree: UI defaults + optional `labels` + DB overrides.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings() {
		if ( null !== self::$settings_cache ) {
			return self::$settings_cache;
		}

		$saved = get_option( Constants::OPTION_SETTINGS, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$defaults = self::default_settings_tree();
		$merged   = self::merge_deep( $defaults, $saved );

		/**
		 * Filters merged plugin settings (after DB merge, before cache).
		 *
		 * @param array<string, mixed> $merged Full tree including labels.
		 */
		self::$settings_cache = apply_filters( 'mp_sticky_custom_cart_settings', $merged );

		return self::$settings_cache;
	}

	/**
	 * Feature flags with defaults applied for unknown keys.
	 *
	 * @return array<string, bool>
	 */
	public static function get_feature_flags() {
		if ( null !== self::$flags_cache ) {
			return self::$flags_cache;
		}

		$saved = get_option( Constants::OPTION_FEATURE_FLAGS, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$merged = array_merge( FeatureFlagsDefaults::get(), $saved );

		foreach ( $merged as $key => $value ) {
			$merged[ $key ] = (bool) $value;
		}

		/**
		 * Filters merged feature flags.
		 *
		 * @param array<string, bool> $merged
		 */
		self::$flags_cache = apply_filters( 'mp_sticky_custom_cart_feature_flags', $merged );

		return self::$flags_cache;
	}

	/**
	 * User-facing strings (merged with raw defaults).
	 *
	 * @return array<string, string>
	 */
	public static function get_labels() {
		if ( null !== self::$labels_cache ) {
			return self::$labels_cache;
		}

		$from_settings = self::get_by_path( self::get_settings(), 'labels', array() );
		if ( ! is_array( $from_settings ) ) {
			$from_settings = array();
		}

		$merged = array_merge( UiLabelsDefaults::get_raw(), $from_settings );

		foreach ( $merged as $k => $v ) {
			$merged[ $k ] = is_string( $v ) ? $v : '';
		}

		$merged = LabelResolver::fill_empty_with_defaults( $merged );

		/**
		 * Filters the full labels map (bulk overrides, e.g. switch locale bundle).
		 *
		 * @param array<string, string> $merged Labels after empty-string fallbacks.
		 */
		$merged = apply_filters( 'mp_sticky_custom_cart_labels', $merged );

		self::$labels_cache = LabelResolver::apply_per_key_filters( $merged );

		return self::$labels_cache;
	}

	/**
	 * Dot-path read on merged settings tree.
	 *
	 * @param string $path Dot-separated keys, e.g. `sticky_cart.z_index`.
	 * @param mixed  $default Returned when path is missing or intermediate is not an array.
	 * @return mixed
	 */
	public static function get_setting( $path, $default = null ) {
		return self::get_by_path( self::get_settings(), $path, $default );
	}

	/**
	 * Single feature flag.
	 *
	 * @param string $key {@see FeatureFlagsDefaults} key constant value.
	 * @param bool   $default Fallback if key unknown.
	 */
	public static function get_flag( $key, $default = false ) {
		$flags = self::get_feature_flags();
		if ( ! array_key_exists( $key, $flags ) ) {
			return (bool) $default;
		}
		return (bool) $flags[ $key ];
	}

	/**
	 * Single label string.
	 *
	 * @param string $key {@see UiLabelsDefaults} key constant value.
	 * @param string $default Fallback.
	 */
	public static function get_label( $key, $default = '' ) {
		$labels = self::get_labels();
		if ( ! array_key_exists( $key, $labels ) ) {
			return (string) $default;
		}
		return $labels[ $key ];
	}

	/**
	 * Generic dot-path getter for any array (used by CSS contract and templates).
	 *
	 * @param array<string, mixed> $data    Source tree.
	 * @param string               $path    Dot path.
	 * @param mixed                $default Default if missing.
	 * @return mixed
	 */
	public static function get_by_path( array $data, $path, $default = null ) {
		$path = (string) $path;
		if ( '' === $path ) {
			return $default;
		}

		$keys    = explode( '.', $path );
		$current = $data;

		foreach ( $keys as $key ) {
			if ( ! is_array( $current ) || ! array_key_exists( $key, $current ) ) {
				return $default;
			}
			$current = $current[ $key ];
		}

		return $current;
	}

	/**
	 * Clear in-request caches after option updates.
	 */
	public static function flush_cache() {
		self::$settings_cache = null;
		self::$flags_cache    = null;
		self::$labels_cache   = null;
	}

	/**
	 * Default tree including nested `labels`.
	 *
	 * @return array<string, mixed>
	 */
	private static function default_settings_tree() {
		$tree = UiSettingsDefaults::get();
		if ( ! isset( $tree['labels'] ) || ! is_array( $tree['labels'] ) ) {
			$tree['labels'] = UiLabelsDefaults::get_raw();
		}

		return $tree;
	}

	/**
	 * Deep merge: overrides replace or recurse into arrays; null skips override.
	 *
	 * @param array<string, mixed> $base
	 * @param array<string, mixed> $overrides
	 * @return array<string, mixed>
	 */
	private static function merge_deep( array $base, array $overrides ) {
		foreach ( $overrides as $key => $value ) {
			if ( null === $value ) {
				continue;
			}
			if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
				$base[ $key ] = self::merge_deep( $base[ $key ], $value );
			} else {
				$base[ $key ] = $value;
			}
		}

		return $base;
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
