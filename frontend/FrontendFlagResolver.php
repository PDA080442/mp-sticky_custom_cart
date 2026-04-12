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

		$catalog_settings = OptionResolver::get_by_path( OptionResolver::get_settings(), 'catalog', array() );
		$img_sel          = isset( $catalog_settings['image_click_selector'] ) ? trim( (string) $catalog_settings['image_click_selector'] ) : '';
		$card_sel         = isset( $catalog_settings['card_root_selector'] ) ? trim( (string) $catalog_settings['card_root_selector'] ) : '';
		if ( '' === $img_sel ) {
			$img_sel = '.woocommerce ul.products li.product img';
		}
		if ( '' === $card_sel ) {
			$card_sel = 'li.product';
		}

		/**
		 * Filters the delegated CSS selector for catalog product image clicks.
		 *
		 * @param string $selector Resolved selector (from settings or default).
		 */
		$img_sel = (string) apply_filters( 'mp_sticky_custom_cart_catalog_image_click_selector', $img_sel );

		/**
		 * Filters the closest() selector from the image to the product card root.
		 *
		 * @param string $selector Resolved selector (from settings or default).
		 */
		$card_sel = (string) apply_filters( 'mp_sticky_custom_cart_catalog_card_root_selector', $card_sel );

		$catalog_js = array(
			'imageClickSelector'  => $img_sel,
			'cardRootSelector'    => $card_sel,
			'resolveErrorMessage' => __( 'Не удалось определить товар для добавления в корзину.', 'mp-sticky-custom-cart' ),
		);

		$data = array(
			'version'  => MP_STICKY_CUSTOM_CART_VERSION,
			'networkErrorMessage' => __( 'Не удалось отправить запрос. Проверьте подключение к сети.', 'mp-sticky-custom-cart' ),
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( Constants::AJAX_NONCE_ACTION ),
			'actions'  => array(
				'cartSnapshot'      => Constants::AJAX_ACTION_CART_SNAPSHOT,
				'setLineQuantity'   => Constants::AJAX_ACTION_SET_LINE_QUANTITY,
				'addSimpleProduct'  => Constants::AJAX_ACTION_ADD_SIMPLE_PRODUCT,
				'logClientEvent'    => Constants::AJAX_ACTION_LOG_CLIENT_EVENT,
			),
			'flags'    => $provider->for_js(),
			'labels'   => OptionResolver::get_labels(),
			'cssVars'  => $styles->get_css_custom_properties(),
			'wcCartFragments' => $cart_fragments,
			'catalog'  => $catalog_js,
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
