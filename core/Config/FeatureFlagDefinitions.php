<?php
/**
 * Human-readable labels and admin hints for each feature flag.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Mirrors {@see FeatureFlagsDefaults} keys with UI copy for Settings screen.
 */
final class FeatureFlagDefinitions {

	/**
	 * Short labels (table header column).
	 *
	 * @return array<string, string>
	 */
	public static function labels() {
		return array(
			FeatureFlagsDefaults::KEY_PRODUCT_IMAGE_ADD_TO_CART         => __( 'Клик по изображению → в корзину', 'mp-sticky-custom-cart' ),
			FeatureFlagsDefaults::KEY_STICKY_CART_ENABLED               => __( 'Sticky-панель', 'mp-sticky-custom-cart' ),
			FeatureFlagsDefaults::KEY_STICKY_DRAWER_ENABLED             => __( 'Drawer корзины', 'mp-sticky-custom-cart' ),
			FeatureFlagsDefaults::KEY_STICKY_TRISTATE_ENABLED           => __( 'Корзина: режим «иконка» (три состояния)', 'mp-sticky-custom-cart' ),
			FeatureFlagsDefaults::KEY_HOVER_MORE_INFO_ENABLED           => __( 'Кнопка «Подробнее» (hover)', 'mp-sticky-custom-cart' ),
			FeatureFlagsDefaults::KEY_WISHLIST_ICON_INTEGRATION_ENABLED => __( 'Интеграция сердечка', 'mp-sticky-custom-cart' ),
		);
	}

	/**
	 * Help text under each checkbox (feature flag).
	 *
	 * @return array<string, string>
	 */
	public static function descriptions() {
		return array(
			FeatureFlagsDefaults::KEY_PRODUCT_IMAGE_ADD_TO_CART => __( 'В каталоге добавление в корзину по клику на изображение товара (AJAX).', 'mp-sticky-custom-cart' ),
			FeatureFlagsDefaults::KEY_STICKY_CART_ENABLED => __( 'Нижняя панель корзины на всех страницах сайта.', 'mp-sticky-custom-cart' ),
			FeatureFlagsDefaults::KEY_STICKY_DRAWER_ENABLED => __( 'Раскрывающийся список позиций и управление количеством.', 'mp-sticky-custom-cart' ),
			FeatureFlagsDefaults::KEY_STICKY_TRISTATE_ENABLED => __( 'Нижняя полоса с суммой скрыта: плавающая кнопка справа снизу (состояние A), панели B/C — далее по ТЗ.', 'mp-sticky-custom-cart' ),
			FeatureFlagsDefaults::KEY_HOVER_MORE_INFO_ENABLED => __( 'Оверлей «Подробнее о товаре» на карточке каталога.', 'mp-sticky-custom-cart' ),
			FeatureFlagsDefaults::KEY_WISHLIST_ICON_INTEGRATION_ENABLED => __( 'Совместимость слоёв с существующей иконкой избранного в карточке.', 'mp-sticky-custom-cart' ),
		);
	}

	/**
	 * Ordered list of flag keys (stable order in admin UI).
	 *
	 * @return array<int, string>
	 */
	public static function ordered_keys() {
		return array(
			FeatureFlagsDefaults::KEY_PRODUCT_IMAGE_ADD_TO_CART,
			FeatureFlagsDefaults::KEY_STICKY_CART_ENABLED,
			FeatureFlagsDefaults::KEY_STICKY_DRAWER_ENABLED,
			FeatureFlagsDefaults::KEY_STICKY_TRISTATE_ENABLED,
			FeatureFlagsDefaults::KEY_HOVER_MORE_INFO_ENABLED,
			FeatureFlagsDefaults::KEY_WISHLIST_ICON_INTEGRATION_ENABLED,
		);
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
