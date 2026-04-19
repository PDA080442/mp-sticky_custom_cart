<?php
/**
 * Output hooks for the sticky cart markup.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\Config\FeatureFlagsDefaults;
use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Registers hooks so the sticky shell prints outside theme footers (fixed positioning vs. viewport).
 */
final class StickyCartRenderHooks {

	/**
	 * Ensures the cart root is only printed once when both body and footer hooks run.
	 *
	 * @var bool
	 */
	private static $sticky_root_printed = false;

	public static function register() {
		add_filter( 'body_class', array( self::class, 'body_class' ), 20 );
		/*
		 * Prefer {@see wp_body_open}: many themes call {@see wp_footer} inside <footer>; ancestors with
		 * transform/filter create a containing block so position:fixed sticks to the footer instead of the viewport.
		 */
		add_action( 'wp_body_open', array( self::class, 'render_placeholder' ), 5 );
		add_action( 'wp_footer', array( self::class, 'render_placeholder' ), 50 );

		/**
		 * Fires when sticky cart render hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_sticky_render_hooks_registered' );
	}

	/**
	 * Marks the document when the sticky bar is active (layout reserve, QA hooks).
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( array $classes ) {
		if ( StickyCartVisibility::should_render_sticky() ) {
			$classes[] = 'mp-scc-sticky-active';
			if ( OptionResolver::get_flag( FeatureFlagsDefaults::KEY_STICKY_TRISTATE_ENABLED, false ) ) {
				$classes[] = 'mp-scc-sticky-layout-tristate';
				$preset     = OptionResolver::get_by_path( OptionResolver::get_settings(), 'sticky_cart.tristate_mobile_layout_preset', 'right_docked' );
				$preset     = is_string( $preset ) ? $preset : 'right_docked';
				if ( ! in_array( $preset, array( 'right_docked', 'full_bottom' ), true ) ) {
					$preset = 'right_docked';
				}
				$classes[] = 'mp-scc-tristate-preset--' . str_replace( '_', '-', $preset );
			}
		}
		return $classes;
	}

	/**
	 * Stub output location; replaced by {@see StickyCartRendererInterface} implementation.
	 */
	public static function render_placeholder() {
		if ( self::$sticky_root_printed ) {
			return;
		}
		self::$sticky_root_printed = true;

		/**
		 * Fires where the sticky cart root should be printed.
		 */
		do_action( 'mp_sticky_custom_cart_render_sticky_cart' );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
