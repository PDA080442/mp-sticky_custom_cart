<?php
/**
 * Read-only access to feature flags for PHP and localized JS.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps persisted flags with defaults from {@see \MpStickyCustomCart\Core\Config\FeatureFlagsDefaults}.
 */
interface FeatureFlagProviderInterface {

	/**
	 * Whether a named flag is enabled.
	 *
	 * @param string $key Stable flag key (see FeatureFlagsDefaults).
	 */
	public function enabled( $key );

	/**
	 * All flags merged with defaults.
	 *
	 * @return array<string, bool>
	 */
	public function all();

	/**
	 * Map suitable for `wp_localize_script` (JSON-friendly).
	 *
	 * @return array<string, bool>
	 */
	public function for_js();
}
