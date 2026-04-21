<?php
/**
 * Default user-visible strings (overridable from admin; translate via text domain).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Keys are stable identifiers for Settings API + JS dictionary.
 *
 * Resolved strings: {@see \MpStickyCustomCart\Core\OptionResolver::get_labels()} applies
 * empty fallbacks and filters {@see 'mp_sticky_custom_cart_labels'} and {@see 'mp_sticky_custom_cart_label'}.
 */
final class UiLabelsDefaults {

	public const KEY_CATALOG_CART_ICON   = 'catalog_cart_icon';
	public const KEY_MORE_INFO           = 'more_info';
	public const KEY_OUT_OF_STOCK        = 'out_of_stock';
	public const KEY_CLEAR_CART          = 'clear_cart';
	/** Aria/title while clear-cart AJAX is in flight (icon toolbar + all clear controls). */
	public const KEY_CLEAR_CART_IN_PROGRESS = 'clear_cart_in_progress';
	public const KEY_CART_CLEARED        = 'cart_cleared';
	public const KEY_CHECKOUT            = 'checkout';
	public const KEY_VARIATION_REQUIRED  = 'variation_required';
	public const KEY_SINGLE_ADD_SUCCESS  = 'single_add_success';
	public const KEY_DRAWER_EMPTY        = 'drawer_empty';
	public const KEY_DRAWER_EMPTY_HINT   = 'drawer_empty_hint';
	public const KEY_DRAWER_REMOVE_LINE  = 'drawer_remove_line';
	public const KEY_LINE_REMOVED        = 'line_removed';
	/** Tri-state panel B + drawer C: metric row label (cart line count). */
	public const KEY_TRISTATE_METRIC_LINES = 'tristate_metric_lines';
	/** Tri-state: total pieces label (sum of quantities). */
	public const KEY_TRISTATE_METRIC_QTY   = 'tristate_metric_qty';
	/** Tri-state: subtotal row label. */
	public const KEY_TRISTATE_METRIC_TOTAL = 'tristate_metric_total';

	/**
	 * @return array<string, string>
	 */
	public static function get() {
		return array(
			self::KEY_CATALOG_CART_ICON  => __( 'Добавить в корзину', 'mp-sticky-custom-cart' ),
			self::KEY_MORE_INFO          => __( 'Подробнее о товаре', 'mp-sticky-custom-cart' ),
			self::KEY_OUT_OF_STOCK       => __( 'Товара нет в наличии', 'mp-sticky-custom-cart' ),
			self::KEY_CLEAR_CART         => __( 'Очистить корзину', 'mp-sticky-custom-cart' ),
			self::KEY_CLEAR_CART_IN_PROGRESS => __( 'Очистка корзины…', 'mp-sticky-custom-cart' ),
			self::KEY_CART_CLEARED       => __( 'Корзина очищена', 'mp-sticky-custom-cart' ),
			self::KEY_CHECKOUT            => __( 'Оформить заказ', 'mp-sticky-custom-cart' ),
			self::KEY_VARIATION_REQUIRED => __( 'Выберите вариацию товара', 'mp-sticky-custom-cart' ),
			self::KEY_SINGLE_ADD_SUCCESS => __( 'Товар добавлен в корзину', 'mp-sticky-custom-cart' ),
			self::KEY_DRAWER_EMPTY       => __( 'Корзина пуста', 'mp-sticky-custom-cart' ),
			self::KEY_DRAWER_EMPTY_HINT  => __( 'Добавьте товары из каталога', 'mp-sticky-custom-cart' ),
			self::KEY_DRAWER_REMOVE_LINE => __( 'Удалить позицию', 'mp-sticky-custom-cart' ),
			self::KEY_LINE_REMOVED       => __( 'Позиция удалена', 'mp-sticky-custom-cart' ),
			self::KEY_TRISTATE_METRIC_LINES => __( 'Позиций', 'mp-sticky-custom-cart' ),
			self::KEY_TRISTATE_METRIC_QTY   => __( 'Товаров', 'mp-sticky-custom-cart' ),
			self::KEY_TRISTATE_METRIC_TOTAL => __( 'Сумма', 'mp-sticky-custom-cart' ),
		);
	}

	/**
	 * Raw defaults without translation API (for option merge before i18n runs).
	 *
	 * @return array<string, string>
	 */
	public static function get_raw() {
		return array(
			self::KEY_CATALOG_CART_ICON  => 'Добавить в корзину',
			self::KEY_MORE_INFO          => 'Подробнее о товаре',
			self::KEY_OUT_OF_STOCK       => 'Товара нет в наличии',
			self::KEY_CLEAR_CART         => 'Очистить корзину',
			self::KEY_CLEAR_CART_IN_PROGRESS => 'Очистка корзины…',
			self::KEY_CART_CLEARED       => 'Корзина очищена',
			self::KEY_CHECKOUT            => 'Оформить заказ',
			self::KEY_VARIATION_REQUIRED => 'Выберите вариацию товара',
			self::KEY_SINGLE_ADD_SUCCESS => 'Товар добавлен в корзину',
			self::KEY_DRAWER_EMPTY       => 'Корзина пуста',
			self::KEY_DRAWER_EMPTY_HINT  => 'Добавьте товары из каталога',
			self::KEY_DRAWER_REMOVE_LINE => 'Удалить позицию',
			self::KEY_LINE_REMOVED       => 'Позиция удалена',
			self::KEY_TRISTATE_METRIC_LINES => 'Позиций',
			self::KEY_TRISTATE_METRIC_QTY   => 'Товаров',
			self::KEY_TRISTATE_METRIC_TOTAL => 'Сумма',
		);
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
