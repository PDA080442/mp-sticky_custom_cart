<?php
/**
 * Optional WooCommerce shop loop: wrap the loop item in one anchor (standard Woo hooks only).
 *
 * Does **not** run for Elementor / Liquid / Hub lists that never call
 * {@see woocommerce_before_shop_loop_item}.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the default loop thumbnail link + duplicate add-to-cart button, then opens/closes either
 * an add-to-cart URL (simple, in stock, purchasable) or the product permalink.
 */
final class ShopLoopAddToCartWrapper {

	/**
	 * @var bool
	 */
	private $wrap_open = false;

	/**
	 * @var bool
	 */
	private $hooks_registered = false;

	/**
	 * @return void
	 */
	public static function register() {
		$i = new self();
		add_action( 'woocommerce_init', array( $i, 'maybe_register_hooks' ), 20 );
		add_filter( 'body_class', array( $i, 'filter_body_class' ) );
	}

	/**
	 * @return bool
	 */
	private function is_wrap_setting_on() {
		$on = (bool) OptionResolver::get_setting( 'catalog.wrap_loop_item_add_to_cart', false );

		/**
		 * Filters whether the shop-loop wrapper is enabled (after reading settings).
		 *
		 * @param bool $on Default from settings.
		 */
		return (bool) apply_filters( 'mp_sticky_custom_cart_shop_loop_wrap_enabled', $on );
	}

	/**
	 * @return void
	 */
	public function maybe_register_hooks() {
		if ( $this->hooks_registered || is_admin() ) {
			return;
		}

		if ( ! $this->is_wrap_setting_on() ) {
			return;
		}

		remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

		add_action( 'woocommerce_before_shop_loop_item', array( $this, 'open_wrapper' ), 5 );
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'close_wrapper' ), 15 );

		$this->hooks_registered = true;
	}

	/**
	 * @param array<int, string> $classes
	 * @return array<int, string>
	 */
	public function filter_body_class( $classes ) {
		if ( is_admin() || ! $this->is_wrap_setting_on() ) {
			return $classes;
		}
		$classes[] = 'mp-scc-shop-loop-wrap-atc';
		return $classes;
	}

	/**
	 * @return void
	 */
	public function open_wrapper() {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$this->wrap_open = false;

		if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
			$url = $product->add_to_cart_url();
			$id  = $product->get_id();
			$sku = $product->get_sku();

			echo '<a href="' . esc_url( $url ) . '" class="mp-scc-loop-wrap-atc button product_type_simple add_to_cart_button ajax_add_to_cart" data-product_id="' . esc_attr( (string) $id ) . '" data-product_sku="' . esc_attr( $sku ) . '" data-quantity="1" rel="nofollow" aria-label="' . esc_attr( $this->get_add_to_cart_aria_label( $product ) ) . '">';
			$this->wrap_open = true;
			return;
		}

		echo '<a href="' . esc_url( $product->get_permalink() ) . '" class="mp-scc-loop-wrap-permalink">';
		$this->wrap_open = true;
	}

	/**
	 * @return void
	 */
	public function close_wrapper() {
		if ( ! $this->wrap_open ) {
			return;
		}
		echo '</a>';
		$this->wrap_open = false;
	}

	/**
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	private function get_add_to_cart_aria_label( \WC_Product $product ) {
		$name = $product->get_name();
		if ( '' === $name ) {
			return __( 'Add to cart', 'mp-sticky-custom-cart' );
		}
		return sprintf(
			/* translators: %s: product name */
			__( 'Add to cart: %s', 'mp-sticky-custom-cart' ),
			$name
		);
	}
}
