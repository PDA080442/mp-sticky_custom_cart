<?php
/**
 * Maps admin-driven settings to CSS custom properties (--mp-scc-*).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Config;

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

			$out[ $row['name'] ] = self::format_value( $raw, $row );
		}

		$out[ self::PREFIX . 'sticky-layout-reserve' ] = self::format_sticky_layout_reserve_px( $merged_settings );

		return $out;
	}

	/**
	 * Approximate height of the fixed bar (padding + one/two rows) for body scroll padding — reduces CLS.
	 *
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
