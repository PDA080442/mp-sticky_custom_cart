<?php
/**
 * Exposes flags to the storefront script layer ({@see mpSccData} + {@see window.mpScc}).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\FeatureFlagProvider;
use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Localizes `mpSccData` (flags + labels) for {@see assets/js/frontend.js}.
 */
final class FrontendFlagResolver {

	/**
	 * Attach runtime data for storefront scripts.
	 *
	 * @param string $handle Registered script handle (e.g. {@see FrontendAssetsHooks::HANDLE_SCRIPT}).
	 */
	public static function localize( $handle ) {
		$provider = new FeatureFlagProvider();
		wp_localize_script(
			$handle,
			'mpSccData',
			array(
				'flags'  => $provider->for_js(),
				'labels' => OptionResolver::get_labels(),
			)
		);
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
