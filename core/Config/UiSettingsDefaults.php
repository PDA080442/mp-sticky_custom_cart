<?php
/**
 * Default values for UI-related plugin settings (admin-editable later).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Shape mirrors persisted option structure under {@see \MpStickyCustomCart\Core\Constants::OPTION_SETTINGS}.
 */
final class UiSettingsDefaults {

	/**
	 * @return array<string, mixed> Nested defaults for catalog, sticky cart, wishlist integration, motion.
	 */
	public static function get() {
		return array(
			'catalog'    => array(
				'hover_overlay_mobile_always' => true,
				'hover_animation_duration_ms'  => 220,
				'hover_animation_easing'       => 'cubic-bezier(0.4, 0, 0.2, 1)',
				'hover_motion_preset'          => 'fade_slide',
				/** Vertical slide distance (px) for overlay enter/leave motion. */
				'hover_slide_offset_px'        => 8,
				/** Extra delay before fade-out when pointer leaves the card (reduces flicker). */
				'hover_hide_delay_ms'          => 50,
				/** CSS selector for delegated image clicks (shop loop); empty = built-in default. */
				'image_click_selector'         => '',
				/** Closest ancestor for loading/added/error states (jQuery selector). */
				'card_root_selector'           => 'li.product',
				/** Open «Подробнее» product URL in a new browser tab (adds target + rel). */
				'more_info_new_tab'            => false,
				/** Stacking: «Подробнее» overlay (keep below wishlist heart). */
				'catalog_overlay_z_index'      => 4,
			),
			'cart_route'  => array(
				/** When true, requests to the WooCommerce cart page redirect to the site front (sticky-only UX). */
				'redirect_to_home'     => false,
				/** HTTP status for the redirect (301 permanent, 302/303/307 temporary). */
				'redirect_status_code' => 302,
				/** Write redirect lines to the PHP debug log (wp-content/debug.log when WP_DEBUG_LOG). */
				'log_redirect_events'  => false,
			),
			'sticky_cart' => array(
				'z_index'                    => 100050,
				'surface_backdrop_blur_px'   => 14,
				'surface_background_alpha'   => 0.78,
				'border_radius_px'           => 14,
				'padding_x_desktop_px'       => 20,
				'padding_y_desktop_px'       => 14,
				'padding_x_mobile_px'        => 14,
				'padding_y_mobile_px'        => 12,
				'drawer_max_height_vh'       => 55,
				'drawer_toggle_duration_ms'  => 260,
				'drawer_toggle_easing'       => 'cubic-bezier(0.4, 0, 0.2, 1)',
				'quantity_debounce_ms'       => 320,
				'summary_font_size_px'       => 15,
				'summary_font_weight'        => 600,
				'button_font_size_px'        => 14,
				'button_font_weight'         => 600,
			),
			'wishlist_ui' => array(
				'heart_reserve_top_px'       => 10,
				'heart_reserve_right_px'     => 10,
				'overlay_clearance_heart_px' => 8,
				/** Stacking: wishlist control above catalog overlay. */
				'heart_icon_z_index'         => 6,
			),
			'styles'      => array(
				'color_text_primary'        => '#1a1a1a',
				'color_surface_tint'        => '#ffffff',
				'color_button_primary'      => '#111111',
				'color_button_primary_text' => '#ffffff',
			),
			'diagnostics' => array(
				'client_error_logging' => true,
				'log_retention_days'   => 14,
			),
		);
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
