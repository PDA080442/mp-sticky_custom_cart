<?php
/**
 * Label fallbacks and translation filters (WPML/Polylang-friendly hooks).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

use MpStickyCustomCart\Core\Config\UiLabelsDefaults;

defined( 'ABSPATH' ) || exit;

/**
 * Ensures non-empty strings and runs per-key filters after bulk {@see 'mp_sticky_custom_cart_labels'}.
 */
final class LabelResolver {

	/**
	 * Replace empty or whitespace-only values with raw defaults from {@see UiLabelsDefaults::get_raw()}.
	 *
	 * @param array<string, string> $labels Merged labels (saved may override defaults).
	 * @return array<string, string>
	 */
	public static function fill_empty_with_defaults( array $labels ) {
		$raw = UiLabelsDefaults::get_raw();
		foreach ( $raw as $key => $default_text ) {
			$current = isset( $labels[ $key ] ) ? $labels[ $key ] : '';
			$current = is_string( $current ) ? $current : '';
			if ( '' === trim( $current ) ) {
				$labels[ $key ] = $default_text;
			}
		}
		return $labels;
	}

	/**
	 * Apply {@see 'mp_sticky_custom_cart_label'} for each key (multilingual / contextual overrides).
	 *
	 * @param array<string, string> $labels Labels after fallbacks and bulk filter.
	 * @return array<string, string>
	 */
	public static function apply_per_key_filters( array $labels ) {
		$out = array();
		foreach ( $labels as $key => $text ) {
			$key = (string) $key;
			/**
			 * Filters a single UI label after merge and empty fallbacks.
			 *
			 * @param string $text Resolved text.
			 * @param string $key   Stable key (e.g. more_info, checkout).
			 */
			$out[ $key ] = (string) apply_filters( 'mp_sticky_custom_cart_label', (string) $text, $key );
		}
		return $out;
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
