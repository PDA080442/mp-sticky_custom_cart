<?php
/**
 * Sanitizes settings and feature flags using {@see SettingsValidationSchema}.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Admin;

use MpStickyCustomCart\Core\Config\FeatureFlagsDefaults;
use MpStickyCustomCart\Core\Config\SettingsValidationSchema;
use MpStickyCustomCart\Core\Config\UiLabelsDefaults;
use MpStickyCustomCart\Core\Config\UiSettingsDefaults;
use MpStickyCustomCart\Core\Constants;
use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Whitelist + type coercion for Settings API persistence.
 */
final class SettingsSanitizer {

	/**
	 * Full option tree for {@see Constants::OPTION_SETTINGS}.
	 *
	 * @param mixed $input Raw POST value.
	 * @return array<string, mixed>
	 */
	public static function sanitize_settings( $input ) {
		$defaults = self::default_settings_bundle();
		if ( ! is_array( $input ) ) {
			OptionResolver::flush_cache();
			return self::merge_with_saved( $defaults );
		}

		$saved = get_option( Constants::OPTION_SETTINGS, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$out = self::merge_deep( $defaults, $saved );
		foreach ( SettingsValidationSchema::get_settings_schema() as $section => $fields ) {
			if ( ! isset( $input[ $section ] ) || ! is_array( $input[ $section ] ) ) {
				continue;
			}
			foreach ( $fields as $key => $field ) {
				$path   = $section . '.' . $key;
				$exists = array_key_exists( $key, $input[ $section ] );
				$raw    = $exists ? $input[ $section ][ $key ] : null;
				$out    = self::set_path( $out, $path, self::sanitize_field( $raw, $field, $defaults, $path, $exists ) );
			}
		}

		OptionResolver::flush_cache();

		return $out;
	}

	/**
	 * @param mixed $input Raw POST value.
	 * @return array<string, bool>
	 */
	public static function sanitize_feature_flags( $input ) {
		$defaults = FeatureFlagsDefaults::get();
		$saved    = get_option( Constants::OPTION_FEATURE_FLAGS, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$out = array_merge( $defaults, $saved );

		if ( ! is_array( $input ) ) {
			OptionResolver::flush_cache();
			return $out;
		}

		foreach ( SettingsValidationSchema::get_flags_schema() as $key => $field ) {
			if ( array_key_exists( $key, $input ) ) {
				$raw         = $input[ $key ];
				$out[ $key ] = ( '1' === (string) $raw || 1 === $raw || true === $raw || 'on' === $raw );
			}
		}

		OptionResolver::flush_cache();

		return $out;
	}

	/**
	 * @param mixed                $raw     Raw value.
	 * @param array<string, mixed> $field   Schema fragment.
	 * @param array<string, mixed> $defaults Full default tree.
	 * @param string               $path    Dot path for fallback.
	 * @param bool                 $exists  Whether key was present in POST.
	 * @return mixed
	 */
	private static function sanitize_field( $raw, array $field, array $defaults, $path, $exists ) {
		$fallback = self::get_path( $defaults, $path, null );
		$type     = isset( $field['type'] ) ? (string) $field['type'] : 'text';

		switch ( $type ) {
			case 'boolean':
				if ( ! $exists ) {
					return false;
				}
				return self::sanitize_boolean( $raw, (bool) $fallback );
			case 'integer':
				return self::sanitize_integer( $raw, $field, $fallback );
			case 'float':
				return self::sanitize_float( $raw, $field, $fallback );
			case 'color':
				return self::sanitize_color( $raw, is_string( $fallback ) ? $fallback : '#000000' );
			case 'text':
			default:
				$text = self::sanitize_text( $raw, isset( $field['max_length'] ) ? (int) $field['max_length'] : 1000 );
				if ( isset( $field['oneof'] ) && is_array( $field['oneof'] ) && ! in_array( $text, $field['oneof'], true ) ) {
					return is_string( $fallback ) ? $fallback : $text;
				}
				return $text;
		}
	}

	/**
	 * @param mixed                $raw
	 * @param array<string, mixed> $field
	 * @param mixed                $fallback
	 */
	private static function sanitize_integer( $raw, array $field, $fallback ) {
		$n = (int) $raw;
		if ( isset( $field['min'] ) ) {
			$n = max( (int) $field['min'], $n );
		}
		if ( isset( $field['max'] ) ) {
			$n = min( (int) $field['max'], $n );
		}
		if ( isset( $field['oneof'] ) && is_array( $field['oneof'] ) && ! in_array( $n, $field['oneof'], true ) ) {
			return is_int( $fallback ) ? (int) $fallback : (int) $field['oneof'][0];
		}
		return $n;
	}

	/**
	 * @param mixed                $raw
	 * @param array<string, mixed> $field
	 * @param mixed                $fallback
	 */
	private static function sanitize_float( $raw, array $field, $fallback ) {
		if ( ! is_numeric( $raw ) ) {
			return is_float( $fallback ) || is_int( $fallback ) ? (float) $fallback : 0.0;
		}
		$n = (float) $raw;
		if ( isset( $field['min'] ) ) {
			$n = max( (float) $field['min'], $n );
		}
		if ( isset( $field['max'] ) ) {
			$n = min( (float) $field['max'], $n );
		}
		return $n;
	}

	/**
	 * @param mixed $raw
	 */
	private static function sanitize_boolean( $raw, $default_false ) {
		if ( '0' === $raw || 0 === $raw || false === $raw || '' === $raw || null === $raw ) {
			return false;
		}
		if ( '1' === $raw || 1 === $raw || true === $raw ) {
			return true;
		}
		return (bool) $default_false;
	}

	/**
	 * @param mixed $raw
	 */
	private static function sanitize_text( $raw, $max_length ) {
		$s = is_string( $raw ) ? wp_strip_all_tags( $raw ) : '';
		if ( $max_length > 0 ) {
			$s = mb_substr( $s, 0, $max_length );
		}
		return $s;
	}

	/**
	 * @param mixed  $raw
	 * @param string $fallback
	 */
	private static function sanitize_color( $raw, $fallback ) {
		$c = is_string( $raw ) ? trim( $raw ) : '';
		if ( '' === $c ) {
			return sanitize_hex_color( $fallback ) ? $fallback : '#000000';
		}
		$hex = sanitize_hex_color( $c );
		if ( $hex ) {
			return $hex;
		}

		return sanitize_hex_color( $fallback ) ? $fallback : '#000000';
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function default_settings_bundle() {
		$tree = UiSettingsDefaults::get();
		if ( ! isset( $tree['labels'] ) || ! is_array( $tree['labels'] ) ) {
			$tree['labels'] = UiLabelsDefaults::get_raw();
		}
		return $tree;
	}

	/**
	 * @param array<string, mixed> $defaults
	 * @return array<string, mixed>
	 */
	private static function merge_with_saved( array $defaults ) {
		$saved = get_option( Constants::OPTION_SETTINGS, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return self::merge_deep( $defaults, $saved );
	}

	/**
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
	 * @param array<string, mixed> $data
	 * @param string               $path Dot path.
	 * @param mixed                $default
	 * @return mixed
	 */
	private static function get_path( array $data, $path, $default = null ) {
		return OptionResolver::get_by_path( $data, $path, $default );
	}

	/**
	 * @param array<string, mixed> $data
	 * @param string               $path Dot path.
	 * @param mixed                $value
	 * @return array<string, mixed>
	 */
	private static function set_path( array $data, $path, $value ) {
		$keys = explode( '.', (string) $path );
		$ref  = &$data;
		$last = array_pop( $keys );
		foreach ( $keys as $k ) {
			if ( ! isset( $ref[ $k ] ) || ! is_array( $ref[ $k ] ) ) {
				$ref[ $k ] = array();
			}
			$ref = &$ref[ $k ];
		}
		$ref[ $last ] = $value;
		return $data;
	}
}
