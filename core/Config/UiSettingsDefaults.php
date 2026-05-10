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
	 * Sticky cart keys that belong to appearance (Styles tab). Not reset alone when resetting Cart tab behavior — Cart reset still replaces full sticky_cart.
	 *
	 * @return list<string>
	 */
	public static function get_sticky_cart_visual_keys() {
		return array(
			'surface_backdrop_blur_px',
			'surface_background_alpha',
			'border_radius_px',
			'padding_x_desktop_px',
			'padding_y_desktop_px',
			'padding_x_mobile_px',
			'padding_y_mobile_px',
			'sticky_inner_gap_mobile_px',
			'sticky_inner_gap_desktop_row_px',
			'sticky_inner_gap_desktop_col_px',
			'summary_font_size_px',
			'summary_font_weight',
			'summary_line_height',
			'summary_gap_px',
			'summary_text_gap_row_px',
			'summary_text_gap_column_px',
			'button_font_size_px',
			'button_font_weight',
			'button_line_height',
			'actions_gap_px',
			'clear_button_min_width_px',
			'checkout_button_min_width_px',
			'drawer_max_height_vh',
			'drawer_padding_x_px',
			'drawer_padding_y_px',
			'drawer_surface_background_alpha',
			'drawer_line_title_font_size_px',
			'drawer_line_title_font_weight',
			'drawer_line_unit_font_size_px',
			'drawer_line_price_font_size_px',
			'drawer_line_price_font_weight',
			'tristate_panel_b_max_height_px',
			'tristate_panel_b_max_height_mobile_px',
			'tristate_panel_b_max_height_tablet_px',
			'tristate_panel_b_width_px',
			'tristate_panel_b_gap_bottom_px',
			'tristate_panel_b_padding_px',
			'tristate_panel_b_border_radius_px',
			'tristate_panel_b_actions_gap_px',
			'tristate_metric_lines_visible',
			'tristate_metrics_row_padding_y_px',
			'tristate_metrics_hr_margin_y_panel_b_px',
			'tristate_metrics_hr_margin_y_drawer_c_px',
			'tristate_metrics_hr_border_width_px',
			'tristate_metrics_hr_style',
			'tristate_metrics_hr_color',
			'tristate_metrics_hr_opacity_percent',
			'tristate_actions_toolbar_padding_top_px',
			'tristate_actions_toolbar_padding_right_px',
			'tristate_actions_toolbar_padding_bottom_px',
			'tristate_actions_toolbar_padding_left_px',
			'tristate_dismiss_hit_px',
			'tristate_dismiss_margin_top_px',
			'tristate_dismiss_margin_right_px',
			'tristate_dismiss_margin_bottom_px',
			'tristate_dismiss_margin_left_px',
			'tristate_dismiss_padding_top_px',
			'tristate_dismiss_padding_right_px',
			'tristate_dismiss_padding_bottom_px',
			'tristate_dismiss_padding_left_px',
			'tristate_dismiss_border_width_px',
			'tristate_dismiss_border_radius_px',
			'tristate_dismiss_color',
			'tristate_dismiss_bg_color',
			'tristate_dismiss_border_color',
			'tristate_dismiss_hover_color',
			'tristate_dismiss_hover_bg_color',
			'tristate_dismiss_hover_border_color',
			'tristate_dismiss_glyph_px',
			'tristate_action_icon_hit_px',
			'tristate_action_icon_glyph_px',
			'tristate_panel_c_width_px',
			'tristate_panel_c_height_px',
			'tristate_panel_c_height_mobile_px',
			'tristate_panel_c_height_tablet_px',
			'tristate_state_a_dock_inset_top_px',
			'tristate_state_a_dock_inset_right_px',
			'tristate_state_a_dock_inset_bottom_px',
			'tristate_state_a_dock_inset_left_px',
			'tristate_dock_inset_top_px',
			'tristate_dock_inset_right_px',
			'tristate_dock_inset_bottom_px',
			'tristate_dock_inset_left_px',
			'tristate_column_backdrop_blur_px',
			'tristate_column_shadow_blur_px',
			'tristate_column_shadow_offset_y_px',
			'tristate_column_shadow_opacity_percent',
			'tristate_mobile_breakpoint_max_px',
			'tristate_tablet_breakpoint_max_px',
			'tristate_mobile_layout_preset',
			'tristate_fab_badge_size_px',
			'tristate_fab_badge_offset_top_px',
			'tristate_fab_badge_offset_right_px',
			'tristate_fab_badge_font_size_px',
			'tristate_fab_badge_bg_color',
			'tristate_fab_badge_text_color',
			'tristate_custom_css_global',
			'tristate_custom_css_state_a',
			'tristate_custom_css_state_b',
			'tristate_custom_css_state_c',
		);
	}

	/**
	 * @return array<string, mixed> Nested defaults for catalog, sticky cart, wishlist integration, motion.
	 */
	public static function get() {
		return array(
			'catalog'    => array(
				/**
				 * add_to_cart: перехват клика по миниатюре и AJAX (при включённом feature flag).
				 * theme_default: не вешать обработчик — тема и ссылки Woo ведут себя как обычно.
				 */
				'image_click_behavior'        => 'add_to_cart',
				/**
				 * image_click: добавление по клику на миниатюру (legacy).
				 * cart_icon: кнопка-иконка корзины на карточке (тот же AJAX endpoint).
				 */
				'catalog_add_surface'         => 'image_click',
				/** Desktop: hover = show icon on card hover; always = always visible. */
				'catalog_cart_icon_desktop'   => 'hover',
				/** Touch: always = visible; tap_reveal = show after first tap on card (non-link). */
				'catalog_cart_icon_touch'     => 'always',
				/** Offset of the icon slot from the top-left of the first loop image (px). */
				'catalog_cart_icon_offset_top_px'  => 8,
				'catalog_cart_icon_offset_left_px'   => 8,
				/** Square hit target (button outer size, px). */
				'catalog_cart_icon_hit_size_px'      => 36,
				/** SVG glyph size inside the button (px). */
				'catalog_cart_icon_glyph_size_px'    => 20,
				/**
				 * Touch / narrow viewport (max-width 768px or pointer: coarse): button box size (bg + glyph together, px).
				 * 0 = same as desktop hit-size above. If only one side is set, the button stays square.
				 * Slot AND inner SVG glyph scale to the same value (so the icon visually fills the new bg).
				 */
				'catalog_cart_icon_box_width_mobile_px'   => 0,
				'catalog_cart_icon_box_height_mobile_px'  => 0,
				/** Built-in SVG cart path stroke width (1–3, default matches previous hard-coded look). */
				'catalog_cart_icon_stroke_width'     => 1.75,
				/** Delay before opacity/visibility transition starts (ms). */
				'catalog_cart_icon_transition_delay_ms' => 0,
				/**
				 * inherit: follow «тач / узкий экран» above.
				 * force_visible: on touch/narrow viewport always show the icon (overrides tap_reveal).
				 */
				'catalog_cart_icon_mobile_mode'      => 'inherit',
				/**
				 * Two curated looks: dark glyph on light button, or light glyph on dark button.
				 * Legacy per-channel color keys remain for import; storefront CSS uses the preset.
				 */
				'catalog_cart_icon_appearance_preset' => 'black_cart_white_bg',
				/** Fill/stroke color for the loop cart icon (SVG uses currentColor). */
				'catalog_cart_icon_color'            => '#1a1a1a',
				/** Button face behind the glyph (combined with alpha into rgba() on the storefront). */
				'catalog_cart_icon_bg_color'         => '#ffffff',
				/** 0–100, opacity of the background (100 = solid). */
				'catalog_cart_icon_bg_alpha_percent' => 94,
				/** Border radius of the cart icon button face (px); see --mp-scc-catalog-cart-icon-border-radius. */
				'catalog_cart_icon_bg_border_radius_px' => 10,
				/** Symmetric padding between button edge and glyph (px); see --mp-scc-catalog-cart-icon-inner-padding. */
				'catalog_cart_icon_inner_padding_px' => 0,
				/** Preset id from {@see \MpStickyCustomCart\Core\CatalogCartIconPresets::IDS}. */
				'catalog_cart_icon_preset'           => 'classic',
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
				/**
				 * Standard Woo loop only: replace the default thumbnail link with one wrapper
				 * ({@see \MpStickyCustomCart\Frontend\ShopLoopAddToCartWrapper}). Ignored by Elementor/Liquid.
				 */
				'wrap_loop_item_add_to_cart'   => false,
				/** Open «Подробнее» product URL in a new browser tab (adds target + rel). */
				'more_info_new_tab'            => false,
				/** Show the visible «Подробнее» caption on the catalog overlay strip (link/aria still work when off). */
				'catalog_more_info_label_visible' => true,
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
				/** Append UTM / click ids from the current cart URL onto the redirect target (not add-to-cart params). */
				'preserve_marketing_params_on_redirect' => true,
				/** Increment {@see Constants::OPTION_EXTERNAL_CART_LINK_HITS} when cart URL contained add-to-cart. */
				'track_external_cart_link_hits'         => false,
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
				'drawer_padding_x_px'        => 16,
				'drawer_padding_y_px'        => 12,
				/** Drawer panel surface (independent from bottom bar when customized). */
				'drawer_surface_background_alpha' => 0.94,
				'drawer_line_title_font_size_px'   => 15,
				'drawer_line_title_font_weight'    => 500,
				'drawer_line_unit_font_size_px'    => 13,
				'drawer_line_price_font_size_px'   => 15,
				'drawer_line_price_font_weight'    => 600,
				'drawer_toggle_duration_ms'  => 260,
				'drawer_toggle_easing'       => 'cubic-bezier(0.4, 0, 0.2, 1)',
				'quantity_debounce_ms'       => 320,
				'summary_font_size_px'       => 15,
				'summary_font_weight'        => 600,
				/** Unitless line-height for summary (count + total). */
				'summary_line_height'        => 1.35,
				'summary_gap_px'             => 10,
				'summary_text_gap_row_px'    => 12,
				'summary_text_gap_column_px' => 20,
				'button_font_size_px'        => 14,
				'button_font_weight'         => 600,
				/** Unitless line-height for sticky action buttons. */
				'button_line_height'         => 1.2,
				'actions_gap_px'             => 10,
				'clear_button_min_width_px'  => 0,
				'checkout_button_min_width_px' => 0,
				'sticky_inner_gap_mobile_px' => 10,
				'sticky_inner_gap_desktop_row_px' => 16,
				'sticky_inner_gap_desktop_col_px' => 24,
				/** Max height of tri-state summary panel B (px); typical content should fit without inner scroll. */
				'tristate_panel_b_max_height_px'     => 368,
				/**
				 * Mobile override for panel B max-height (px); 0 = inherit desktop.
				 * Applies when viewport width ≤ `tristate_mobile_breakpoint_max_px`.
				 */
				'tristate_panel_b_max_height_mobile_px' => 0,
				/**
				 * Tablet override for panel B max-height (px); 0 = inherit desktop.
				 * Applies between `tristate_mobile_breakpoint_max_px + 1` and `tristate_tablet_breakpoint_max_px`.
				 */
				'tristate_panel_b_max_height_tablet_px' => 0,
				/** Width of panel B (px). */
				'tristate_panel_b_width_px'          => 280,
				/** Gap between FAB and bottom edge of panel B (px). */
				'tristate_panel_b_gap_bottom_px'     => 10,
				/** Inner padding of panel B (px). */
				'tristate_panel_b_padding_px'        => 12,
				/** Corner radius of panel B (px). */
				'tristate_panel_b_border_radius_px'  => 12,
				/** Gap between icon action buttons in panel B (px). */
				'tristate_panel_b_actions_gap_px'    => 8,
				/**
				 * When false, hide the «Позиций» (cart line count) row in tri-state panel B and drawer C header.
				 * FAB badge and AJAX still use line count; only the summary rows in B/C are omitted.
				 */
				'tristate_metric_lines_visible'      => true,
				/** Panel B + drawer C metrics rows: vertical padding (px). */
				'tristate_metrics_row_padding_y_px'           => 10,
				'tristate_metrics_hr_margin_y_panel_b_px'     => 0,
				'tristate_metrics_hr_margin_y_drawer_c_px'    => 8,
				'tristate_metrics_hr_border_width_px'         => 1,
				'tristate_metrics_hr_style'                   => 'dashed',
				'tristate_metrics_hr_color'                   => '#000000',
				'tristate_metrics_hr_opacity_percent'         => 14,
				/** Padding around the icon toolbar (panel B body + drawer C), px per side → --mp-scc-tristate-actions-toolbar-padding-*. */
				'tristate_actions_toolbar_padding_top_px'    => 24,
				'tristate_actions_toolbar_padding_right_px'  => 0,
				'tristate_actions_toolbar_padding_bottom_px' => 0,
				'tristate_actions_toolbar_padding_left_px'   => 0,
				/** Dismiss (×) for panel B + drawer C: outer hit (px). */
				'tristate_dismiss_hit_px'                    => 36,
				'tristate_dismiss_margin_top_px'             => 0,
				'tristate_dismiss_margin_right_px'           => 0,
				'tristate_dismiss_margin_bottom_px'          => 0,
				'tristate_dismiss_margin_left_px'            => 0,
				'tristate_dismiss_padding_top_px'            => 2,
				'tristate_dismiss_padding_right_px'          => 2,
				'tristate_dismiss_padding_bottom_px'         => 2,
				'tristate_dismiss_padding_left_px'           => 2,
				'tristate_dismiss_border_width_px'           => 1,
				'tristate_dismiss_border_radius_px'          => 8,
				'tristate_dismiss_color'                     => '#1a1a1a',
				'tristate_dismiss_bg_color'                  => '#ffffff',
				'tristate_dismiss_border_color'              => '#d0d0d0',
				'tristate_dismiss_hover_color'               => '#1a1a1a',
				'tristate_dismiss_hover_bg_color'            => '#f0f0f0',
				'tristate_dismiss_hover_border_color'        => '#b0b0b0',
				'tristate_dismiss_glyph_px'                  => 16,
				/** Icon-only clear/checkout control outer size (px), tri-state panel B + drawer C. */
				'tristate_action_icon_hit_px'        => 44,
				/** Glyph (SVG) size inside {@see tristate_action_icon_hit_px} (px). */
				'tristate_action_icon_glyph_px'      => 22,
				/** Drawer C width (px), expands left from right edge; height matches panel B max-height. */
				'tristate_panel_c_width_px'          => 600,
				/** Drawer C fixed height (px). */
				'tristate_panel_c_height_px'         => 368,
				/**
				 * Mobile override for drawer C fixed height (px); 0 = inherit desktop.
				 * Applies when viewport width ≤ `tristate_mobile_breakpoint_max_px`.
				 */
				'tristate_panel_c_height_mobile_px'  => 0,
				/**
				 * Tablet override for drawer C fixed height (px); 0 = inherit desktop.
				 * Applies between `tristate_mobile_breakpoint_max_px + 1` and `tristate_tablet_breakpoint_max_px`.
				 */
				'tristate_panel_c_height_tablet_px'  => 0,
				/** State A (FAB only): top inset from viewport (px). */
				'tristate_state_a_dock_inset_top_px'    => 0,
				/** State A: right inset (px). */
				'tristate_state_a_dock_inset_right_px'  => 32,
				/** State A: bottom inset above safe-area (px). */
				'tristate_state_a_dock_inset_bottom_px' => 32,
				/** State A: left inset (px). */
				'tristate_state_a_dock_inset_left_px'   => 32,
				/** Top inset of tri-state dock (FAB + B/C) from viewport (px). */
				'tristate_dock_inset_top_px'         => 0,
				/** Right inset of tri-state dock from viewport (px). */
				'tristate_dock_inset_right_px'       => 32,
				/** Bottom inset of tri-state dock above safe-area (px). */
				'tristate_dock_inset_bottom_px'      => 32,
				/** Left inset of tri-state dock from viewport (px). */
				'tristate_dock_inset_left_px'        => 32,
				/** Backdrop blur for floating panels B/C (px); 0 disables blur on those surfaces. */
				'tristate_column_backdrop_blur_px'   => 14,
				/** Box-shadow blur for B/C elevation (px). */
				'tristate_column_shadow_blur_px'     => 28,
				/** Shadow offset upward (px); rendered as negative Y. */
				'tristate_column_shadow_offset_y_px' => 8,
				/** Shadow opacity 4–28 → 0.04–0.28 (rgba alpha). */
				'tristate_column_shadow_opacity_percent' => 12,
				/** Viewport max-width (px) for mobile preset rules (admin + injected @media). */
				'tristate_mobile_breakpoint_max_px'  => 782,
				/**
				 * Viewport max-width (px) for the tablet adaptive range (mobile_bp, tablet_bp].
				 * Used by the panel B/C adaptive height overrides below.
				 */
				'tristate_tablet_breakpoint_max_px'  => 1024,
				/** Cart count badge on FAB (state A): circle size in px. */
				'tristate_fab_badge_size_px'         => 20,
				/** FAB badge offset from top edge (negative lifts outside button). */
				'tristate_fab_badge_offset_top_px'   => -8,
				/** FAB badge offset from right edge (negative shifts outside button). */
				'tristate_fab_badge_offset_right_px' => -8,
				/** FAB badge font size (px). */
				'tristate_fab_badge_font_size_px'    => 11,
				/** FAB badge background color. */
				'tristate_fab_badge_bg_color'        => '#e53935',
				/** FAB badge text color. */
				'tristate_fab_badge_text_color'      => '#ffffff',
				/** Advanced override CSS for all tri-state nodes (printed as-is). */
				'tristate_custom_css_global'         => '',
				/** Advanced override CSS for state A wrapper only (printed as-is). */
				'tristate_custom_css_state_a'        => '',
				/** Advanced override CSS for state B wrapper only (printed as-is). */
				'tristate_custom_css_state_b'        => '',
				/** Advanced override CSS for state C wrapper only (printed as-is). */
				'tristate_custom_css_state_c'        => '',
				/**
				 * Show sticky cart shell on all frontend templates when cart has items.
				 * When false, sticky is limited to Woo templates/endpoints only.
				 */
				'visibility_show_on_all_templates' => true,
				/**
				 * Optional URL/path exclusions (one per line, supports '*' wildcard).
				 * Examples: /checkout/*, /cart/*, https://example.com/some-page
				 */
				'visibility_excluded_urls'         => '',
				/**
				 * right_docked: narrow view keeps column at right (default).
				 * full_bottom: below breakpoint, B/C span between horizontal insets (bottom-sheet style).
				 */
				'tristate_mobile_layout_preset'      => 'right_docked',
			),
			'wishlist_ui' => array(
				'heart_reserve_top_px'       => 10,
				'heart_reserve_right_px'     => 10,
				'overlay_clearance_heart_px' => 8,
				/** Stacking: wishlist control above catalog overlay. */
				'heart_icon_z_index'         => 6,
				/** YITH heart circle on catalog: idle (not in wishlist). */
				'heart_idle_bg_color'        => '#ffffff',
				'heart_idle_icon_color'      => '#000000',
				/** After product is in wishlist (matches typical filled look). */
				'heart_in_wishlist_bg_color'   => '#111111',
				'heart_in_wishlist_icon_color' => '#ffffff',
				/**
				 * Hover/focus поверх idle — типичный паттерн «превью in-state» (чёрный круг, белая иконка).
				 * Меняйте, если хотите, чтобы при наведении сердечко становилось другим цветом.
				 */
				'heart_idle_hover_bg_color'        => '#111111',
				'heart_idle_hover_icon_color'      => '#ffffff',
				/**
				 * Hover/focus для уже добавленного товара. По умолчанию слегка осветлён — намёк, что
				 * клик активный (удалит из избранного). Сделайте красным (#e53935), если хотите чёткий
				 * сигнал «удалить».
				 */
				'heart_in_wishlist_hover_bg_color'   => '#333333',
				'heart_in_wishlist_hover_icon_color' => '#ffffff',
			),
			'styles'      => array(
				'color_text_primary'        => '#1a1a1a',
				'color_surface_tint'        => '#ffffff',
				'color_drawer_surface_tint' => '#ffffff',
				'color_clear_button_text'   => '#1a1a1a',
				'color_clear_button_text_hover' => '#1a1a1a',
				'color_clear_button_border' => '#cfcfcf',
				'color_clear_button_border_hover' => '#b0b0b0',
				'color_clear_button_bg'     => '#ffffff',
				'color_clear_button_bg_hover' => '#f0f0f0',
				'color_qty_button_bg'       => '#ffffff',
				'color_qty_button_bg_hover' => '#ececec',
				'color_qty_button_border'   => '#cccccc',
				'color_qty_button_border_hover' => '#9a9a9a',
				'color_qty_button_text'     => '#1a1a1a',
				'color_qty_button_text_hover' => '#1a1a1a',
				'color_drawer_line_title'   => '#1a1a1a',
				'color_drawer_line_unit'    => '#5c5c5c',
				'color_drawer_line_price'   => '#1a1a1a',
				'color_button_primary'      => '#111111',
				'color_button_primary_text' => '#ffffff',
				'color_button_primary_hover' => '#333333',
				'color_button_primary_text_hover' => '#ffffff',
				/** inherit | system | serif | mono | custom */
				'font_family_preset'        => 'inherit',
				/** Used when preset is custom; CSS font-family stack. */
				'font_family_custom'        => '',
				/** Scales summary + button font sizes on the sticky bar (70–130%). */
				'typography_scale_percent'  => 100,
			),
			'diagnostics' => array(
				'client_error_logging' => true,
				'log_retention_days'   => 14,
				/** Max rows kept after time-based prune (cap). */
				'log_max_entries'      => 300,
				/** Max serialized size of the log option (bytes); oldest rows dropped first. */
				'log_max_bytes'        => 262144,
			),
			'notices'     => array(
				/** Strip the "View cart" anchor from WooCommerce add-to-cart success HTML (sticky replaces cart UX). */
				'remove_view_cart_link' => true,
			),
		);
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
