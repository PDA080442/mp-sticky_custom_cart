<?php
/**
 * AJAX actions for cart snapshot and mutations.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Registers `wp_ajax_*` / `wp_ajax_nopriv_*` handlers.
 */
final class AjaxEndpointsHooks {

	public static function register() {
		add_action( 'wp_ajax_' . Constants::AJAX_ACTION_CART_SNAPSHOT, array( self::class, 'handle_cart_snapshot' ) );
		add_action( 'wp_ajax_nopriv_' . Constants::AJAX_ACTION_CART_SNAPSHOT, array( self::class, 'handle_cart_snapshot' ) );
		add_action( 'wp_ajax_' . Constants::AJAX_ACTION_SET_LINE_QUANTITY, array( self::class, 'handle_set_line_quantity' ) );
		add_action( 'wp_ajax_nopriv_' . Constants::AJAX_ACTION_SET_LINE_QUANTITY, array( self::class, 'handle_set_line_quantity' ) );

		/**
		 * Fires when AJAX endpoint hooks are registered — attach real handlers here.
		 */
		do_action( 'mp_sticky_custom_cart_ajax_endpoints_registered' );
	}

	/**
	 * JSON snapshot for sticky UI (counts, subtotal HTML, line items).
	 */
	public static function handle_cart_snapshot() {
		self::verify_nonce();
		$payload = self::build_cart_snapshot_payload();
		if ( is_wp_error( $payload ) ) {
			wp_send_json_error( array( 'message' => $payload->get_error_message() ), 400 );
		}
		wp_send_json_success( $payload );
	}

	/**
	 * Set quantity for a cart line (used with debounced +/- in drawer).
	 */
	public static function handle_set_line_quantity() {
		self::verify_nonce();
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => 'Cart unavailable.' ), 400 );
		}

		$key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
		$qty = isset( $_POST['quantity'] ) ? (int) wp_unslash( $_POST['quantity'] ) : 0;

		if ( '' === $key ) {
			wp_send_json_error( array( 'message' => 'Missing cart line.' ), 400 );
		}

		if ( $qty < 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid quantity.' ), 400 );
		}

		$cart = WC()->cart;
		$contents = $cart->get_cart();
		if ( ! isset( $contents[ $key ] ) ) {
			wp_send_json_error( array( 'message' => 'Cart line not found.' ), 404 );
		}

		if ( 0 === $qty ) {
			$cart->remove_cart_item( $key );
		} else {
			$ok = $cart->set_quantity( $key, $qty, true );
			if ( ! $ok ) {
				wp_send_json_error( array( 'message' => 'Could not update quantity.' ), 400 );
			}
		}

		$cart->calculate_totals();

		$payload = self::build_cart_snapshot_payload();
		if ( is_wp_error( $payload ) ) {
			wp_send_json_error( array( 'message' => $payload->get_error_message() ), 500 );
		}

		wp_send_json_success( $payload );
	}

	/**
	 * @return array<string, mixed>|\WP_Error
	 */
	private static function build_cart_snapshot_payload() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return new \WP_Error( 'mp_scc_no_cart', 'Cart unavailable.' );
		}

		$cart = WC()->cart;
		$cart->calculate_totals();

		$items = array();
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
			if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
				continue;
			}

			if ( is_callable( array( $cart, 'get_product_subtotal' ) ) ) {
				$line_subtotal_raw = $cart->get_product_subtotal( $product, $cart_item['quantity'] );
			} else {
				$line_subtotal_raw = wc_price( isset( $cart_item['line_subtotal'] ) ? (float) $cart_item['line_subtotal'] : 0 );
			}

			$line_subtotal_html = apply_filters(
				'woocommerce_cart_item_subtotal',
				$line_subtotal_raw,
				$cart_item,
				$cart_item_key
			);

			$name = apply_filters(
				'woocommerce_cart_item_name',
				$product->get_name(),
				$cart_item,
				$cart_item_key
			);

			$permalink = apply_filters(
				'woocommerce_cart_item_permalink',
				$product->is_visible() ? $product->get_permalink( $cart_item ) : '',
				$cart_item,
				$cart_item_key
			);

			$items[] = array(
				'key'                => (string) $cart_item_key,
				'product_id'         => (int) $cart_item['product_id'],
				'name'               => wp_strip_all_tags( (string) $name ),
				'quantity'           => (int) $cart_item['quantity'],
				'line_subtotal_html' => is_string( $line_subtotal_html ) ? $line_subtotal_html : '',
				'permalink'          => is_string( $permalink ) ? $permalink : '',
			);
		}

		$empty          = $cart->is_empty();
		$subtotal_html  = $empty ? wc_price( 0 ) : $cart->get_cart_subtotal();
		$subtotal_html  = is_string( $subtotal_html ) ? $subtotal_html : wc_price( 0 );

		return array(
			'is_empty'         => (bool) $empty,
			'cart_contents_count' => (int) $cart->get_cart_contents_count(),
			'line_count'       => count( $items ),
			'subtotal_html'    => $subtotal_html,
			'items'            => $items,
		);
	}

	private static function verify_nonce() {
		if ( ! check_ajax_referer( Constants::AJAX_NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Bad nonce.' ), 403 );
		}
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
