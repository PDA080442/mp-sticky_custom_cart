<?php
/**
 * Default feature flags (toggleable from admin later).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * All keys must stay stable for migrations and JS localization.
 *
 * Registry (dp.md / feature rollout):
 * - ProductImageAddToCart — AJAX-добавление из лупы (миниатюра или иконка корзины; см. catalog.catalog_add_surface)
 * - StickyCartEnabled — sticky-панель
 * - StickyDrawerEnabled — drawer корзины
 * - StickyTristateEnabled — плавающая иконка (фаза 17) вместо полосы summary
 * - StickyHideWhenEmptyEnabled — не выводить плавающий shell при пустой корзине до первого add (по умолчанию вкл.; фаза 18)
 * - HoverMoreInfoEnabled — hover «подробнее»
 * - WishlistIconIntegrationEnabled — интеграция heart-иконки
 */
final class FeatureFlagsDefaults {

	public const KEY_PRODUCT_IMAGE_ADD_TO_CART       = 'product_image_add_to_cart';
	public const KEY_STICKY_CART_ENABLED             = 'sticky_cart_enabled';
	public const KEY_STICKY_DRAWER_ENABLED           = 'sticky_drawer_enabled';
	public const KEY_STICKY_TRISTATE_ENABLED         = 'sticky_tristate_enabled';
	public const KEY_STICKY_HIDE_WHEN_EMPTY_ENABLED = 'sticky_hide_when_empty_enabled';
	public const KEY_HOVER_MORE_INFO_ENABLED         = 'hover_more_info_enabled';
	public const KEY_WISHLIST_ICON_INTEGRATION_ENABLED = 'wishlist_icon_integration_enabled';

	/**
	 * @return array<string, bool>
	 */
	public static function get() {
		return array(
			self::KEY_PRODUCT_IMAGE_ADD_TO_CART         => true,
			self::KEY_STICKY_CART_ENABLED               => true,
			self::KEY_STICKY_DRAWER_ENABLED             => true,
			self::KEY_STICKY_TRISTATE_ENABLED           => false,
			self::KEY_STICKY_HIDE_WHEN_EMPTY_ENABLED   => true,
			self::KEY_HOVER_MORE_INFO_ENABLED           => true,
			self::KEY_WISHLIST_ICON_INTEGRATION_ENABLED => true,
		);
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
