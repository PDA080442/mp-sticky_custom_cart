<?php
/**
 * Exposes flags to the storefront script layer ({@see mpSccData} + {@see window.mpScc}).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\CheckoutQueryPreserve;
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
			$img_sel = 'ul.products li.product img, ul.products div.product img, .woocommerce ul.products li.product img, .woocommerce ul.products div.product img, .products li.product img, .products div.product img, div.products div.product img';
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

		$title_link_selectors = array(
			'h2.woocommerce-loop-product__title a',
			'.woocommerce-loop-product__title a',
			'.product-title a',
			'a.woocommerce-LoopProduct-link',
		);

		/**
		 * Filters CSS selectors for product title / permalink links inside a loop card (navigation + sanity checks).
		 *
		 * @param string[] $selectors Relative selectors searched with {@see jQuery#find} on the card root.
		 */
		$title_link_selectors = apply_filters( 'mp_sticky_custom_cart_catalog_title_link_selectors', $title_link_selectors );

		$title_analytics_selectors = array(
			'h2.woocommerce-loop-product__title a',
			'.woocommerce-loop-product__title a',
			'.product-title a',
		);

		/**
		 * Filters selectors for {@see mpScc:catalogTitleClick} — exclude image-only wrapper links (e.g. LoopProduct-link around the thumbnail).
		 *
		 * @param string[] $selectors Delegated click filter (comma-joined for jQuery).
		 */
		$title_analytics_selectors = apply_filters( 'mp_sticky_custom_cart_catalog_title_analytics_selectors', $title_analytics_selectors );

		$image_title_block_selectors = array(
			'.woocommerce-loop-product__title',
			'h2.woocommerce-loop-product__title',
			'.product-title',
		);

		/**
		 * Filters selectors for headings/blocks where an {@see img} should not trigger image add-to-cart (let the link navigate).
		 *
		 * @param string[] $selectors Relative selectors for {@see jQuery#closest} from the image.
		 */
		$image_title_block_selectors = apply_filters( 'mp_sticky_custom_cart_catalog_image_title_block_selectors', $image_title_block_selectors );

		$overlay_host_selectors = array(
			'a.woocommerce-LoopProduct-link',
			'a.woocommerce-loop-product__link',
			'.woocommerce-LoopProduct-link',
		);

		/**
		 * Filters node(s) that wrap the catalog thumbnail; overlay is injected inside the first match (prefer anchor-wrapped images).
		 *
		 * @param string[] $selectors Relative selectors for {@see jQuery#find} on the card root.
		 */
		$overlay_host_selectors = apply_filters( 'mp_sticky_custom_cart_catalog_overlay_host_selectors', $overlay_host_selectors );

		$image_click_behavior = isset( $catalog_settings['image_click_behavior'] ) ? (string) $catalog_settings['image_click_behavior'] : 'add_to_cart';
		if ( ! in_array( $image_click_behavior, array( 'add_to_cart', 'theme_default' ), true ) ) {
			$image_click_behavior = 'add_to_cart';
		}

		$catalog_js = array(
			'imageClickBehavior'       => $image_click_behavior,
			'imageClickSelector'       => $img_sel,
			'cardRootSelector'         => $card_sel,
			'resolveErrorMessage'      => __( 'Не удалось определить товар для добавления в корзину.', 'mp-sticky-custom-cart' ),
			'titleLinkSelectors'         => array_values( $title_link_selectors ),
			'titleAnalyticsSelectors'    => array_values( $title_analytics_selectors ),
			'imageTitleBlockSelectors'   => array_values( $image_title_block_selectors ),
			'hoverOverlayMobileAlways'   => ! empty( $catalog_settings['hover_overlay_mobile_always'] ),
			'overlayHostSelectors'       => array_values( $overlay_host_selectors ),
			'hoverMotionPreset'          => isset( $catalog_settings['hover_motion_preset'] ) ? (string) $catalog_settings['hover_motion_preset'] : 'fade_slide',
			'moreInfoNewTab'             => ! empty( $catalog_settings['more_info_new_tab'] ),
		);

		$data = array(
			'version'  => MP_STICKY_CUSTOM_CART_VERSION,
			'cartQtyTotalLabel'   => __( 'Total quantity in cart: %d', 'mp-sticky-custom-cart' ),
			'networkErrorMessage' => __( 'Не удалось отправить запрос. Проверьте подключение к сети.', 'mp-sticky-custom-cart' ),
			'checkoutPreserveQueryKeys' => CheckoutQueryPreserve::allowed_keys(),
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( Constants::AJAX_NONCE_ACTION ),
			'actions'  => array(
				'cartSnapshot'      => Constants::AJAX_ACTION_CART_SNAPSHOT,
				'setLineQuantity'   => Constants::AJAX_ACTION_SET_LINE_QUANTITY,
				'addSimpleProduct'  => Constants::AJAX_ACTION_ADD_SIMPLE_PRODUCT,
				'clearCart'         => Constants::AJAX_ACTION_CLEAR_CART,
				'removeCartLine'    => Constants::AJAX_ACTION_REMOVE_CART_LINE,
				'logClientEvent'    => Constants::AJAX_ACTION_LOG_CLIENT_EVENT,
			),
			'flags'    => $provider->for_js(),
			'labels'   => OptionResolver::get_labels(),
			'cssVars'  => $styles->get_css_custom_properties(),
			'wcCartFragments' => $cart_fragments,
			'catalog'  => $catalog_js,
			'clientLogging'     => (bool) OptionResolver::get_setting( 'diagnostics.client_error_logging', true ),
			'clientLogFlushMs'  => 1200,
			'clientLogMaxBatch' => 12,
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
