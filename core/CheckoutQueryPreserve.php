<?php
/**
 * Marketing/query params copied from the current request onto the checkout URL.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Same key list is exposed to JS as {@see FrontendFlagResolver} `checkoutPreserveQueryKeys`.
 */
final class CheckoutQueryPreserve {

	/**
	 * Query parameter names allowed when merging onto checkout (UTM, click ids).
	 *
	 * @return string[]
	 */
	public static function allowed_keys() {
		$keys = array(
			'utm_source',
			'utm_medium',
			'utm_campaign',
			'utm_term',
			'utm_content',
			'gclid',
			'fbclid',
			'msclkid',
		);

		/**
		 * Filters which `$_GET` keys may be appended to the sticky checkout link.
		 *
		 * @param string[] $keys Allowed parameter names.
		 */
		return array_values( array_unique( array_map( 'strval', (array) apply_filters( 'mp_sticky_custom_cart_checkout_preserve_query_keys', $keys ) ) ) );
	}

	/**
	 * Append allowed keys from the current request onto a URL (typically checkout).
	 *
	 * @param string $url Target URL.
	 * @return string
	 */
	public static function merge_request_into_url( $url ) {
		$url = (string) $url;
		if ( '' === $url || empty( $_GET ) || ! is_array( $_GET ) ) {
			return $url;
		}

		$allowed = array_flip( self::allowed_keys() );
		$to_add  = array();

		foreach ( $_GET as $key => $value ) {
			if ( ! is_string( $key ) || '' === $key || ! isset( $allowed[ $key ] ) ) {
				continue;
			}
			$value = wp_unslash( $value );
			if ( is_array( $value ) ) {
				continue;
			}
			$to_add[ $key ] = sanitize_text_field( (string) $value );
		}

		if ( empty( $to_add ) ) {
			return $url;
		}

		return add_query_arg( $to_add, $url );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
