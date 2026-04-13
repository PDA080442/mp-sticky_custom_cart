<?php
/**
 * Declarative validation rules for OPTION_SETTINGS and OPTION_FEATURE_FLAGS.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Config;

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
				'color_button_primary'      => array( 'type' => 'color' ),
				'color_button_primary_text' => array( 'type' => 'color' ),
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
