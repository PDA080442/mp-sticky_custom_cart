<?php
/**
 * Exposes flags to the storefront script layer ({@see mpSccData} + {@see window.mpScc}).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\FeatureFlagProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Localizes `mpSccData.flags` for {@see assets/js/frontend.js}.
 */
final class FrontendFlagResolver {

	/**
	 * Attach localized flag map to an enqueued script handle.
	 *
	 * @param string $handle Registered script handle (e.g. {@see FrontendAssetsHooks::HANDLE_SCRIPT}).
	 */
	public static function localize( $handle ) {
		$provider = new FeatureFlagProvider();
		wp_localize_script(
			$handle,
			'mpSccData',
			array(
				'flags' => $provider->for_js(),
			)
		);
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
