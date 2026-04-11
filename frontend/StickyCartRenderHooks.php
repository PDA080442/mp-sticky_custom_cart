<?php
/**
 * Output hooks for the sticky cart markup.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Registers footer/body hooks for the sticky shell (markup added in renderer task).
 */
final class StickyCartRenderHooks {

	public static function register() {
		add_action( 'wp_footer', array( self::class, 'render_placeholder' ), 50 );

		/**
		 * Fires when sticky cart render hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_sticky_render_hooks_registered' );
	}

	/**
	 * Stub output location; replaced by {@see StickyCartRendererInterface} implementation.
	 */
	public static function render_placeholder() {
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
