<?php
/**
 * Resolves feature flags from persisted options + defaults.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

use MpStickyCustomCart\Core\Contracts\FeatureFlagProviderInterface;

defined( 'ABSPATH' ) || exit;

/**
 * @implements FeatureFlagProviderInterface
 */
final class FeatureFlagProvider implements FeatureFlagProviderInterface {

	/**
	 * @return bool
	 */
	public function enabled( $key ) {
		return OptionResolver::get_flag( (string) $key );
	}

	/**
	 * @return array<string, bool>
	 */
	public function all() {
		return OptionResolver::get_feature_flags();
	}

	/**
	 * @return array<string, bool>
	 */
	public function for_js() {
		$out = array();
		foreach ( $this->all() as $k => $v ) {
			$out[ (string) $k ] = (bool) $v;
		}
		return $out;
	}
}
