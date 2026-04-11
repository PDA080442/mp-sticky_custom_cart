<?php
/**
 * Emits CSS custom properties derived from saved UI settings.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Bridges {@see \MpStickyCustomCart\Core\Config\CssVariablesContract} to enqueue/hooks.
 */
interface DynamicStylesProviderInterface {

	/**
	 * `--var-name` => value strings for inline CSS.
	 *
	 * @return array<string, string>
	 */
	public function get_css_custom_properties();

	/**
	 * Full `:root { ... }` or scoped block for `wp_add_inline_style` / footer.
	 *
	 * @param string $selector Root selector (e.g. `:root` or `.mp-scc-root`).
	 */
	public function get_inline_css_block( $selector = ':root' );

	/**
	 * Register hook to print or attach inline styles on the front.
	 */
	public function register_hooks();
}
