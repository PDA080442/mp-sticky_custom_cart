<?php
/**
 * Optional redirect from the WooCommerce cart page URL to the site front (sticky-only flow).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\CheckoutQueryPreserve;
use MpStickyCustomCart\Core\Constants;
use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Runs early on {@see template_redirect} when {@see OptionResolver} `cart_route.redirect_to_home` is enabled.
 */
final class CartRouteRedirectHooks {

	public static function register() {
		add_action( 'template_redirect', array( self::class, 'maybe_redirect_cart' ), 0 );

		/**
		 * Fires when cart route redirect hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_cart_route_redirect_hooks_registered' );
	}

	/**
	 * Redirect `/cart/` (and localized equivalents) to home unless a loop would occur.
	 */
	public static function maybe_redirect_cart() {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		if ( ! OptionResolver::get_setting( 'cart_route.redirect_to_home', false ) ) {
			return;
		}

		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return;
		}

		if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return;
		}

		/**
		 * Short-circuit cart redirect (e.g. allow cart in preview or tests).
		 *
		 * @param bool|null $redirect Whether to redirect. Null = use default logic.
		 */
		$short = apply_filters( 'mp_sticky_custom_cart_cart_redirect_short_circuit', null );
		if ( false === $short ) {
			return;
		}

		$cart_page_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'cart' ) : 0;
		$front_id     = (int) get_option( 'page_on_front' );
		if ( $cart_page_id > 0 && $front_id > 0 && $cart_page_id === $front_id ) {
			self::maybe_log( 'skip cart page is front page (loop risk)' );
			return;
		}

		$had_add_to_cart = isset( $_GET['add-to-cart'] );

		$target = (string) apply_filters( 'mp_sticky_custom_cart_cart_redirect_url', home_url( '/' ) );
		$target = wp_validate_redirect( $target, home_url( '/' ) );

		if ( function_exists( 'wc_get_cart_url' ) ) {
			$cart_url = wc_get_cart_url();
			if ( is_string( $cart_url ) && $cart_url !== '' ) {
				if ( untrailingslashit( $target ) === untrailingslashit( $cart_url ) ) {
					self::maybe_log( 'skip target equals cart URL' );
					return;
				}
			}
		}

		if ( OptionResolver::get_setting( 'cart_route.preserve_marketing_params_on_redirect', true ) ) {
			$target = CheckoutQueryPreserve::merge_request_into_url( $target );
		}

		/**
		 * Final redirect URL after marketing merge (cart page with ?add-to-cart=&quantity=&utm_… → home with UTM only).
		 *
		 * @param string $target          Validated base + optional marketing params.
		 * @param bool   $had_add_to_cart Whether the request had an add-to-cart query arg (product may already be added by Woo).
		 */
		$target = (string) apply_filters( 'mp_sticky_custom_cart_cart_redirect_final_url', $target, $had_add_to_cart );
		$target = wp_validate_redirect( $target, home_url( '/' ) );

		if ( $had_add_to_cart && OptionResolver::get_setting( 'cart_route.track_external_cart_link_hits', false ) ) {
			$n = (int) get_option( Constants::OPTION_EXTERNAL_CART_LINK_HITS, 0 );
			update_option( Constants::OPTION_EXTERNAL_CART_LINK_HITS, $n + 1, false );
		}

		$code = (int) OptionResolver::get_setting( 'cart_route.redirect_status_code', 302 );
		if ( ! in_array( $code, array( 301, 302, 303, 307 ), true ) ) {
			$code = 302;
		}

		self::maybe_log(
			sprintf(
				'redirect status=%d to=%s add_to_cart=%s',
				$code,
				$target,
				$had_add_to_cart ? '1' : '0'
			)
		);

		wp_safe_redirect( $target, $code );
		exit;
	}

	/**
	 * @param string $message Short context (no PII).
	 */
	private static function maybe_log( $message ) {
		if ( ! OptionResolver::get_setting( 'cart_route.log_redirect_events', false ) ) {
			return;
		}

		/**
		 * Filters whether a cart redirect line is written to the debug log.
		 *
		 * @param bool   $log     Default true when logging is enabled in settings.
		 * @param string $message Context string.
		 */
		if ( ! apply_filters( 'mp_sticky_custom_cart_should_log_cart_redirect', true, $message ) ) {
			return;
		}

		$line = '[mp-sticky-custom-cart] cart_redirect ' . $message;
		if ( function_exists( 'wp_debug_log' ) ) {
			wp_debug_log( $line );
		} else {
			error_log( $line );
		}
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
