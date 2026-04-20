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
		if ( ! self::passes_template_scope_policy() ) {
			return false;
		}
		if ( self::is_excluded_by_url_policy() ) {
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

	/**
	 * Visibility policy: default is "all frontend templates"; optional Woo-only scope.
	 *
	 * @return bool
	 */
	private static function passes_template_scope_policy() {
		$show_everywhere = (bool) OptionResolver::get_setting( 'sticky_cart.visibility_show_on_all_templates', true );
		if ( $show_everywhere ) {
			return true;
		}

		return self::is_woocommerce_context();
	}

	/**
	 * Matches current request URL/path against newline-delimited exclusions.
	 *
	 * @return bool
	 */
	private static function is_excluded_by_url_policy() {
		$raw = OptionResolver::get_setting( 'sticky_cart.visibility_excluded_urls', '' );
		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return false;
		}
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		if ( ! is_array( $lines ) || ! $lines ) {
			return false;
		}
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '/';
		if ( '' === $request_uri ) {
			$request_uri = '/';
		}
		$current_url = home_url( $request_uri );
		$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
		$path_query  = ltrim( $request_uri, '/' );

		foreach ( $lines as $line ) {
			$rule = trim( (string) $line );
			if ( '' === $rule || '#' === substr( $rule, 0, 1 ) ) {
				continue;
			}
			if ( self::match_url_rule( $rule, $current_url, $request_uri, $path, $path_query ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $rule       Configured rule line.
	 * @param string $current_url Full absolute current URL.
	 * @param string $request_uri Raw request URI.
	 * @param string $path       Current URL path.
	 * @param string $path_query Current URI without leading slash.
	 * @return bool
	 */
	private static function match_url_rule( $rule, $current_url, $request_uri, $path, $path_query ) {
		if ( false !== strpos( $rule, '*' ) ) {
			$quoted = preg_quote( $rule, '/' );
			$regex  = '/^' . str_replace( '\*', '.*', $quoted ) . '$/i';
			return (bool) preg_match( $regex, $current_url )
				|| (bool) preg_match( $regex, $request_uri )
				|| (bool) preg_match( $regex, ltrim( $request_uri, '/' ) );
		}
		if ( 0 === strpos( $rule, '/' ) ) {
			return 0 === strpos( $request_uri, $rule ) || 0 === strpos( $path, $rule );
		}
		return false !== stripos( $current_url, $rule )
			|| false !== stripos( $request_uri, $rule )
			|| false !== stripos( $path_query, $rule );
	}

	/**
	 * @return bool
	 */
	private static function is_woocommerce_context() {
		if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
			return true;
		}
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return true;
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return true;
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return true;
		}
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() ) {
			return true;
		}
		return false;
	}
}
