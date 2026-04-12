<?php
/**
 * Exposes flags to the storefront script layer ({@see mpSccData} + {@see window.mpScc}).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\Constants;
use MpStickyCustomCart\Core\FeatureFlagProvider;
use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Localizes `mpSccData` (config, AJAX, flags, labels, CSS vars) for {@see assets/js/frontend.js}.
 */
final class FrontendFlagResolver {

	/**
	 * Attach runtime data for storefront scripts.
	 *
	 * @param string $handle Registered script handle (e.g. {@see FrontendAssetsHooks::HANDLE_SCRIPT}).
	 */
	public static function localize( $handle ) {
		$provider       = new FeatureFlagProvider();
		$styles         = new DynamicStylesProvider();
		$cart_fragments = wp_script_is( 'wc-cart-fragments', 'registered' );

		$data = array(
			'version'  => MP_STICKY_CUSTOM_CART_VERSION,
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( Constants::AJAX_NONCE_ACTION ),
			'actions'  => array(
				'cartSnapshot'     => Constants::AJAX_ACTION_CART_SNAPSHOT,
				'setLineQuantity' => Constants::AJAX_ACTION_SET_LINE_QUANTITY,
			),
			'flags'    => $provider->for_js(),
			'labels'   => OptionResolver::get_labels(),
			'cssVars'  => $styles->get_css_custom_properties(),
			'wcCartFragments' => $cart_fragments,
		);

		/**
		 * Filters the object passed to {@see wp_localize_script} as `mpSccData`.
		 *
		 * @param array<string, mixed> $data   Localized payload.
		 * @param string                 $handle Script handle.
		 */
		$data = apply_filters( 'mp_sticky_custom_cart_localize_script_data', $data, $handle );

		wp_localize_script(
			$handle,
			'mpSccData',
			$data
		);
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
