<?php
/**
 * Emits :root CSS custom properties from saved settings.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\Config\CssVariablesContract;
use MpStickyCustomCart\Core\Contracts\DynamicStylesProviderInterface;
use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * @implements DynamicStylesProviderInterface
 */
final class DynamicStylesProvider implements DynamicStylesProviderInterface {

	public const STYLE_HANDLE = 'mp-scc-runtime-vars';

	/**
	 * @return array<string, string>
	 */
	public function get_css_custom_properties() {
		$props = CssVariablesContract::build_properties( OptionResolver::get_settings() );

		/**
		 * Filters the generated CSS custom property map before inline output.
		 *
		 * @param array<string, string> $props Variable name => value.
		 */
		return apply_filters( 'mp_sticky_custom_cart_css_custom_properties', $props );
	}

	/**
	 * @param string $selector Root selector (e.g. `:root`).
	 */
	public function get_inline_css_block( $selector = ':root' ) {
		$selector = is_string( $selector ) && '' !== $selector ? $selector : CssVariablesContract::DEFAULT_ROOT_SELECTOR;
		$props    = $this->get_css_custom_properties();
		$parts    = array();
		foreach ( $props as $name => $value ) {
			$parts[] = $name . ':' . $value . ';';
		}

		$css = $selector . '{' . implode( '', $parts ) . '}';

		/**
		 * Filters the full CSS block for runtime variables.
		 *
		 * @param string $css      Generated CSS.
		 * @param string $selector Root selector used.
		 */
		return (string) apply_filters( 'mp_sticky_custom_cart_runtime_css_inline', $css, $selector );
	}

	public function register_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_runtime_variables' ), 25 );
	}

	/**
	 * Register a handleless style and attach inline :root variables.
	 */
	public function enqueue_runtime_variables() {
		wp_register_style(
			self::STYLE_HANDLE,
			false,
			array(),
			MP_STICKY_CUSTOM_CART_ASSET_VERSION
		);
		wp_enqueue_style( self::STYLE_HANDLE );
		wp_add_inline_style( self::STYLE_HANDLE, $this->get_inline_css_block( CssVariablesContract::DEFAULT_ROOT_SELECTOR ) );
	}
}
