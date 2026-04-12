<?php
/**
 * WooCommerce add-to-cart success notice: remove the default "View cart" link (sticky cart UX).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Filters {@see wc_add_to_cart_message_html} output (and legacy alias filter when present).
 */
final class AddToCartMessageHooks {

	public static function register() {
		add_filter( 'wc_add_to_cart_message_html', array( self::class, 'strip_view_cart_link' ), 20, 3 );
		add_filter( 'woocommerce_add_to_cart_message_html', array( self::class, 'strip_view_cart_link' ), 20, 4 );

		/**
		 * Fires when add-to-cart message hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_add_to_cart_message_hooks_registered' );
	}

	/**
	 * @param string       $message   HTML notice body.
	 * @param mixed        $products  Product id(s) / qty map (unused).
	 * @param bool|null    $show_qty  Whether quantities shown (unused).
	 * @param string|null  $cart_url  Optional cart URL (Woo 4+ fourth arg on alias filter).
	 * @return string
	 */
	public static function strip_view_cart_link( $message, $products = null, $show_qty = null, $cart_url = null ) {
		unset( $products, $show_qty );

		if ( ! is_string( $message ) || '' === $message ) {
			return $message;
		}

		if ( ! OptionResolver::get_setting( 'notices.remove_view_cart_link', true ) ) {
			return $message;
		}

		/**
		 * Return false to keep the default WooCommerce link in the notice.
		 *
		 * @param bool $strip Default true when the setting is on.
		 */
		if ( ! apply_filters( 'mp_sticky_custom_cart_strip_view_cart_from_notice', true ) ) {
			return $message;
		}

		$href_cart = is_string( $cart_url ) && '' !== $cart_url ? $cart_url : null;
		$stripped  = self::strip_cart_anchor_html( $message, $href_cart );

		/**
		 * Filters the add-to-cart notice HTML after removing the cart link.
		 *
		 * @param string $stripped Cleaned HTML.
		 * @param string $message  Original HTML.
		 */
		return (string) apply_filters( 'mp_sticky_custom_cart_add_to_cart_notice_html', $stripped, $message );
	}

	/**
	 * Remove anchor pointing to the cart page or using Woo’s `wc-forward` class.
	 *
	 * @param string      $message  HTML.
	 * @param string|null $cart_url Href from Woo filter when available.
	 * @return string
	 */
	private static function strip_cart_anchor_html( $message, $cart_url = null ) {
		$out = $message;

		if ( ! is_string( $cart_url ) || '' === $cart_url ) {
			$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';
		}

		if ( is_string( $cart_url ) && '' !== $cart_url ) {
			$normalized = untrailingslashit( $cart_url );
			$escaped    = preg_quote( $normalized, '/' );
			// href equals cart URL (optional slash before closing quote).
			$out = (string) preg_replace(
				'/\<a\b[^>]*\bhref\s*=\s*([\'"])' . $escaped . '(?:\/)?\\1[^>]*\>.*?\<\/a\>/is',
				'',
				$out
			);
			// Relative href="/cart/".
			$path = wp_parse_url( $cart_url, PHP_URL_PATH );
			if ( is_string( $path ) && '' !== $path && '/' !== $path ) {
				$path_q = preg_quote( untrailingslashit( $path ), '/' );
				$out    = (string) preg_replace(
					'/\<a\b[^>]*\bhref\s*=\s*([\'"])' . $path_q . '(?:\/)?\\1[^>]*\>.*?\<\/a\>/is',
					'',
					$out
				);
			}
		}

		if ( $out === $message ) {
			// Themes may reorder attributes: target class wc-forward first.
			$out = (string) preg_replace( '/\<a\b[^>]*\bwc-forward\b[^>]*\>.*?\<\/a\>/is', '', $message );
		}

		$out = trim( preg_replace( '/\s{2,}/u', ' ', $out ) );

		return $out;
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
