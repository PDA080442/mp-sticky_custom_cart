<?php
/**
 * Emits :root CSS custom properties from saved settings.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\Config\CssVariablesContract;
use MpStickyCustomCart\Core\Config\FeatureFlagsDefaults;
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
		add_action( 'wp_enqueue_scripts', array( $this, 'register_runtime_style_handle' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_runtime_variables' ), 25 );
		/**
		 * Duplicate `--mp-scc-catalog-cart-icon-*` on the real asset handle so variables survive
		 * optimizers / edge cases where the src-less `mp-scc-runtime-vars` inline is dropped.
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_catalog_cart_icon_vars_on_main_stylesheet' ), 27 );
		/** Advanced user overrides for tri-state A/B/C styles (admin textarea CSS). */
		add_action( 'wp_footer', array( $this, 'print_footer_tristate_custom_css' ), 997 );
		/** Tri-state mobile preset (phase 17.6 / 20): breakpoint is numeric → safe injected @media. */
		add_action( 'wp_footer', array( $this, 'print_footer_tristate_responsive_layer_css' ), 998 );
		/** Adaptive max-heights for tri-state panels B/C on mobile/tablet viewports. */
		add_action( 'wp_footer', array( $this, 'print_footer_tristate_adaptive_heights_css' ), 998 );
		/** Last-resort :root + rules at end of body (after theme CSS / late bundles). */
		add_action( 'wp_footer', array( $this, 'print_footer_catalog_cart_icon_late_style' ), 999 );
	}

	/**
	 * Narrow-viewport overrides when `tristate_mobile_layout_preset` is `full_bottom` (dp §17.6 / §20.2).
	 */
	public function print_footer_tristate_responsive_layer_css() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		if ( ! wp_style_is( FrontendAssetsHooks::HANDLE_STYLE, 'enqueued' ) && ! wp_style_is( FrontendAssetsHooks::HANDLE_STYLE, 'done' ) ) {
			return;
		}
		if ( ! OptionResolver::get_flag( FeatureFlagsDefaults::KEY_STICKY_TRISTATE_ENABLED, false ) ) {
			return;
		}
		$settings = OptionResolver::get_settings();
		$preset   = OptionResolver::get_by_path( $settings, 'sticky_cart.tristate_mobile_layout_preset', 'right_docked' );
		if ( 'full_bottom' !== $preset ) {
			return;
		}
		$bp = (int) OptionResolver::get_by_path( $settings, 'sticky_cart.tristate_mobile_breakpoint_max_px', 782 );
		$bp = max( 480, min( 900, $bp ) );

		$sel_stack = 'body.mp-scc-sticky-layout-tristate.mp-scc-tristate-preset--full-bottom .mp-scc-sticky-bar.mp-scc-sticky--tristate .mp-scc-sticky-stack';
		$sel_inner = 'body.mp-scc-sticky-layout-tristate.mp-scc-tristate-preset--full-bottom .mp-scc-sticky-bar.mp-scc-sticky--tristate .mp-scc-sticky-inner';
		$sel_b     = 'body.mp-scc-sticky-layout-tristate.mp-scc-tristate-preset--full-bottom .mp-scc-sticky-bar.mp-scc-sticky--tristate .mp-scc-shell-panel-b';
		$sel_c     = 'body.mp-scc-sticky-layout-tristate.mp-scc-tristate-preset--full-bottom .mp-scc-sticky-bar.mp-scc-sticky--tristate .mp-scc-drawer.mp-scc-drawer--tristate-c';

		$css  = '@media (max-width: ' . (string) $bp . "px) {\n";
		$css .= $sel_stack . "{align-items:stretch!important;width:100%!important;max-width:100%!important;}\n";
		$css .= $sel_inner . "{justify-content:center!important;width:100%!important;}\n";
		$css .= $sel_b . "{left:max(var(--mp-scc-tristate-dock-inset-left,32px),env(safe-area-inset-left,0))!important;right:max(var(--mp-scc-tristate-dock-inset-right,32px),env(safe-area-inset-right,0))!important;width:auto!important;max-width:none!important;margin-left:auto!important;margin-right:auto!important;}\n";
		$css .= $sel_c . "{left:max(var(--mp-scc-tristate-dock-inset-left,32px),env(safe-area-inset-left,0))!important;right:max(var(--mp-scc-tristate-dock-inset-right,32px),env(safe-area-inset-right,0))!important;width:auto!important;max-width:none!important;}\n";
		$css .= "}\n";

		echo '<style id="mp-scc-tristate-responsive" type="text/css">' . "\n";
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- numeric breakpoint + fixed selectors.
		echo $css;
		echo '</style>' . "\n";
	}

	/**
	 * Adaptive max-heights for tri-state panels B (summary) and C (drawer) on mobile/tablet.
	 *
	 * Reads:
	 *   sticky_cart.tristate_panel_b_max_height_mobile_px (0 = inherit desktop)
	 *   sticky_cart.tristate_panel_b_max_height_tablet_px (0 = inherit desktop)
	 *   sticky_cart.tristate_panel_c_height_mobile_px     (0 = inherit desktop)
	 *   sticky_cart.tristate_panel_c_height_tablet_px     (0 = inherit desktop)
	 *   sticky_cart.tristate_mobile_breakpoint_max_px     (mobile upper bound, px)
	 *   sticky_cart.tristate_tablet_breakpoint_max_px     (tablet upper bound, px)
	 *
	 * Emits `:root { --mp-scc-tristate-panel-b-max-height: …; --mp-scc-tristate-panel-c-height: …; }`
	 * inside @media blocks, so the existing CSS — which consumes these tokens for
	 * `height/min-height/max-height` on `.mp-scc-shell-panel-b` and the drawer C surface —
	 * picks up the override automatically on the matching viewport.
	 *
	 * Applies to any preset (right_docked / full_bottom) and independently of tri-state enabled flag,
	 * because the tokens are no-ops when tri-state markup is absent.
	 */
	public function print_footer_tristate_adaptive_heights_css() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		if ( ! wp_style_is( FrontendAssetsHooks::HANDLE_STYLE, 'enqueued' ) && ! wp_style_is( FrontendAssetsHooks::HANDLE_STYLE, 'done' ) ) {
			return;
		}

		$settings = OptionResolver::get_settings();

		$b_mobile = (int) OptionResolver::get_by_path( $settings, 'sticky_cart.tristate_panel_b_max_height_mobile_px', 0 );
		$b_tablet = (int) OptionResolver::get_by_path( $settings, 'sticky_cart.tristate_panel_b_max_height_tablet_px', 0 );
		$c_mobile = (int) OptionResolver::get_by_path( $settings, 'sticky_cart.tristate_panel_c_height_mobile_px', 0 );
		$c_tablet = (int) OptionResolver::get_by_path( $settings, 'sticky_cart.tristate_panel_c_height_tablet_px', 0 );

		$mobile_bp = (int) OptionResolver::get_by_path( $settings, 'sticky_cart.tristate_mobile_breakpoint_max_px', 782 );
		$tablet_bp = (int) OptionResolver::get_by_path( $settings, 'sticky_cart.tristate_tablet_breakpoint_max_px', 1024 );
		$mobile_bp = max( 480, min( 900, $mobile_bp ) );
		$tablet_bp = max( 768, min( 1440, $tablet_bp ) );
		if ( $tablet_bp <= $mobile_bp ) {
			$tablet_bp = $mobile_bp + 1;
		}

		$has_mobile = ( $b_mobile > 0 ) || ( $c_mobile > 0 );
		$has_tablet = ( $b_tablet > 0 ) || ( $c_tablet > 0 );
		if ( ! $has_mobile && ! $has_tablet ) {
			return;
		}

		$blocks = '';

		if ( $has_mobile ) {
			$decls = '';
			if ( $b_mobile > 0 ) {
				$decls .= '--mp-scc-tristate-panel-b-max-height:' . (int) $b_mobile . 'px;';
			}
			if ( $c_mobile > 0 ) {
				$decls .= '--mp-scc-tristate-panel-c-height:' . (int) $c_mobile . 'px;';
			}
			$blocks .= '@media (max-width: ' . (int) $mobile_bp . 'px){:root{' . $decls . '}}' . "\n";
		}

		if ( $has_tablet ) {
			$decls = '';
			if ( $b_tablet > 0 ) {
				$decls .= '--mp-scc-tristate-panel-b-max-height:' . (int) $b_tablet . 'px;';
			}
			if ( $c_tablet > 0 ) {
				$decls .= '--mp-scc-tristate-panel-c-height:' . (int) $c_tablet . 'px;';
			}
			$blocks .= '@media (min-width: ' . ( (int) $mobile_bp + 1 ) . 'px) and (max-width: ' . (int) $tablet_bp . 'px){:root{' . $decls . '}}' . "\n";
		}

		echo '<style id="mp-scc-tristate-adaptive-heights" type="text/css">' . "\n";
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- numeric breakpoints + fixed declarations.
		echo $blocks;
		echo '</style>' . "\n";
	}

	/**
	 * Prints advanced user CSS overrides for tri-state states A/B/C.
	 * Global CSS is printed as-is; state CSS fields are wrapped by state selectors.
	 */
	public function print_footer_tristate_custom_css() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		if ( ! wp_style_is( FrontendAssetsHooks::HANDLE_STYLE, 'enqueued' ) && ! wp_style_is( FrontendAssetsHooks::HANDLE_STYLE, 'done' ) ) {
			return;
		}
		if ( ! OptionResolver::get_flag( FeatureFlagsDefaults::KEY_STICKY_TRISTATE_ENABLED, false ) ) {
			return;
		}
		$settings = OptionResolver::get_settings();
		$global   = trim( (string) OptionResolver::get_by_path( $settings, 'sticky_cart.tristate_custom_css_global', '' ) );
		$state_a  = trim( (string) OptionResolver::get_by_path( $settings, 'sticky_cart.tristate_custom_css_state_a', '' ) );
		$state_b  = trim( (string) OptionResolver::get_by_path( $settings, 'sticky_cart.tristate_custom_css_state_b', '' ) );
		$state_c  = trim( (string) OptionResolver::get_by_path( $settings, 'sticky_cart.tristate_custom_css_state_c', '' ) );
		if ( '' === $global && '' === $state_a && '' === $state_b && '' === $state_c ) {
			return;
		}

		$css = '';
		if ( '' !== $global ) {
			$css .= "/* mp-scc tristate custom global */\n" . $global . "\n";
		}
		if ( '' !== $state_a ) {
			$css .= ".mp-scc-sticky-bar.mp-scc-sticky--tristate[data-mp-scc-shell-state=\"A\"]{\n" . $state_a . "\n}\n";
		}
		if ( '' !== $state_b ) {
			$css .= ".mp-scc-sticky-bar.mp-scc-sticky--tristate[data-mp-scc-shell-state=\"B\"]{\n" . $state_b . "\n}\n";
		}
		if ( '' !== $state_c ) {
			$css .= ".mp-scc-sticky-bar.mp-scc-sticky--tristate[data-mp-scc-shell-state=\"C\"]{\n" . $state_c . "\n}\n";
		}
		if ( '' === trim( $css ) ) {
			return;
		}

		echo '<style id="mp-scc-tristate-custom-css" type="text/css">' . "\n";
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- settings sanitizer strips tags; CSS is intended raw text.
		echo $css;
		echo '</style>' . "\n";
	}

	/**
	 * Echo `<style>` in footer so catalog icon tokens apply even when head CSS is stripped or reordered.
	 */
	public function print_footer_catalog_cart_icon_late_style() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		if ( ! wp_style_is( FrontendAssetsHooks::HANDLE_STYLE, 'enqueued' ) && ! wp_style_is( FrontendAssetsHooks::HANDLE_STYLE, 'done' ) ) {
			return;
		}
		$props  = $this->get_css_custom_properties();
		$prefix = CssVariablesContract::PREFIX . 'catalog-cart-icon-';
		$parts  = array();
		foreach ( $props as $name => $value ) {
			if ( ! is_string( $name ) || 0 !== strpos( $name, $prefix ) ) {
				continue;
			}
			if ( ! is_string( $value ) ) {
				continue;
			}
			$parts[] = $name . ':' . $value . ';';
		}
		if ( array() === $parts ) {
			return;
		}
		$root = CssVariablesContract::DEFAULT_ROOT_SELECTOR . '{' . implode( '', $parts ) . '}';
		$sel  = 'button.mp-scc-catalog-cart-icon-btn[data-mp-scc-cart-icon="1"]';
		$btn  = $sel . '{color:var(--mp-scc-catalog-cart-icon-color)!important;background-color:var(--mp-scc-catalog-cart-icon-background)!important;background-image:none!important}';
		$hov  = $sel . ':hover{background-color:var(--mp-scc-catalog-cart-icon-background-hover)!important;color:var(--mp-scc-catalog-cart-icon-color-hover)!important;background-image:none!important}';

		echo '<style id="mp-scc-catalog-cart-icon-late" type="text/css">' . "\n";
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline CSS from plugin contract (sanitized upstream).
		echo $root . $btn . $hov;
		echo "\n" . '</style>' . "\n";
	}

	/**
	 * Register handle early so other styles can list it as a dependency before priority 25.
	 */
	public function register_runtime_style_handle() {
		wp_register_style(
			self::STYLE_HANDLE,
			false,
			array(),
			MP_STICKY_CUSTOM_CART_ASSET_VERSION
		);
	}

	/**
	 * Enqueue runtime vars and attach inline :root block.
	 */
	public function enqueue_runtime_variables() {
		wp_enqueue_style( self::STYLE_HANDLE );
		wp_add_inline_style( self::STYLE_HANDLE, $this->get_inline_css_block( CssVariablesContract::DEFAULT_ROOT_SELECTOR ) );
	}

	/**
	 * Re-emits catalog cart icon variables after `frontend.css` (compact :root fragment).
	 */
	public function enqueue_catalog_cart_icon_vars_on_main_stylesheet() {
		if ( ! wp_style_is( FrontendAssetsHooks::HANDLE_STYLE, 'enqueued' ) ) {
			return;
		}
		$props  = $this->get_css_custom_properties();
		$prefix = CssVariablesContract::PREFIX . 'catalog-cart-icon-';
		$parts  = array();
		foreach ( $props as $name => $value ) {
			if ( ! is_string( $name ) || 0 !== strpos( $name, $prefix ) ) {
				continue;
			}
			$parts[] = $name . ':' . $value . ';';
		}
		if ( array() === $parts ) {
			return;
		}
		$css = CssVariablesContract::DEFAULT_ROOT_SELECTOR . '{' . implode( '', $parts ) . '}';

		/**
		 * Filters the compact :root block appended to `mp-scc-frontend` (catalog cart icon vars only).
		 *
		 * @param string               $css   CSS fragment.
		 * @param array<string, string> $props Full custom properties map (same as runtime).
		 */
		$css = (string) apply_filters( 'mp_sticky_custom_cart_catalog_cart_icon_runtime_css_fallback', $css, $props );

		wp_add_inline_style( FrontendAssetsHooks::HANDLE_STYLE, $css );
	}
}
