<?php
/**
 * Two curated presets for the shop-loop cart icon (glyph + button face).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces manual per-channel color fields for the catalog cart icon (simpler admin UX).
 */
final class CatalogCartIconAppearance {

	/** Dark glyph (#1a1a1a), light frosted button (white ~94%). */
	public const PRESET_BLACK_CART_WHITE_BG = 'black_cart_white_bg';

	/** Light glyph (white), dark button (#111 ~94%). */
	public const PRESET_WHITE_CART_BLACK_BG = 'white_cart_black_bg';

	/**
	 * @param mixed $raw Saved value.
	 * @return string One of {@see self::PRESET_*}
	 */
	public static function normalize_preset( $raw ) {
		$s = is_string( $raw ) ? $raw : '';
		if ( self::PRESET_WHITE_CART_BLACK_BG === $s ) {
			return self::PRESET_WHITE_CART_BLACK_BG;
		}

		return self::PRESET_BLACK_CART_WHITE_BG;
	}

	/**
	 * Resolved colors for CSS and admin preview.
	 *
	 * @param array<string, mixed> $catalog `catalog` section (merged).
	 * @return array{glyph_hex:string,bg_hex:string,bg_alpha_percent:int}
	 */
	public static function resolve( array $catalog ) {
		$p = self::normalize_preset( isset( $catalog['catalog_cart_icon_appearance_preset'] ) ? $catalog['catalog_cart_icon_appearance_preset'] : '' );

		if ( self::PRESET_WHITE_CART_BLACK_BG === $p ) {
			return array(
				'glyph_hex'          => '#ffffff',
				'bg_hex'             => '#111111',
				'bg_alpha_percent'   => 94,
			);
		}

		return array(
			'glyph_hex'          => '#1a1a1a',
			'bg_hex'             => '#ffffff',
			'bg_alpha_percent'   => 94,
		);
	}
}
