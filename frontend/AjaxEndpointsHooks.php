<?php
/**
 * AJAX actions for cart snapshot and mutations.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\Config\FeatureFlagsDefaults;
use MpStickyCustomCart\Core\Constants;
use MpStickyCustomCart\Core\Contracts\LoggingServiceInterface;
use MpStickyCustomCart\Core\ErrorLogService;
use MpStickyCustomCart\Core\OptionResolver;

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
		add_action( 'wp_ajax_' . Constants::AJAX_ACTION_ADD_SIMPLE_PRODUCT, array( self::class, 'handle_add_simple_product' ) );
		add_action( 'wp_ajax_nopriv_' . Constants::AJAX_ACTION_ADD_SIMPLE_PRODUCT, array( self::class, 'handle_add_simple_product' ) );
		add_action( 'wp_ajax_' . Constants::AJAX_ACTION_CLEAR_CART, array( self::class, 'handle_clear_cart' ) );
		add_action( 'wp_ajax_nopriv_' . Constants::AJAX_ACTION_CLEAR_CART, array( self::class, 'handle_clear_cart' ) );
		add_action( 'wp_ajax_' . Constants::AJAX_ACTION_REMOVE_CART_LINE, array( self::class, 'handle_remove_cart_line' ) );
		add_action( 'wp_ajax_nopriv_' . Constants::AJAX_ACTION_REMOVE_CART_LINE, array( self::class, 'handle_remove_cart_line' ) );

		/**
		 * Fires when AJAX endpoint hooks are registered — attach real handlers here.
		 */
		do_action( 'mp_sticky_custom_cart_ajax_endpoints_registered' );
	}

	/**
	 * Remove all items from the cart and return the unified snapshot.
	 */
	public static function handle_clear_cart() {
		self::verify_nonce();
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			self::log_clear_cart_failure( 'no_cart', 'Cart unavailable.', array() );
			wp_send_json_error(
				array(
					'message' => __( 'Корзина недоступна.', 'mp-sticky-custom-cart' ),
					'code'    => 'cart_unavailable',
				)
			);
		}

		$cart = WC()->cart;
		if ( $cart->is_empty() ) {
			$payload = self::build_cart_snapshot_payload();
			if ( is_wp_error( $payload ) ) {
				self::log_clear_cart_failure( 'snapshot_failed', $payload->get_error_message(), array( 'phase' => 'already_empty' ) );
				wp_send_json_error(
					array(
						'message' => __( 'Не удалось получить состояние корзины.', 'mp-sticky-custom-cart' ),
						'code'    => 'snapshot_failed',
					)
				);
			}
			wp_send_json_success( $payload );
			return;
		}

		try {
			$cart->empty_cart();
			$cart->calculate_totals();
		} catch ( \Throwable $e ) {
			self::log_clear_cart_failure( 'empty_cart_exception', $e->getMessage(), array() );
			wp_send_json_error(
				array(
					'message' => __( 'Не удалось очистить корзину.', 'mp-sticky-custom-cart' ),
					'code'    => 'clear_failed',
				)
			);
		}

		if ( function_exists( 'wc_clear_notices' ) ) {
			wc_clear_notices();
		}

		$payload = self::build_cart_snapshot_payload();
		if ( is_wp_error( $payload ) ) {
			self::log_clear_cart_failure( 'snapshot_after_clear', $payload->get_error_message(), array() );
			wp_send_json_error(
				array(
					'message' => __( 'Корзина очищена, но не удалось обновить данные.', 'mp-sticky-custom-cart' ),
					'code'    => 'snapshot_failed',
				)
			);
		}

		wp_send_json_success( $payload );
	}

	/**
	 * Remove a single cart line (sticky drawer); returns unified snapshot.
	 */
	public static function handle_remove_cart_line() {
		self::verify_nonce();
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			self::log_remove_line_failure( 'no_cart', 'Cart unavailable.', array() );
			wp_send_json_error(
				array(
					'message' => __( 'Корзина недоступна.', 'mp-sticky-custom-cart' ),
					'code'    => 'cart_unavailable',
				)
			);
		}

		$key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
		if ( '' === $key ) {
			self::log_remove_line_failure( 'missing_key', 'cart_item_key missing.', array() );
			wp_send_json_error(
				array(
					'message' => __( 'Не указана позиция корзины.', 'mp-sticky-custom-cart' ),
					'code'    => 'missing_cart_line',
				)
			);
		}

		$cart     = WC()->cart;
		$contents = $cart->get_cart();
		if ( ! isset( $contents[ $key ] ) ) {
			self::log_remove_line_failure( 'cart_line_not_found', 'Line not in cart.', array( 'cart_item_key' => $key ) );
			wp_send_json_error(
				array(
					'message' => __( 'Позиция в корзине не найдена.', 'mp-sticky-custom-cart' ),
					'code'    => 'cart_line_not_found',
				)
			);
		}

		$removed = $cart->remove_cart_item( $key );
		if ( ! $removed ) {
			self::log_remove_line_failure( 'remove_failed', 'remove_cart_item returned false.', array( 'cart_item_key' => $key ) );
			wp_send_json_error(
				array(
					'message' => __( 'Не удалось удалить позицию.', 'mp-sticky-custom-cart' ),
					'code'    => 'remove_failed',
				)
			);
		}

		$cart->calculate_totals();
		if ( function_exists( 'wc_clear_notices' ) ) {
			wc_clear_notices();
		}

		$payload = self::build_cart_snapshot_payload();
		if ( is_wp_error( $payload ) ) {
			self::log_remove_line_failure( 'snapshot_failed', $payload->get_error_message(), array( 'cart_item_key' => $key ) );
			wp_send_json_error(
				array(
					'message' => __( 'Позиция удалена, но не удалось обновить данные корзины.', 'mp-sticky-custom-cart' ),
					'code'    => 'snapshot_failed',
				)
			);
		}

		wp_send_json_success( $payload );
	}

	/**
	 * JSON snapshot for sticky UI (counts, subtotal HTML, line items).
	 */
	public static function handle_cart_snapshot() {
		self::verify_nonce();
		$include_shell = false;
		if ( isset( $_POST['include_sticky_shell'] ) ) {
			$raw = wp_unslash( $_POST['include_sticky_shell'] );
			$include_shell = ( true === $raw || 1 === $raw || '1' === (string) $raw || 'true' === strtolower( (string) $raw ) );
		}
		$payload = self::build_cart_snapshot_payload(
			array(
				'include_sticky_shell' => $include_shell,
			)
		);
		if ( is_wp_error( $payload ) ) {
			self::log_snapshot_failure( $payload->get_error_code(), $payload->get_error_message() );
			wp_send_json_error(
				array(
					'message' => __( 'Корзина недоступна.', 'mp-sticky-custom-cart' ),
					'code'    => 'cart_unavailable',
				)
			);
		}
		wp_send_json_success( $payload );
	}

	/**
	 * Set quantity for a cart line (used with debounced +/- in drawer).
	 */
	public static function handle_set_line_quantity() {
		self::verify_nonce();
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			self::log_line_quantity_failure( 'cart_unavailable', 'Cart unavailable.', array() );
			wp_send_json_error(
				array(
					'message' => __( 'Корзина недоступна.', 'mp-sticky-custom-cart' ),
					'code'    => 'cart_unavailable',
				)
			);
		}

		$key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
		$qty = isset( $_POST['quantity'] ) ? (int) wp_unslash( $_POST['quantity'] ) : 0;

		if ( '' === $key ) {
			self::log_line_quantity_failure( 'missing_cart_line', 'cart_item_key missing.', array() );
			wp_send_json_error(
				array(
					'message' => __( 'Не указана позиция корзины.', 'mp-sticky-custom-cart' ),
					'code'    => 'missing_cart_line',
				)
			);
		}

		if ( $qty < 0 ) {
			self::log_line_quantity_failure( 'invalid_quantity', 'Negative quantity.', array( 'quantity' => $qty ) );
			wp_send_json_error(
				array(
					'message' => __( 'Некорректное количество.', 'mp-sticky-custom-cart' ),
					'code'    => 'invalid_quantity',
				)
			);
		}

		$cart = WC()->cart;
		$contents = $cart->get_cart();
		if ( ! isset( $contents[ $key ] ) ) {
			self::log_line_quantity_failure( 'cart_line_not_found', 'Line not in cart.', array( 'cart_item_key' => $key ) );
			wp_send_json_error(
				array(
					'message' => __( 'Позиция в корзине не найдена.', 'mp-sticky-custom-cart' ),
					'code'    => 'cart_line_not_found',
				)
			);
		}

		$cart_item = $contents[ $key ];
		$product   = isset( $cart_item['data'] ) ? $cart_item['data'] : null;

		if ( 0 === $qty ) {
			$cart->remove_cart_item( $key );
		} else {
			if ( ! is_a( $product, 'WC_Product' ) ) {
				self::log_line_quantity_failure( 'invalid_product', 'Product object missing.', array( 'cart_item_key' => $key ) );
				wp_send_json_error(
					array(
						'message' => __( 'Товар для позиции недоступен.', 'mp-sticky-custom-cart' ),
						'code'    => 'invalid_product',
					)
				);
			}

			$min_req = 1;
			if ( is_callable( array( $product, 'get_min_purchase_quantity' ) ) ) {
				$mp = (int) $product->get_min_purchase_quantity();
				if ( $mp > 1 ) {
					$min_req = $mp;
				}
			}
			if ( $qty < $min_req ) {
				self::log_line_quantity_failure(
					'below_min_quantity',
					'Below min purchase quantity.',
					array( 'cart_item_key' => $key, 'quantity' => $qty, 'min' => $min_req )
				);
				wp_send_json_error(
					array(
						'message' => sprintf(
							/* translators: %d: minimum quantity for the line */
							__( 'Минимальное количество для этой позиции: %d.', 'mp-sticky-custom-cart' ),
							$min_req
						),
						'code'    => 'below_min_quantity',
					)
				);
			}

			$max_q = $product->get_max_purchase_quantity();
			if ( is_numeric( $max_q ) && (int) $max_q > 0 && $qty > (int) $max_q ) {
				self::log_line_quantity_failure(
					'above_max_quantity',
					'Above max purchase quantity.',
					array( 'cart_item_key' => $key, 'quantity' => $qty, 'max' => $max_q )
				);
				wp_send_json_error(
					array(
						'message' => sprintf(
							/* translators: %d: maximum quantity that can be purchased */
							__( 'Доступно не более %d шт.', 'mp-sticky-custom-cart' ),
							(int) $max_q
						),
						'code'    => 'above_max_quantity',
					)
				);
			}

			$ok = $cart->set_quantity( $key, $qty, true );
			if ( ! $ok ) {
				self::log_line_quantity_failure( 'set_quantity_failed', 'set_quantity returned false.', array( 'cart_item_key' => $key, 'quantity' => $qty ) );
				wp_send_json_error(
					array(
						'message' => __( 'Не удалось обновить количество.', 'mp-sticky-custom-cart' ),
						'code'    => 'set_quantity_failed',
					)
				);
			}
		}

		$cart->calculate_totals();

		$payload = self::build_cart_snapshot_payload();
		if ( is_wp_error( $payload ) ) {
			self::log_line_quantity_failure( 'snapshot_failed', $payload->get_error_message(), array( 'phase' => 'after_set_quantity' ) );
			wp_send_json_error(
				array(
					'message' => __( 'Не удалось получить состояние корзины.', 'mp-sticky-custom-cart' ),
					'code'    => 'snapshot_failed',
				)
			);
		}

		wp_send_json_success( $payload );
	}

	/**
	 * Add a simple product to the cart (catalog image); validates stock and returns unified snapshot.
	 */
	public static function handle_add_simple_product() {
		self::verify_nonce();

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			self::log_add_simple_failure(
				'cart_unavailable',
				'Cart not available.',
				array()
			);
			wp_send_json_error(
				array(
					'message' => __( 'Корзина недоступна.', 'mp-sticky-custom-cart' ),
					'code'    => 'cart_unavailable',
				)
			);
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$quantity   = isset( $_POST['quantity'] ) ? (int) wp_unslash( $_POST['quantity'] ) : 0;

		if ( $product_id < 1 ) {
			self::log_add_simple_failure(
				'invalid_product_id',
				'Invalid product_id.',
				array( 'product_id' => $product_id )
			);
			wp_send_json_error(
				array(
					'message' => __( 'Некорректный идентификатор товара.', 'mp-sticky-custom-cart' ),
					'code'    => 'invalid_product_id',
				)
			);
		}

		if ( $quantity < 1 ) {
			self::log_add_simple_failure(
				'invalid_quantity',
				'Quantity must be at least 1.',
				array( 'product_id' => $product_id, 'quantity' => $quantity )
			);
			wp_send_json_error(
				array(
					'message' => __( 'Количество должно быть не меньше 1.', 'mp-sticky-custom-cart' ),
					'code'    => 'invalid_quantity',
				)
			);
		}

		/**
		 * Allow blocking add-to-cart before product load (e.g. maintenance).
		 *
		 * @param bool $allow       Default true.
		 * @param int  $product_id  Product ID.
		 * @param int  $quantity    Requested quantity.
		 */
		if ( ! apply_filters( 'mp_sticky_custom_cart_before_add_simple_product', true, $product_id, $quantity ) ) {
			self::log_add_simple_failure(
				'blocked',
				'Blocked by filter.',
				array( 'product_id' => $product_id )
			);
			wp_send_json_error(
				array(
					'message' => __( 'Добавление товара сейчас недоступно.', 'mp-sticky-custom-cart' ),
					'code'    => 'blocked',
				)
			);
		}

		$product = wc_get_product( $product_id );
		if ( ! $product instanceof \WC_Product ) {
			self::log_add_simple_failure(
				'product_not_found',
				'Product not found.',
				array( 'product_id' => $product_id )
			);
			wp_send_json_error(
				array(
					'message' => __( 'Товар не найден.', 'mp-sticky-custom-cart' ),
					'code'    => 'product_not_found',
				)
			);
		}

		if ( ! $product->is_type( 'simple' ) ) {
			self::log_add_simple_failure(
				'not_simple',
				'Product is not simple.',
				array( 'product_id' => $product_id, 'type' => $product->get_type() )
			);
			wp_send_json_error(
				array(
					'message' => __( 'Можно добавить только простой товар без вариаций.', 'mp-sticky-custom-cart' ),
					'code'    => 'not_simple',
				)
			);
		}

		if ( ! $product->is_purchasable() ) {
			self::log_add_simple_failure(
				'not_purchasable',
				'Product not purchasable.',
				array( 'product_id' => $product_id )
			);
			wp_send_json_error(
				array(
					'message' => __( 'Этот товар нельзя купить.', 'mp-sticky-custom-cart' ),
					'code'    => 'not_purchasable',
				)
			);
		}

		if ( ! $product->is_in_stock() ) {
			self::log_add_simple_failure(
				'out_of_stock',
				'Product out of stock.',
				array( 'product_id' => $product_id )
			);
			wp_send_json_error(
				array(
					'message' => __( 'Товара нет в наличии.', 'mp-sticky-custom-cart' ),
					'code'    => 'out_of_stock',
				)
			);
		}

		$min_q = (int) $product->get_min_purchase_quantity();
		if ( $min_q < 1 ) {
			$min_q = 1;
		}
		if ( $quantity < $min_q ) {
			self::log_add_simple_failure(
				'below_min_quantity',
				'Below minimum purchase quantity.',
				array( 'product_id' => $product_id, 'quantity' => $quantity, 'min' => $min_q )
			);
			wp_send_json_error(
				array(
					'message' => __( 'Слишком малое количество для этого товара.', 'mp-sticky-custom-cart' ),
					'code'    => 'below_min_quantity',
				)
			);
		}

		$max_q = $product->get_max_purchase_quantity();
		if ( is_numeric( $max_q ) && (int) $max_q > 0 && $quantity > (int) $max_q ) {
			self::log_add_simple_failure(
				'above_max_quantity',
				'Above max purchase quantity.',
				array( 'product_id' => $product_id, 'quantity' => $quantity, 'max' => $max_q )
			);
			wp_send_json_error(
				array(
					'message' => __( 'Превышено максимально допустимое количество.', 'mp-sticky-custom-cart' ),
					'code'    => 'above_max_quantity',
				)
			);
		}

		if ( is_callable( array( $product, 'has_enough_stock' ) ) && ! $product->has_enough_stock( $quantity ) ) {
			self::log_add_simple_failure(
				'insufficient_stock',
				'Insufficient stock for requested quantity.',
				array( 'product_id' => $product_id, 'quantity' => $quantity )
			);
			wp_send_json_error(
				array(
					'message' => __( 'Недостаточно товара на складе.', 'mp-sticky-custom-cart' ),
					'code'    => 'insufficient_stock',
				)
			);
		}

		wc_clear_notices();

		$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity );

		if ( ! $cart_item_key ) {
			$woo_messages = self::collect_wc_error_notice_texts();
			$message      = self::map_woo_errors_to_message( $woo_messages );
			wc_clear_notices();

			self::log_add_simple_failure(
				'add_to_cart_failed',
				$message,
				array(
					'product_id' => $product_id,
					'quantity'   => $quantity,
					'notices'    => $woo_messages,
				)
			);

			/**
			 * Filters the user-visible error when {@see WC_Cart::add_to_cart} returns false.
			 *
			 * @param string   $message      Localized message.
			 * @param string[] $woo_messages Raw notice strings from WooCommerce.
			 * @param int      $product_id   Product ID.
			 */
			$message = (string) apply_filters( 'mp_sticky_custom_cart_add_simple_product_error_message', $message, $woo_messages, $product_id );

			wp_send_json_error(
				array(
					'message' => $message,
					'code'    => 'add_to_cart_failed',
				)
			);
		}

		wc_clear_notices();
		WC()->cart->calculate_totals();

		$payload = self::build_cart_snapshot_payload();
		if ( is_wp_error( $payload ) ) {
			self::log_add_simple_failure(
				'snapshot_after_add_failed',
				$payload->get_error_message(),
				array( 'product_id' => $product_id )
			);
			wp_send_json_error(
				array(
					'message' => __( 'Товар добавлен, но не удалось обновить данные корзины.', 'mp-sticky-custom-cart' ),
					'code'    => 'snapshot_failed',
				)
			);
		}

		wp_send_json_success( $payload );
	}

	/**
	 * @param array<string, mixed> $opts Optional. `include_sticky_shell` (bool): append server-rendered sticky HTML for deferred mount (dp §18.1).
	 * @return array<string, mixed>|\WP_Error
	 */
	private static function build_cart_snapshot_payload( array $opts = array() ) {
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

			$thumb_html = $product->get_image(
				'woocommerce_thumbnail',
				array(
					'class'   => 'mp-scc-line__img',
					'alt'     => '',
					'loading' => 'lazy',
				),
				true
			);
			$thumb_html = is_string( $thumb_html ) ? $thumb_html : '';
			if ( '' === trim( $thumb_html ) && function_exists( 'wc_placeholder_img' ) ) {
				$thumb_html = wc_placeholder_img(
					'woocommerce_thumbnail',
					array(
						'class' => 'mp-scc-line__img mp-scc-line__img--placeholder',
						'alt'   => '',
					)
				);
			}
			$thumb_html = wp_kses_post( is_string( $thumb_html ) ? $thumb_html : '' );

			$price_raw       = $cart->get_product_price( $product );
			$line_price_html = apply_filters( 'woocommerce_cart_item_price', $price_raw, $cart_item, $cart_item_key );
			$line_price_html = is_string( $line_price_html ) ? wp_kses_post( $line_price_html ) : '';

			$max_qty   = $product->get_max_purchase_quantity();
			$max_q_out = null;
			if ( is_numeric( $max_qty ) && (int) $max_qty > 0 ) {
				$max_q_out = (int) $max_qty;
			}

			$stock_notice = self::build_cart_line_stock_notice( $product, $cart_item );

			$line_data = array(
				'key'                => (string) $cart_item_key,
				'snapshot_line_id'   => (string) $cart_item_key,
				'product_id'         => (int) $cart_item['product_id'],
				'variation_id'       => isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0,
				'name'               => wp_strip_all_tags( (string) $name ),
				'quantity'           => (int) $cart_item['quantity'],
				'line_subtotal_html' => is_string( $line_subtotal_html ) ? $line_subtotal_html : '',
				'line_price_html'    => $line_price_html,
				'thumbnail_html'     => $thumb_html,
				'permalink'          => is_string( $permalink ) ? $permalink : '',
				'stock_status'       => (string) $product->get_stock_status(),
				'stock_notice'       => $stock_notice,
				'max_quantity'       => $max_q_out,
			);

			/**
			 * Filters one cart line in the sticky drawer snapshot (add fields for custom templates).
			 *
			 * @param array<string, mixed> $line_data    Line payload for JS.
			 * @param array<string, mixed> $cart_item    WooCommerce cart row.
			 * @param string               $cart_item_key Line key.
			 */
			$items[] = apply_filters( 'mp_sticky_custom_cart_cart_line_snapshot', $line_data, $cart_item, $cart_item_key );
		}

		$empty          = $cart->is_empty();
		$subtotal_html  = $empty ? wc_price( 0 ) : $cart->get_cart_subtotal();
		$subtotal_html  = is_string( $subtotal_html ) ? $subtotal_html : wc_price( 0 );

		$out = array(
			'is_empty'              => (bool) $empty,
			'cart_contents_count'   => (int) $cart->get_cart_contents_count(),
			'line_count'            => count( $items ),
			'subtotal_html'         => $subtotal_html,
			'items'                 => $items,
			'snapshot_ts'           => time(),
		);

		$include_shell = ! empty( $opts['include_sticky_shell'] )
			&& ! $empty
			&& OptionResolver::get_flag( FeatureFlagsDefaults::KEY_STICKY_CART_ENABLED, true )
			&& OptionResolver::get_flag( FeatureFlagsDefaults::KEY_STICKY_HIDE_WHEN_EMPTY_ENABLED, true );

		/**
		 * Filters whether cart snapshot may embed sticky shell HTML (deferred mount after first add).
		 *
		 * @param bool                 $include_shell Whether shell HTML will be appended.
		 * @param bool                 $empty         Whether Woo cart is empty.
		 * @param array<string, mixed> $opts          Options passed to {@see build_cart_snapshot_payload()}.
		 */
		$include_shell = (bool) apply_filters( 'mp_sticky_custom_cart_include_sticky_shell_in_snapshot', $include_shell, $empty, $opts );

		if ( $include_shell && StickyCartVisibility::should_render_sticky() ) {
			$renderer = new StickyCartRenderer();
			$out['sticky_shell_html']     = $renderer->render();
			$out['sticky_body_classes']   = StickyCartRenderHooks::collect_sticky_body_classes();
		}

		return $out;
	}

	/**
	 * Short availability hint when stock or purchasability changes (drawer line).
	 *
	 * @param \WC_Product          $product   Cart line product.
	 * @param array<string, mixed> $cart_item Cart row.
	 */
	private static function build_cart_line_stock_notice( $product, array $cart_item ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return '';
		}
		if ( ! $product->is_purchasable() ) {
			return __( 'Недоступно к покупке', 'mp-sticky-custom-cart' );
		}
		$status = $product->get_stock_status();
		if ( 'outofstock' === $status && ! $product->backorders_allowed() ) {
			return __( 'Нет в наличии', 'mp-sticky-custom-cart' );
		}
		$max = $product->get_max_purchase_quantity();
		if ( is_numeric( $max ) && (int) $max > 0 && isset( $cart_item['quantity'] ) && (int) $cart_item['quantity'] > (int) $max ) {
			return sprintf(
				/* translators: %d: maximum quantity that can be purchased */
				__( 'Доступно не более %d шт.', 'mp-sticky-custom-cart' ),
				(int) $max
			);
		}
		if ( 'onbackorder' === $status && $product->backorders_require_notification() ) {
			return __( 'Под заказ', 'mp-sticky-custom-cart' );
		}
		return '';
	}

	private static function verify_nonce() {
		if ( ! check_ajax_referer( Constants::AJAX_NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Неверный запрос безопасности. Обновите страницу.', 'mp-sticky-custom-cart' ),
					'code'    => 'invalid_nonce',
				)
			);
		}
	}

	/**
	 * @return string[]
	 */
	private static function collect_wc_error_notice_texts() {
		if ( ! function_exists( 'wc_get_notices' ) ) {
			return array();
		}
		$notices = wc_get_notices( 'error' );
		if ( ! is_array( $notices ) ) {
			return array();
		}
		$out = array();
		foreach ( $notices as $n ) {
			if ( isset( $n['notice'] ) && is_string( $n['notice'] ) ) {
				$out[] = wp_strip_all_tags( $n['notice'] );
			}
		}
		return $out;
	}

	/**
	 * @param string[] $woo_messages
	 */
	private static function map_woo_errors_to_message( array $woo_messages ) {
		if ( array() !== $woo_messages ) {
			return $woo_messages[0];
		}
		return __( 'Не удалось добавить товар в корзину.', 'mp-sticky-custom-cart' );
	}

	/**
	 * @param string               $code    Stable error code.
	 * @param string               $message Technical message (English OK).
	 * @param array<string, mixed> $context Extra context (product_id, notices, …).
	 */
	private static function log_add_simple_failure( $code, $message, array $context ) {
		ErrorLogService::instance()->log(
			LoggingServiceInterface::LEVEL_ERROR,
			(string) $message,
			array(
				'source'   => 'ajax_add_simple_product',
				'endpoint' => Constants::AJAX_ACTION_ADD_SIMPLE_PRODUCT,
				'code'     => (string) $code,
				'payload'  => self::summarize_post_fields( array( 'product_id', 'quantity' ) ),
				'context'  => $context,
			)
		);
	}

	/**
	 * @param string               $code    Stable error code.
	 * @param string               $message Technical message.
	 * @param array<string, mixed> $context Extra context.
	 */
	private static function log_clear_cart_failure( $code, $message, array $context ) {
		ErrorLogService::instance()->log(
			LoggingServiceInterface::LEVEL_ERROR,
			(string) $message,
			array(
				'source'   => 'ajax_clear_cart',
				'endpoint' => Constants::AJAX_ACTION_CLEAR_CART,
				'code'     => (string) $code,
				'payload'  => self::summarize_post_fields( array() ),
				'context'  => $context,
			)
		);
	}

	/**
	 * @param string               $code    Stable error code.
	 * @param string               $message Technical message.
	 * @param array<string, mixed> $context Extra context.
	 */
	private static function log_remove_line_failure( $code, $message, array $context ) {
		ErrorLogService::instance()->log(
			LoggingServiceInterface::LEVEL_ERROR,
			(string) $message,
			array(
				'source'   => 'ajax_remove_cart_line',
				'endpoint' => Constants::AJAX_ACTION_REMOVE_CART_LINE,
				'code'     => (string) $code,
				'payload'  => self::summarize_post_fields( array( 'cart_item_key' ) ),
				'context'  => $context,
			)
		);
	}

	/**
	 * @param string               $code    Error code.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Extra context.
	 */
	private static function log_line_quantity_failure( $code, $message, array $context ) {
		ErrorLogService::instance()->log(
			LoggingServiceInterface::LEVEL_ERROR,
			(string) $message,
			array(
				'source'   => 'ajax_set_line_quantity',
				'endpoint' => Constants::AJAX_ACTION_SET_LINE_QUANTITY,
				'code'     => (string) $code,
				'payload'  => self::summarize_post_fields( array( 'cart_item_key', 'quantity' ) ),
				'context'  => $context,
			)
		);
	}

	/**
	 * @param string $code    WP_Error code.
	 * @param string $message Error message.
	 */
	private static function log_snapshot_failure( $code, $message ) {
		ErrorLogService::instance()->log(
			LoggingServiceInterface::LEVEL_ERROR,
			(string) $message,
			array(
				'source'   => 'ajax_cart_snapshot',
				'endpoint' => Constants::AJAX_ACTION_CART_SNAPSHOT,
				'code'     => (string) $code,
				'payload'  => self::summarize_post_fields( array() ),
				'context'  => array(),
			)
		);
	}

	/**
	 * Safe subset of $_POST for diagnostics (whitelisted keys only).
	 *
	 * @param list<string> $keys POST keys to copy.
	 * @return array<string, mixed>
	 */
	private static function summarize_post_fields( array $keys ) {
		$out = array();
		foreach ( $keys as $k ) {
			if ( ! isset( $_POST[ $k ] ) ) {
				continue;
			}
			$raw = wp_unslash( $_POST[ $k ] );
			if ( 'product_id' === $k || 'quantity' === $k ) {
				$out[ $k ] = (int) $raw;
			} elseif ( 'cart_item_key' === $k ) {
				$out[ $k ] = substr( sanitize_text_field( (string) $raw ), 0, 64 );
			} else {
				$out[ $k ] = is_scalar( $raw ) ? substr( (string) $raw, 0, 200 ) : '[complex]';
			}
		}
		return $out;
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
