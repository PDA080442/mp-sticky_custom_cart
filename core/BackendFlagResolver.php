<?php
/**
 * PHP helper for feature flag checks (themes, templates, other PHP).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Thin facade over {@see OptionResolver} / {@see FeatureFlagProvider}.
 */
final class BackendFlagResolver {

	/**
	 * @param string $key {@see \MpStickyCustomCart\Core\Config\FeatureFlagsDefaults} KEY_*.
	 */
	public static function enabled( $key ) {
		return OptionResolver::get_flag( (string) $key );
	}

	/**
	 * @return array<string, bool>
	 */
	public static function all() {
		return OptionResolver::get_feature_flags();
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
