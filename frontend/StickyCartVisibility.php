<?php
/**
 * Whether the sticky cart shell is shown (same rules for markup, body class, and docs).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\Config\FeatureFlagsDefaults;
use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Sticky shell is rendered when {@see should_render_sticky()} is true.
 * With {@see FeatureFlagsDefaults::KEY_STICKY_HIDE_WHEN_EMPTY_ENABLED}, an empty cart skips markup and body reserve (dp §18.1).
 */
final class StickyCartVisibility {

	/**
	 * Mirrors {@see StickyCartRenderer::should_render()} for reuse on `body_class` and tests.
	 *
	 * @return bool
	 */
	public static function should_render_sticky() {
		if ( ! OptionResolver::get_flag( FeatureFlagsDefaults::KEY_STICKY_CART_ENABLED, true ) ) {
			return false;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}

		if ( is_feed() || is_embed() ) {
			return false;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		if ( OptionResolver::get_flag( FeatureFlagsDefaults::KEY_STICKY_HIDE_WHEN_EMPTY_ENABLED, true ) && WC()->cart->is_empty() ) {
			return false;
		}

		/**
		 * Filters whether the sticky cart root is printed on this request (home, catalog, product, cart, checkout, etc.).
		 *
		 * @param bool $show Default decision.
		 */
		return (bool) apply_filters( 'mp_sticky_custom_cart_should_render_sticky', true );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
