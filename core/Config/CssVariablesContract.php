<?php
/**
 * Maps admin-driven settings to CSS custom properties (--mp-scc-*).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Config;

use MpStickyCustomCart\Core\CatalogCartIconAppearance;
use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Contract: variable names are stable; values come from merged settings via dot paths.
 */
final class CssVariablesContract {

	/**
	 * Prefix for all theme variables exposed to frontend CSS.
	 */
	public const PREFIX = '--mp-scc-';

	/**
	 * Selector used when injecting rules (e.g. :root or .mp-scc-root).
	 */
	public const DEFAULT_ROOT_SELECTOR = ':root';

	/**
	 * @return array<int, array{name:string, path:string, suffix:string, format:string}>
	 */
	public static function get_map() {
		return array(
			array(
				'name'   => self::PREFIX . 'catalog-hover-duration-ms',
				'path'   => 'catalog.hover_animation_duration_ms',
				'suffix' => 'ms',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'catalog-hover-easing',
				'path'   => 'catalog.hover_animation_easing',
				'suffix' => '',
				'format' => 'raw',
			),
			array(
				'name'   => self::PREFIX . 'catalog-hover-slide-y',
				'path'   => 'catalog.hover_slide_offset_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'catalog-hover-hide-delay-ms',
				'path'   => 'catalog.hover_hide_delay_ms',
				'suffix' => 'ms',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'catalog-overlay-z-index',
				'path'   => 'catalog.catalog_overlay_z_index',
				'suffix' => '',
				'format' => 'integer',
			),
			array(
				'name'   => self::PREFIX . 'catalog-cart-icon-hit-size',
				'path'   => 'catalog.catalog_cart_icon_hit_size_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'catalog-cart-icon-glyph-size',
				'path'   => 'catalog.catalog_cart_icon_glyph_size_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'           => self::PREFIX . 'catalog-cart-icon-box-w-mobile',
				'path'           => 'catalog.catalog_cart_icon_box_width_mobile_px',
				'suffix'         => 'px',
				'format'         => 'unit',
				'omit_if_zero'   => true,
			),
			array(
				'name'           => self::PREFIX . 'catalog-cart-icon-box-h-mobile',
				'path'           => 'catalog.catalog_cart_icon_box_height_mobile_px',
				'suffix'         => 'px',
				'format'         => 'unit',
				'omit_if_zero'   => true,
			),
			array(
				'name'   => self::PREFIX . 'catalog-cart-icon-transition-delay',
				'path'   => 'catalog.catalog_cart_icon_transition_delay_ms',
				'suffix' => 'ms',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'catalog-cart-icon-border-radius',
				'path'   => 'catalog.catalog_cart_icon_bg_border_radius_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'catalog-cart-icon-inner-padding',
				'path'   => 'catalog.catalog_cart_icon_inner_padding_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-z-index',
				'path'   => 'sticky_cart.z_index',
				'suffix' => '',
				'format' => 'integer',
			),
			array(
				'name'   => self::PREFIX . 'sticky-backdrop-blur',
				'path'   => 'sticky_cart.surface_backdrop_blur_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-surface-alpha',
				'path'   => 'sticky_cart.surface_background_alpha',
				'suffix' => '',
				'format' => 'float',
			),
			array(
				'name'   => self::PREFIX . 'sticky-border-radius',
				'path'   => 'sticky_cart.border_radius_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-padding-x-desktop',
				'path'   => 'sticky_cart.padding_x_desktop_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-padding-y-desktop',
				'path'   => 'sticky_cart.padding_y_desktop_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-padding-x-mobile',
				'path'   => 'sticky_cart.padding_x_mobile_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-padding-y-mobile',
				'path'   => 'sticky_cart.padding_y_mobile_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-drawer-max-height',
				'path'   => 'sticky_cart.drawer_max_height_vh',
				'suffix' => 'vh',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-drawer-padding-x',
				'path'   => 'sticky_cart.drawer_padding_x_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-drawer-padding-y',
				'path'   => 'sticky_cart.drawer_padding_y_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-drawer-toggle-duration-ms',
				'path'   => 'sticky_cart.drawer_toggle_duration_ms',
				'suffix' => 'ms',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-drawer-toggle-easing',
				'path'   => 'sticky_cart.drawer_toggle_easing',
				'suffix' => '',
				'format' => 'raw',
			),
			array(
				'name'   => self::PREFIX . 'sticky-quantity-debounce-ms',
				'path'   => 'sticky_cart.quantity_debounce_ms',
				'suffix' => 'ms',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-summary-font-size',
				'path'   => 'sticky_cart.summary_font_size_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-summary-font-weight',
				'path'   => 'sticky_cart.summary_font_weight',
				'suffix' => '',
				'format' => 'integer',
			),
			array(
				'name'   => self::PREFIX . 'sticky-summary-line-height',
				'path'   => 'sticky_cart.summary_line_height',
				'suffix' => '',
				'format' => 'float',
			),
			array(
				'name'   => self::PREFIX . 'sticky-button-font-size',
				'path'   => 'sticky_cart.button_font_size_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-button-font-weight',
				'path'   => 'sticky_cart.button_font_weight',
				'suffix' => '',
				'format' => 'integer',
			),
			array(
				'name'   => self::PREFIX . 'sticky-button-line-height',
				'path'   => 'sticky_cart.button_line_height',
				'suffix' => '',
				'format' => 'float',
			),
			array(
				'name'   => self::PREFIX . 'sticky-inner-gap-mobile',
				'path'   => 'sticky_cart.sticky_inner_gap_mobile_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-inner-gap-desktop-row',
				'path'   => 'sticky_cart.sticky_inner_gap_desktop_row_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-inner-gap-desktop-col',
				'path'   => 'sticky_cart.sticky_inner_gap_desktop_col_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-summary-gap',
				'path'   => 'sticky_cart.summary_gap_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-actions-gap',
				'path'   => 'sticky_cart.actions_gap_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-summary-text-gap-row',
				'path'   => 'sticky_cart.summary_text_gap_row_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-summary-text-gap-column',
				'path'   => 'sticky_cart.summary_text_gap_column_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'         => self::PREFIX . 'sticky-clear-min-width',
				'path'         => 'sticky_cart.clear_button_min_width_px',
				'suffix'       => 'px',
				'format'       => 'unit',
				'omit_if_zero' => true,
			),
			array(
				'name'         => self::PREFIX . 'sticky-checkout-min-width',
				'path'         => 'sticky_cart.checkout_button_min_width_px',
				'suffix'       => 'px',
				'format'       => 'unit',
				'omit_if_zero' => true,
			),
			array(
				'name'   => self::PREFIX . 'tristate-panel-b-max-height',
				'path'   => 'sticky_cart.tristate_panel_b_max_height_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-panel-b-width',
				'path'   => 'sticky_cart.tristate_panel_b_width_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-panel-b-gap-bottom',
				'path'   => 'sticky_cart.tristate_panel_b_gap_bottom_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-panel-b-padding',
				'path'   => 'sticky_cart.tristate_panel_b_padding_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-panel-b-border-radius',
				'path'   => 'sticky_cart.tristate_panel_b_border_radius_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-panel-b-actions-gap',
				'path'   => 'sticky_cart.tristate_panel_b_actions_gap_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-metrics-row-padding-y',
				'path'   => 'sticky_cart.tristate_metrics_row_padding_y_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'         => self::PREFIX . 'tristate-metrics-hr-margin-y-panel-b',
				'path'         => 'sticky_cart.tristate_metrics_hr_margin_y_panel_b_px',
				'suffix'       => 'px',
				'format'       => 'unit',
				'omit_if_zero' => true,
			),
			array(
				'name'   => self::PREFIX . 'tristate-metrics-hr-margin-y-drawer-c',
				'path'   => 'sticky_cart.tristate_metrics_hr_margin_y_drawer_c_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'         => self::PREFIX . 'tristate-metrics-hr-border-width',
				'path'         => 'sticky_cart.tristate_metrics_hr_border_width_px',
				'suffix'       => 'px',
				'format'       => 'unit',
				'omit_if_zero' => true,
			),
			array(
				'name'   => self::PREFIX . 'tristate-metrics-hr-style',
				'path'   => 'sticky_cart.tristate_metrics_hr_style',
				'suffix' => '',
				'format' => 'raw',
			),
			array(
				'name'   => self::PREFIX . 'tristate-actions-toolbar-padding-top',
				'path'   => 'sticky_cart.tristate_actions_toolbar_padding_top_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-actions-toolbar-padding-right',
				'path'   => 'sticky_cart.tristate_actions_toolbar_padding_right_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-actions-toolbar-padding-bottom',
				'path'   => 'sticky_cart.tristate_actions_toolbar_padding_bottom_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-actions-toolbar-padding-left',
				'path'   => 'sticky_cart.tristate_actions_toolbar_padding_left_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-action-icon-hit',
				'path'   => 'sticky_cart.tristate_action_icon_hit_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-action-icon-glyph',
				'path'   => 'sticky_cart.tristate_action_icon_glyph_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-hit',
				'path'   => 'sticky_cart.tristate_dismiss_hit_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-margin-top',
				'path'   => 'sticky_cart.tristate_dismiss_margin_top_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-margin-right',
				'path'   => 'sticky_cart.tristate_dismiss_margin_right_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-margin-bottom',
				'path'   => 'sticky_cart.tristate_dismiss_margin_bottom_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-margin-left',
				'path'   => 'sticky_cart.tristate_dismiss_margin_left_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-padding-top',
				'path'   => 'sticky_cart.tristate_dismiss_padding_top_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-padding-right',
				'path'   => 'sticky_cart.tristate_dismiss_padding_right_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-padding-bottom',
				'path'   => 'sticky_cart.tristate_dismiss_padding_bottom_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-padding-left',
				'path'   => 'sticky_cart.tristate_dismiss_padding_left_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-border-width',
				'path'   => 'sticky_cart.tristate_dismiss_border_width_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-border-radius',
				'path'   => 'sticky_cart.tristate_dismiss_border_radius_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-color',
				'path'   => 'sticky_cart.tristate_dismiss_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-bg',
				'path'   => 'sticky_cart.tristate_dismiss_bg_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-border-color',
				'path'   => 'sticky_cart.tristate_dismiss_border_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-hover-color',
				'path'   => 'sticky_cart.tristate_dismiss_hover_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-hover-bg',
				'path'   => 'sticky_cart.tristate_dismiss_hover_bg_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-hover-border-color',
				'path'   => 'sticky_cart.tristate_dismiss_hover_border_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dismiss-glyph',
				'path'   => 'sticky_cart.tristate_dismiss_glyph_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-panel-c-width',
				'path'   => 'sticky_cart.tristate_panel_c_width_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-panel-c-height',
				'path'   => 'sticky_cart.tristate_panel_c_height_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-state-a-dock-inset-top',
				'path'   => 'sticky_cart.tristate_state_a_dock_inset_top_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-state-a-dock-inset-right',
				'path'   => 'sticky_cart.tristate_state_a_dock_inset_right_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-state-a-dock-inset-bottom',
				'path'   => 'sticky_cart.tristate_state_a_dock_inset_bottom_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-state-a-dock-inset-left',
				'path'   => 'sticky_cart.tristate_state_a_dock_inset_left_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-fab-badge-size',
				'path'   => 'sticky_cart.tristate_fab_badge_size_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-fab-badge-offset-top',
				'path'   => 'sticky_cart.tristate_fab_badge_offset_top_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-fab-badge-offset-right',
				'path'   => 'sticky_cart.tristate_fab_badge_offset_right_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-fab-badge-font-size',
				'path'   => 'sticky_cart.tristate_fab_badge_font_size_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-fab-badge-bg',
				'path'   => 'sticky_cart.tristate_fab_badge_bg_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'tristate-fab-badge-text',
				'path'   => 'sticky_cart.tristate_fab_badge_text_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dock-inset-top',
				'path'   => 'sticky_cart.tristate_dock_inset_top_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dock-inset-right',
				'path'   => 'sticky_cart.tristate_dock_inset_right_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dock-inset-bottom',
				'path'   => 'sticky_cart.tristate_dock_inset_bottom_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-dock-inset-left',
				'path'   => 'sticky_cart.tristate_dock_inset_left_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-column-backdrop-blur',
				'path'   => 'sticky_cart.tristate_column_backdrop_blur_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-column-shadow-blur',
				'path'   => 'sticky_cart.tristate_column_shadow_blur_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'tristate-column-shadow-offset-y',
				'path'   => 'sticky_cart.tristate_column_shadow_offset_y_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'color-text-primary',
				'path'   => 'styles.color_text_primary',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-surface-tint',
				'path'   => 'styles.color_surface_tint',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-button-primary',
				'path'   => 'styles.color_button_primary',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-button-primary-text',
				'path'   => 'styles.color_button_primary_text',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-button-primary-hover',
				'path'   => 'styles.color_button_primary_hover',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-button-primary-text-hover',
				'path'   => 'styles.color_button_primary_text_hover',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-drawer-surface-tint',
				'path'   => 'styles.color_drawer_surface_tint',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'sticky-drawer-surface-alpha',
				'path'   => 'sticky_cart.drawer_surface_background_alpha',
				'suffix' => '',
				'format' => 'float',
			),
			array(
				'name'   => self::PREFIX . 'color-clear-button-text',
				'path'   => 'styles.color_clear_button_text',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-clear-button-text-hover',
				'path'   => 'styles.color_clear_button_text_hover',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-clear-button-border',
				'path'   => 'styles.color_clear_button_border',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-clear-button-border-hover',
				'path'   => 'styles.color_clear_button_border_hover',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-clear-button-bg',
				'path'   => 'styles.color_clear_button_bg',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-clear-button-bg-hover',
				'path'   => 'styles.color_clear_button_bg_hover',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-qty-button-bg',
				'path'   => 'styles.color_qty_button_bg',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-qty-button-bg-hover',
				'path'   => 'styles.color_qty_button_bg_hover',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-qty-button-border',
				'path'   => 'styles.color_qty_button_border',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-qty-button-border-hover',
				'path'   => 'styles.color_qty_button_border_hover',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-qty-button-text',
				'path'   => 'styles.color_qty_button_text',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-qty-button-text-hover',
				'path'   => 'styles.color_qty_button_text_hover',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-drawer-line-title',
				'path'   => 'styles.color_drawer_line_title',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-drawer-line-unit',
				'path'   => 'styles.color_drawer_line_unit',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'color-drawer-line-price',
				'path'   => 'styles.color_drawer_line_price',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'sticky-drawer-line-title-font-size',
				'path'   => 'sticky_cart.drawer_line_title_font_size_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-drawer-line-title-font-weight',
				'path'   => 'sticky_cart.drawer_line_title_font_weight',
				'suffix' => '',
				'format' => 'integer',
			),
			array(
				'name'   => self::PREFIX . 'sticky-drawer-line-unit-font-size',
				'path'   => 'sticky_cart.drawer_line_unit_font_size_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-drawer-line-price-font-size',
				'path'   => 'sticky_cart.drawer_line_price_font_size_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'sticky-drawer-line-price-font-weight',
				'path'   => 'sticky_cart.drawer_line_price_font_weight',
				'suffix' => '',
				'format' => 'integer',
			),
			array(
				'name'   => self::PREFIX . 'wishlist-heart-reserve-top',
				'path'   => 'wishlist_ui.heart_reserve_top_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'wishlist-heart-reserve-right',
				'path'   => 'wishlist_ui.heart_reserve_right_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'wishlist-overlay-clearance',
				'path'   => 'wishlist_ui.overlay_clearance_heart_px',
				'suffix' => 'px',
				'format' => 'unit',
			),
			array(
				'name'   => self::PREFIX . 'wishlist-icon-z-index',
				'path'   => 'wishlist_ui.heart_icon_z_index',
				'suffix' => '',
				'format' => 'integer',
			),
			array(
				'name'   => self::PREFIX . 'wishlist-heart-bg',
				'path'   => 'wishlist_ui.heart_idle_bg_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'wishlist-heart-color',
				'path'   => 'wishlist_ui.heart_idle_icon_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'wishlist-heart-in-list-bg',
				'path'   => 'wishlist_ui.heart_in_wishlist_bg_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'wishlist-heart-in-list-color',
				'path'   => 'wishlist_ui.heart_in_wishlist_icon_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'wishlist-heart-bg-hover',
				'path'   => 'wishlist_ui.heart_idle_hover_bg_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'wishlist-heart-color-hover',
				'path'   => 'wishlist_ui.heart_idle_hover_icon_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'wishlist-heart-in-list-bg-hover',
				'path'   => 'wishlist_ui.heart_in_wishlist_hover_bg_color',
				'suffix' => '',
				'format' => 'color',
			),
			array(
				'name'   => self::PREFIX . 'wishlist-heart-in-list-color-hover',
				'path'   => 'wishlist_ui.heart_in_wishlist_hover_icon_color',
				'suffix' => '',
				'format' => 'color',
			),
		);
	}

	/**
	 * Build CSS custom property name => value for inline or file emission.
	 *
	 * @param array<string, mixed> $merged_settings Result of {@see OptionResolver::get_settings()}.
	 * @return array<string, string>
	 */
	public static function build_properties( array $merged_settings ) {
		$out = array();

		foreach ( self::get_map() as $row ) {
			$raw = OptionResolver::get_by_path( $merged_settings, $row['path'], null );
			if ( null === $raw ) {
				continue;
			}
			if ( ! empty( $row['omit_if_zero'] ) && is_numeric( $raw ) && (float) $raw <= 0 ) {
				continue;
			}

			$out[ $row['name'] ] = self::format_value( $raw, $row );
		}

		$out[ self::PREFIX . 'sticky-font-family' ] = self::resolve_sticky_font_family( $merged_settings );

		$scale = (int) OptionResolver::get_by_path( $merged_settings, 'styles.typography_scale_percent', 100 );
		$scale = max( 70, min( 130, $scale ) );
		if ( 100 !== $scale ) {
			$sum = (int) OptionResolver::get_by_path( $merged_settings, 'sticky_cart.summary_font_size_px', 15 );
			$btn = (int) OptionResolver::get_by_path( $merged_settings, 'sticky_cart.button_font_size_px', 14 );
			$out[ self::PREFIX . 'sticky-summary-font-size' ]  = (string) max( 8, (int) round( $sum * $scale / 100 ) ) . 'px';
			$out[ self::PREFIX . 'sticky-button-font-size' ] = (string) max( 8, (int) round( $btn * $scale / 100 ) ) . 'px';
		}

		$out[ self::PREFIX . 'sticky-layout-reserve' ] = self::format_sticky_layout_reserve_px( $merged_settings );

		// Reserve under floating FAB (phase 17.2); body uses it only with .mp-scc-sticky-layout-tristate.
		$out[ self::PREFIX . 'sticky-fab-layout-reserve' ] = '72px';

		$z = (int) OptionResolver::get_by_path( $merged_settings, 'sticky_cart.z_index', 100050 );
		$z = max( 1, min( 9999999, $z ) );
		$out[ self::PREFIX . 'tristate-z-panel-b' ]   = (string) ( $z + 5 );
		$out[ self::PREFIX . 'tristate-z-drawer-c' ] = (string) ( $z + 6 );

		$shadow_pct = (int) OptionResolver::get_by_path( $merged_settings, 'sticky_cart.tristate_column_shadow_opacity_percent', 12 );
		$shadow_pct = max( 4, min( 28, $shadow_pct ) );
		$out[ self::PREFIX . 'tristate-column-shadow-alpha' ] = (string) round( $shadow_pct / 100, 4 );

		$hr_hex = (string) OptionResolver::get_by_path( $merged_settings, 'sticky_cart.tristate_metrics_hr_color', '#000000' );
		$hr_pct = (int) OptionResolver::get_by_path( $merged_settings, 'sticky_cart.tristate_metrics_hr_opacity_percent', 14 );
		$hr_pct = max( 0, min( 100, $hr_pct ) );
		$tri    = self::parse_hex_rgb_triplet( $hr_hex );
		$r      = ( $tri && isset( $tri[0] ) ) ? $tri[0] : 0;
		$g      = ( $tri && isset( $tri[1] ) ) ? $tri[1] : 0;
		$b      = ( $tri && isset( $tri[2] ) ) ? $tri[2] : 0;
		$hr_a   = $hr_pct / 100.0;
		$out[ self::PREFIX . 'tristate-metrics-hr-border-color' ] = sprintf(
			'rgba(%d,%d,%d,%s)',
			$r,
			$g,
			$b,
			self::format_css_alpha_string( $hr_a )
		);

		self::apply_catalog_cart_icon_appearance_tokens( $merged_settings, $out );

		return $out;
	}

	/**
	 * Glyph color + button background from {@see CatalogCartIconAppearance} (two admin presets).
	 *
	 * @param array<string, mixed>    $merged_settings Merged settings tree.
	 * @param array<string, string> $out               Output map (by ref).
	 */
	private static function apply_catalog_cart_icon_appearance_tokens( array $merged_settings, array &$out ) {
		$catalog = OptionResolver::get_by_path( $merged_settings, 'catalog', array() );
		if ( ! is_array( $catalog ) ) {
			$catalog = array();
		}
		$r       = CatalogCartIconAppearance::resolve( $catalog );
		$row     = array( 'format' => 'color' );
		$glyph   = sanitize_hex_color( $r['glyph_hex'] );
		$out[ self::PREFIX . 'catalog-cart-icon-color' ] = self::format_value( $glyph ? $glyph : $r['glyph_hex'], $row );

		foreach ( self::build_catalog_cart_icon_background_from_hex_alpha( $r['bg_hex'], $r['bg_alpha_percent'] ) as $name => $value ) {
			$out[ $name ] = $value;
		}

		// Hover: invert face vs glyph (same idea as wishlist heart: dark/light swap).
		$tri = self::parse_hex_rgb_triplet( $r['glyph_hex'] );
		if ( null !== $tri ) {
			$out[ self::PREFIX . 'catalog-cart-icon-background-hover' ] = sprintf(
				'rgba(%d,%d,%d,1)',
				$tri[0],
				$tri[1],
				$tri[2]
			);
		} else {
			$out[ self::PREFIX . 'catalog-cart-icon-background-hover' ] = 'rgba(26,26,26,1)';
		}
		$icon_hover = sanitize_hex_color( $r['bg_hex'] );
		$out[ self::PREFIX . 'catalog-cart-icon-color-hover' ] = self::format_value(
			$icon_hover ? $icon_hover : '#ffffff',
			$row
		);
	}

	/**
	 * rgba() for default button face (hover uses inverted solid colors in {@see self::apply_catalog_cart_icon_appearance_tokens}).
	 *
	 * @param string $bg_hex          #rrggbb
	 * @param int    $bg_alpha_percent 0–100
	 * @return array<string, string>
	 */
	private static function build_catalog_cart_icon_background_from_hex_alpha( $bg_hex, $bg_alpha_percent ) {
		$hex = sanitize_hex_color( (string) $bg_hex );
		if ( ! is_string( $hex ) || '' === $hex ) {
			$hex = '#ffffff';
		}
		$pct = (int) $bg_alpha_percent;
		$pct = max( 0, min( 100, $pct ) );
		$a   = $pct / 100.0;

		$stripped = strtolower( ltrim( $hex, '#' ) );
		if ( 3 === strlen( $stripped ) && ctype_xdigit( $stripped ) ) {
			$stripped = $stripped[0] . $stripped[0] . $stripped[1] . $stripped[1] . $stripped[2] . $stripped[2];
		}
		$r = 255;
		$g = 255;
		$b = 255;
		if ( 6 === strlen( $stripped ) && ctype_xdigit( $stripped ) ) {
			$r = hexdec( substr( $stripped, 0, 2 ) );
			$g = hexdec( substr( $stripped, 2, 2 ) );
			$b = hexdec( substr( $stripped, 4, 2 ) );
		}

		return array(
			self::PREFIX . 'catalog-cart-icon-background' => sprintf(
				'rgba(%d,%d,%d,%s)',
				$r,
				$g,
				$b,
				self::format_css_alpha_string( $a )
			),
		);
	}

	/**
	 * @param string $hex #rrggbb (or #rgb expanded by sanitize_hex_color).
	 * @return array{0:int,1:int,2:int}|null
	 */
	private static function parse_hex_rgb_triplet( $hex ) {
		$hex = sanitize_hex_color( (string) $hex );
		if ( ! is_string( $hex ) || '' === $hex ) {
			return null;
		}
		$stripped = strtolower( ltrim( $hex, '#' ) );
		if ( 3 === strlen( $stripped ) && ctype_xdigit( $stripped ) ) {
			$stripped = $stripped[0] . $stripped[0] . $stripped[1] . $stripped[1] . $stripped[2] . $stripped[2];
		}
		if ( 6 !== strlen( $stripped ) || ! ctype_xdigit( $stripped ) ) {
			return null;
		}

		return array(
			(int) hexdec( substr( $stripped, 0, 2 ) ),
			(int) hexdec( substr( $stripped, 2, 2 ) ),
			(int) hexdec( substr( $stripped, 4, 2 ) ),
		);
	}

	/**
	 * @param float $a 0..1
	 */
	private static function format_css_alpha_string( $a ) {
		$a = (float) $a;
		if ( $a >= 1.0 ) {
			return '1';
		}
		if ( $a <= 0.0 ) {
			return '0';
		}
		$s = rtrim( rtrim( sprintf( '%.4f', $a ), '0' ), '.' );

		return '' === $s ? '0' : $s;
	}

	/**
	 * Approximate height of the fixed bar (padding + one/two rows) for body scroll padding — reduces CLS.
	 *
	 * @param array<string, mixed> $merged_settings Merged settings tree.
	 */
	/**
	 * Font stack for the sticky bar / drawer (styles tab).
	 *
	 * @param array<string, mixed> $merged_settings Merged settings tree.
	 */
	public static function resolve_sticky_font_family( array $merged_settings ) {
		$preset = (string) OptionResolver::get_by_path( $merged_settings, 'styles.font_family_preset', 'inherit' );
		$custom = trim( (string) OptionResolver::get_by_path( $merged_settings, 'styles.font_family_custom', '' ) );

		if ( 'custom' === $preset ) {
			return '' !== $custom ? $custom : 'inherit';
		}

		switch ( $preset ) {
			case 'system':
				return 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
			case 'serif':
				return 'Georgia, "Times New Roman", Times, serif';
			case 'mono':
				return 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace';
			case 'inherit':
			default:
				return 'inherit';
		}
	}

	/**
	 * @param array<string, mixed> $merged_settings Merged settings tree.
	 */
	private static function format_sticky_layout_reserve_px( array $merged_settings ) {
		$py_m = (int) OptionResolver::get_by_path( $merged_settings, 'sticky_cart.padding_y_mobile_px', 12 );
		$py_d = (int) OptionResolver::get_by_path( $merged_settings, 'sticky_cart.padding_y_desktop_px', 14 );
		$btn  = (int) OptionResolver::get_by_path( $merged_settings, 'sticky_cart.button_font_size_px', 14 );
		$sum  = (int) OptionResolver::get_by_path( $merged_settings, 'sticky_cart.summary_font_size_px', 15 );
		$drawer_toggle = 44;
		$mobile_stack  = (int) round( ( 2 * $py_m ) + ( 1.25 * $sum ) + 10 + max( 38.0, 1.45 * $btn ) + $drawer_toggle );
		$desktop_row   = (int) round( ( 2 * $py_d ) + max( 1.25 * $sum, 1.45 * $btn, 40.0 ) + 12 );
		$n             = max( $mobile_stack, $desktop_row, 92 );

		return (string) $n . 'px';
	}

	/**
	 * @param mixed                $raw  Raw value from settings tree.
	 * @param array{name:string, path:string, suffix:string, format:string} $row Contract row.
	 */
	private static function format_value( $raw, array $row ) {
		switch ( $row['format'] ) {
			case 'raw':
				return is_scalar( $raw ) ? (string) $raw : '';

			case 'integer':
				return (string) (int) $raw;

			case 'float':
				return is_numeric( $raw ) ? (string) (float) $raw : '0';

			case 'color':
				$c = is_string( $raw ) ? trim( $raw ) : '';
				$hex = sanitize_hex_color( $c );
				return $hex ? $hex : '#000000';

			case 'unit':
			default:
				$n = is_numeric( $raw ) ? 0 + $raw : 0;
				return (string) $n . $row['suffix'];
		}
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
