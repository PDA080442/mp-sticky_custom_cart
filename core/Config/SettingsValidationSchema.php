<?php
/**
 * Declarative validation rules for OPTION_SETTINGS and OPTION_FEATURE_FLAGS.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Config;

use MpStickyCustomCart\Core\CatalogCartIconPresets;

defined( 'ABSPATH' ) || exit;

/**
 * Each field: type + optional bounds; used by {@see \MpStickyCustomCart\Admin\SettingsSanitizer}.
 */
final class SettingsValidationSchema {

	/**
	 * Nested schema for {@see Constants::OPTION_SETTINGS}.
	 *
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	public static function get_settings_schema() {
		return array(
			'catalog'     => array(
				'image_click_behavior'        => array(
					'type'  => 'text',
					'oneof' => array( 'add_to_cart', 'theme_default' ),
				),
				'catalog_add_surface'         => array(
					'type'  => 'text',
					'oneof' => array( 'image_click', 'cart_icon' ),
				),
				'catalog_cart_icon_desktop'   => array(
					'type'  => 'text',
					'oneof' => array( 'hover', 'always' ),
				),
				'catalog_cart_icon_touch'     => array(
					'type'  => 'text',
					'oneof' => array( 'always', 'tap_reveal' ),
				),
				'catalog_cart_icon_offset_top_px'  => array(
					'type' => 'integer',
					'min'  => 0,
					'max'  => 64,
				),
				'catalog_cart_icon_offset_left_px' => array(
					'type' => 'integer',
					'min'  => 0,
					'max'  => 64,
				),
				'catalog_cart_icon_hit_size_px'    => array(
					'type' => 'integer',
					'min'  => 28,
					'max'  => 56,
				),
				'catalog_cart_icon_glyph_size_px'  => array(
					'type' => 'integer',
					'min'  => 14,
					'max'  => 28,
				),
				'catalog_cart_icon_stroke_width'   => array(
					'type' => 'float',
					'min'  => 1,
					'max'  => 3,
				),
				'catalog_cart_icon_transition_delay_ms' => array(
					'type' => 'integer',
					'min'  => 0,
					'max'  => 2000,
				),
				'catalog_cart_icon_mobile_mode'    => array(
					'type'  => 'text',
					'oneof' => array( 'inherit', 'force_visible' ),
				),
				'catalog_cart_icon_appearance_preset' => array(
					'type'  => 'text',
					'oneof' => array( 'black_cart_white_bg', 'white_cart_black_bg' ),
				),
				'catalog_cart_icon_color'          => array( 'type' => 'color' ),
				'catalog_cart_icon_bg_color'       => array( 'type' => 'color' ),
				'catalog_cart_icon_bg_alpha_percent' => array(
					'type' => 'integer',
					'min'  => 0,
					'max'  => 100,
				),
				'catalog_cart_icon_bg_border_radius_px' => array(
					'type' => 'integer',
					'min'  => 0,
					'max'  => 28,
				),
				'catalog_cart_icon_inner_padding_px' => array(
					'type' => 'integer',
					'min'  => 0,
					'max'  => 12,
				),
				'catalog_cart_icon_preset'         => array(
					'type'  => 'text',
					'oneof' => CatalogCartIconPresets::IDS,
				),
				'hover_overlay_mobile_always' => array(
					'type' => 'boolean',
				),
				'hover_animation_duration_ms' => array(
					'type' => 'integer',
					'min'  => 0,
					'max'  => 10000,
				),
				'hover_animation_easing'      => array(
					'type'        => 'text',
					'max_length'  => 120,
				),
				'hover_motion_preset'       => array(
					'type' => 'text',
					'oneof' => array( 'fade_slide', 'fade', 'slide' ),
				),
				'hover_slide_offset_px'     => array(
					'type' => 'integer',
					'min'  => 0,
					'max'  => 48,
				),
				'hover_hide_delay_ms'       => array(
					'type' => 'integer',
					'min'  => 0,
					'max'  => 500,
				),
				'image_click_selector'      => array(
					'type'        => 'text',
					'max_length'  => 500,
				),
				'card_root_selector'        => array(
					'type'        => 'text',
					'max_length'  => 200,
				),
				'wrap_loop_item_add_to_cart' => array(
					'type' => 'boolean',
				),
				'more_info_new_tab'         => array(
					'type' => 'boolean',
				),
				'catalog_overlay_z_index'   => array(
					'type' => 'integer',
					'min'  => 0,
					'max'  => 100,
				),
			),
			'cart_route'  => array(
				'redirect_to_home'     => array( 'type' => 'boolean' ),
				'redirect_status_code' => array(
					'type'  => 'integer',
					'oneof' => array( 301, 302, 303, 307 ),
				),
				'log_redirect_events'                   => array( 'type' => 'boolean' ),
				'preserve_marketing_params_on_redirect' => array( 'type' => 'boolean' ),
				'track_external_cart_link_hits'         => array( 'type' => 'boolean' ),
			),
			'sticky_cart' => array(
				'z_index'                   => array( 'type' => 'integer', 'min' => 1, 'max' => 9999999 ),
				'surface_backdrop_blur_px'  => array( 'type' => 'integer', 'min' => 0, 'max' => 100 ),
				'surface_background_alpha'  => array( 'type' => 'float', 'min' => 0, 'max' => 1 ),
				'border_radius_px'          => array( 'type' => 'integer', 'min' => 0, 'max' => 100 ),
				'padding_x_desktop_px'      => array( 'type' => 'integer', 'min' => 0, 'max' => 200 ),
				'padding_y_desktop_px'      => array( 'type' => 'integer', 'min' => 0, 'max' => 200 ),
				'padding_x_mobile_px'       => array( 'type' => 'integer', 'min' => 0, 'max' => 200 ),
				'padding_y_mobile_px'       => array( 'type' => 'integer', 'min' => 0, 'max' => 200 ),
				'drawer_max_height_vh'      => array( 'type' => 'integer', 'min' => 10, 'max' => 100 ),
				'drawer_padding_x_px'       => array( 'type' => 'integer', 'min' => 0, 'max' => 64 ),
				'drawer_padding_y_px'       => array( 'type' => 'integer', 'min' => 0, 'max' => 64 ),
				'drawer_surface_background_alpha' => array( 'type' => 'float', 'min' => 0, 'max' => 1 ),
				'drawer_line_title_font_size_px'   => array( 'type' => 'integer', 'min' => 8, 'max' => 48 ),
				'drawer_line_title_font_weight'    => array( 'type' => 'integer', 'min' => 100, 'max' => 900 ),
				'drawer_line_unit_font_size_px'    => array( 'type' => 'integer', 'min' => 8, 'max' => 40 ),
				'drawer_line_price_font_size_px'    => array( 'type' => 'integer', 'min' => 8, 'max' => 48 ),
				'drawer_line_price_font_weight'     => array( 'type' => 'integer', 'min' => 100, 'max' => 900 ),
				'drawer_toggle_duration_ms' => array( 'type' => 'integer', 'min' => 0, 'max' => 5000 ),
				'drawer_toggle_easing'      => array(
					'type'       => 'text',
					'max_length' => 120,
				),
				'quantity_debounce_ms'      => array( 'type' => 'integer', 'min' => 0, 'max' => 5000 ),
				'summary_font_size_px'      => array( 'type' => 'integer', 'min' => 8, 'max' => 48 ),
				'summary_font_weight'       => array( 'type' => 'integer', 'min' => 100, 'max' => 900 ),
				'summary_line_height'       => array( 'type' => 'float', 'min' => 1, 'max' => 2.5 ),
				'summary_gap_px'            => array( 'type' => 'integer', 'min' => 0, 'max' => 48 ),
				'summary_text_gap_row_px'   => array( 'type' => 'integer', 'min' => 0, 'max' => 48 ),
				'summary_text_gap_column_px' => array( 'type' => 'integer', 'min' => 0, 'max' => 64 ),
				'button_font_size_px'       => array( 'type' => 'integer', 'min' => 8, 'max' => 48 ),
				'button_font_weight'        => array( 'type' => 'integer', 'min' => 100, 'max' => 900 ),
				'button_line_height'        => array( 'type' => 'float', 'min' => 1, 'max' => 2.5 ),
				'actions_gap_px'            => array( 'type' => 'integer', 'min' => 0, 'max' => 48 ),
				'clear_button_min_width_px' => array( 'type' => 'integer', 'min' => 0, 'max' => 400 ),
				'checkout_button_min_width_px' => array( 'type' => 'integer', 'min' => 0, 'max' => 400 ),
				'sticky_inner_gap_mobile_px' => array( 'type' => 'integer', 'min' => 0, 'max' => 48 ),
				'sticky_inner_gap_desktop_row_px' => array( 'type' => 'integer', 'min' => 0, 'max' => 64 ),
				'sticky_inner_gap_desktop_col_px' => array( 'type' => 'integer', 'min' => 0, 'max' => 96 ),
				'tristate_panel_b_max_height_px'     => array( 'type' => 'integer', 'min' => 120, 'max' => 480 ),
				'tristate_panel_b_width_px'          => array( 'type' => 'integer', 'min' => 200, 'max' => 480 ),
				'tristate_panel_b_gap_bottom_px'     => array( 'type' => 'integer', 'min' => 4, 'max' => 40 ),
				'tristate_panel_b_padding_px'        => array( 'type' => 'integer', 'min' => 8, 'max' => 32 ),
				'tristate_panel_b_border_radius_px'  => array( 'type' => 'integer', 'min' => 0, 'max' => 28 ),
				'tristate_panel_b_actions_gap_px'    => array( 'type' => 'integer', 'min' => 4, 'max' => 24 ),
			),
			'wishlist_ui' => array(
				'heart_reserve_top_px'       => array( 'type' => 'integer', 'min' => 0, 'max' => 200 ),
				'heart_reserve_right_px'     => array( 'type' => 'integer', 'min' => 0, 'max' => 200 ),
				'overlay_clearance_heart_px'   => array( 'type' => 'integer', 'min' => 0, 'max' => 200 ),
				'heart_icon_z_index'           => array( 'type' => 'integer', 'min' => 0, 'max' => 100 ),
			),
			'styles'      => array(
				'color_text_primary'        => array( 'type' => 'color' ),
				'color_surface_tint'        => array( 'type' => 'color' ),
				'color_drawer_surface_tint' => array( 'type' => 'color' ),
				'color_clear_button_text'   => array( 'type' => 'color' ),
				'color_clear_button_text_hover' => array( 'type' => 'color' ),
				'color_clear_button_border' => array( 'type' => 'color' ),
				'color_clear_button_border_hover' => array( 'type' => 'color' ),
				'color_clear_button_bg'     => array( 'type' => 'color' ),
				'color_clear_button_bg_hover' => array( 'type' => 'color' ),
				'color_qty_button_bg'       => array( 'type' => 'color' ),
				'color_qty_button_bg_hover' => array( 'type' => 'color' ),
				'color_qty_button_border'   => array( 'type' => 'color' ),
				'color_qty_button_border_hover' => array( 'type' => 'color' ),
				'color_qty_button_text'     => array( 'type' => 'color' ),
				'color_qty_button_text_hover' => array( 'type' => 'color' ),
				'color_drawer_line_title'   => array( 'type' => 'color' ),
				'color_drawer_line_unit'    => array( 'type' => 'color' ),
				'color_drawer_line_price'   => array( 'type' => 'color' ),
				'color_button_primary'      => array( 'type' => 'color' ),
				'color_button_primary_text' => array( 'type' => 'color' ),
				'color_button_primary_hover' => array( 'type' => 'color' ),
				'color_button_primary_text_hover' => array( 'type' => 'color' ),
				'font_family_preset'        => array(
					'type'  => 'text',
					'oneof' => array( 'inherit', 'system', 'serif', 'mono', 'custom' ),
				),
				'font_family_custom'        => array( 'type' => 'text', 'max_length' => 500 ),
				'typography_scale_percent'  => array( 'type' => 'integer', 'min' => 70, 'max' => 130 ),
			),
			'diagnostics' => array(
				'client_error_logging' => array( 'type' => 'boolean' ),
				'log_retention_days'   => array( 'type' => 'integer', 'min' => 1, 'max' => 365 ),
				'log_max_entries'      => array( 'type' => 'integer', 'min' => 10, 'max' => 2000 ),
				'log_max_bytes'        => array( 'type' => 'integer', 'min' => 4096, 'max' => 1048576 ),
			),
			'notices'     => array(
				'remove_view_cart_link' => array( 'type' => 'boolean' ),
			),
			'labels'      => array(
				'catalog_cart_icon'  => array( 'type' => 'text', 'max_length' => 500 ),
				'more_info'          => array( 'type' => 'text', 'max_length' => 500 ),
				'out_of_stock'       => array( 'type' => 'text', 'max_length' => 500 ),
				'clear_cart'         => array( 'type' => 'text', 'max_length' => 500 ),
				'cart_cleared'       => array( 'type' => 'text', 'max_length' => 500 ),
				'checkout'           => array( 'type' => 'text', 'max_length' => 500 ),
				'variation_required' => array( 'type' => 'text', 'max_length' => 500 ),
				'single_add_success' => array( 'type' => 'text', 'max_length' => 500 ),
				'drawer_empty'       => array( 'type' => 'text', 'max_length' => 500 ),
				'drawer_empty_hint'  => array( 'type' => 'text', 'max_length' => 500 ),
				'drawer_remove_line' => array( 'type' => 'text', 'max_length' => 500 ),
				'line_removed'       => array( 'type' => 'text', 'max_length' => 500 ),
			),
		);
	}

	/**
	 * Feature flag keys and types (all boolean).
	 *
	 * @return array<string, array{type:string}>
	 */
	public static function get_flags_schema() {
		return array(
			FeatureFlagsDefaults::KEY_PRODUCT_IMAGE_ADD_TO_CART         => array( 'type' => 'boolean' ),
			FeatureFlagsDefaults::KEY_STICKY_CART_ENABLED               => array( 'type' => 'boolean' ),
			FeatureFlagsDefaults::KEY_STICKY_DRAWER_ENABLED             => array( 'type' => 'boolean' ),
			FeatureFlagsDefaults::KEY_STICKY_TRISTATE_ENABLED           => array( 'type' => 'boolean' ),
			FeatureFlagsDefaults::KEY_HOVER_MORE_INFO_ENABLED           => array( 'type' => 'boolean' ),
			FeatureFlagsDefaults::KEY_WISHLIST_ICON_INTEGRATION_ENABLED => array( 'type' => 'boolean' ),
		);
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
