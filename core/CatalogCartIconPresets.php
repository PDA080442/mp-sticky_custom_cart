<?php
/**
 * Built-in catalog loop cart icon SVG variants (single stroke + geometry source of truth for admin + storefront).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Preset IDs and inner SVG fragments. Stroke width placeholder {@see CatalogCartIconPresets::STROKE_PLACEHOLDER}.
 */
final class CatalogCartIconPresets {

	public const DEFAULT = 'classic';

	/**
	 * Replaced in templates with numeric stroke width (storefront + admin preview).
	 */
	public const STROKE_PLACEHOLDER = '__MP_SCC_SW__';

	/**
	 * Ordered list of preset keys (admin + schema oneof).
	 *
	 * @var list<string>
	 */
	public const IDS = array(
		'classic',
		'lucide',
		'basket',
		'hero_compact',
		'tabler',
		'outline_bold',
		'minimal',
		'soft_round',
		'angular',
		'retail',
		/** Same filled-cart geometry as tri-state FAB `.mp-scc-drawer-toggle-icon` (Material-style mask path). */
		'tristate_panel_a',
	);

	/**
	 * @return list<string>
	 */
	public static function keys() {
		return self::IDS;
	}

	/**
	 * @param string $raw Raw slug from settings.
	 */
	public static function normalize( $raw ) {
		$id = is_string( $raw ) ? sanitize_key( $raw ) : '';

		return in_array( $id, self::IDS, true ) ? $id : self::DEFAULT;
	}

	/**
	 * Localized short labels for the admin preset grid.
	 *
	 * @return array<string, string>
	 */
	public static function labels() {
		return array(
			'classic'       => __( 'Классика', 'mp-sticky-custom-cart' ),
			'lucide'        => __( 'Lucide', 'mp-sticky-custom-cart' ),
			'basket'        => __( 'Корзина', 'mp-sticky-custom-cart' ),
			'hero_compact'  => __( 'Компактная', 'mp-sticky-custom-cart' ),
			'tabler'        => __( 'Tabler', 'mp-sticky-custom-cart' ),
			'outline_bold'  => __( 'Жирный контур', 'mp-sticky-custom-cart' ),
			'minimal'       => __( 'Минимал', 'mp-sticky-custom-cart' ),
			'soft_round'    => __( 'Плавная', 'mp-sticky-custom-cart' ),
			'angular'       => __( 'Угловатая', 'mp-sticky-custom-cart' ),
			'retail'        => __( 'Витрина', 'mp-sticky-custom-cart' ),
			'tristate_panel_a' => __( 'Панель A (как FAB)', 'mp-sticky-custom-cart' ),
		);
	}

	/**
	 * Inner markup only (children of root &lt;svg&gt;), 24×24 viewBox.
	 *
	 * @return array<string, string>
	 */
	private static function inner_templates() {
		$w = self::STROKE_PLACEHOLDER;

		return array(
			// Feather-style (legacy default).
			'classic'      => '<circle cx="9" cy="21" r="1" fill="currentColor"/><circle cx="20" cy="21" r="1" fill="currentColor"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" fill="none" stroke="currentColor" stroke-width="' . $w . '" stroke-linecap="round" stroke-linejoin="round"/>',
			// Lucide shopping-cart.
			'lucide'       => '<circle cx="8" cy="21" r="1" fill="currentColor"/><circle cx="19" cy="21" r="1" fill="currentColor"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" fill="none" stroke="currentColor" stroke-width="' . $w . '" stroke-linecap="round" stroke-linejoin="round"/>',
			// Lucide shopping-basket (wireframe basket + wheels).
			'basket'       => '<g fill="none" stroke="currentColor" stroke-width="' . $w . '" stroke-linecap="round" stroke-linejoin="round"><path d="m5 11 4-7"/><path d="M19 11h-16"/><path d="m19 11-4 7"/><path d="M2 11h20"/><path d="M9 12v6"/><path d="M15 12v6"/><circle cx="9.5" cy="18" r="1.5" fill="none"/><circle cx="14.5" cy="18" r="1.5" fill="none"/></g>',
			// Heroicons-style compact cart + wheels (single path).
			'hero_compact' => '<path fill="none" stroke="currentColor" stroke-width="' . $w . '" stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>',
			// Tabler-style angled cart with stroke wheels.
			'tabler'       => '<g fill="none" stroke="currentColor" stroke-width="' . $w . '" stroke-linecap="round" stroke-linejoin="round"><path d="M6 19a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 19a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/></g>',
			// Bold outline (rounded trapezoid + handle + ring wheels), similar to heavy retail icons.
			'outline_bold' => '<g fill="none" stroke="currentColor" stroke-width="' . $w . '" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6V4h2.5"/><path d="M4 6h14.5l-2.2 11H7.4L4.6 8H20"/><circle cx="9" cy="20.5" r="1.85"/><circle cx="16.5" cy="20.5" r="1.85"/></g>',
			'minimal'      => '<path fill="none" stroke="currentColor" stroke-width="' . $w . '" stroke-linecap="round" stroke-linejoin="round" d="M5 7h14l-1.5 9h-10L5 7z"/><circle cx="9.5" cy="19" r="1" fill="currentColor"/><circle cx="16" cy="19" r="1" fill="currentColor"/>',
			'soft_round'   => '<g fill="none" stroke="currentColor" stroke-width="' . $w . '" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2l1.2 10.2a2.2 2.2 0 0 0 2.18 1.8h8.24a2.1 2.1 0 0 0 2.08-1.7L21 8H7"/><circle cx="9.5" cy="20" r="1.35" fill="currentColor"/><circle cx="17" cy="20" r="1.35" fill="currentColor"/></g>',
			'angular'      => '<g fill="none" stroke="currentColor" stroke-width="' . $w . '" stroke-linecap="round" stroke-linejoin="miter"><path d="M2 3h3l3 14h11l3-10H8"/><path d="M2 3v1"/><circle cx="10" cy="21" r="1" fill="currentColor"/><circle cx="18" cy="21" r="1" fill="currentColor"/></g>',
			'retail'       => '<g fill="none" stroke="currentColor" stroke-width="' . $w . '" stroke-linecap="round" stroke-linejoin="round"><path d="M1 2h3l1.5 13a2 2 0 0 0 2 1.75h9a2 2 0 0 0 2-1.65L21 7H6"/><path d="M5.5 2 7 7"/><circle cx="9.5" cy="21" r="1.5" fill="currentColor"/><circle cx="17" cy="21" r="1.5" fill="currentColor"/></g>',
			// Filled cart: matches `assets/css/frontend.css` mask on `.mp-scc-drawer-toggle--fab .mp-scc-drawer-toggle-icon` (stroke width setting is ignored).
			'tristate_panel_a' => '<path fill="currentColor" d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.15.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>',
		);
	}

	/**
	 * Template for a preset, or classic if unknown.
	 */
	public static function inner_template( $id ) {
		$map = self::inner_templates();
		$key = self::normalize( $id );

		return isset( $map[ $key ] ) ? $map[ $key ] : $map[ self::DEFAULT ];
	}

	/**
	 * Map preset id → inner fragment (for frontend localize JSON).
	 *
	 * @return array<string, string>
	 */
	public static function inner_templates_for_js() {
		return self::inner_templates();
	}

	/**
	 * Full SVG element for admin previews / optional PHP reuse.
	 *
	 * @param int   $width  Pixel width attribute.
	 * @param int   $height Pixel height attribute.
	 * @param float $stroke Stroke width in user units (1–3).
	 */
	public static function svg_markup( $id, $width, $height, $stroke ) {
		$sw    = is_numeric( $stroke ) ? (float) $stroke : 1.75;
		$sw    = min( 3.0, max( 1.0, $sw ) );
		$inner = str_replace( self::STROKE_PLACEHOLDER, (string) ( round( $sw * 100 ) / 100 ), self::inner_template( $id ) );

		return sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">%s</svg>',
			max( 1, (int) $width ),
			max( 1, (int) $height ),
			$inner
		);
	}
}
