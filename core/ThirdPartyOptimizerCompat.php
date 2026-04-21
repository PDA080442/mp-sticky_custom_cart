<?php
/**
 * Keeps plugin CSS/JS out of third-party optimizer pipelines.
 *
 * Motivation: WP Rocket / LiteSpeed / Autoptimize rebuild combined+minified bundles lazily.
 * After a plugin update the bundle they already cached on disk can stay stale for hours or
 * until someone clicks "Clear cache". The cleanest fix is to keep our `frontend.css` and
 * `frontend.js` out of those pipelines entirely: they're already small, already concat-free,
 * they carry a `?ver=<version>.<filemtime>` query (see {@see PluginPaths::asset_file_version()}),
 * and they must execute early enough to own the sticky cart / catalog hover UI.
 *
 * Shop owners don't need to edit anything in the optimizer UI — the filters below do it for them.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Registers exclusion filters for the most common WP performance/optimizer plugins.
 */
final class ThirdPartyOptimizerCompat {

	/**
	 * Path fragments (relative to site root, query strings dropped) that identify plugin assets.
	 *
	 * Kept as a const so unit snapshots / admin UI can surface the same list.
	 *
	 * @var string[]
	 */
	private const RELATIVE_ASSET_PATHS = array(
		'/wp-content/plugins/mp-sticky_custom_cart/assets/css/frontend.css',
		'/wp-content/plugins/mp-sticky_custom_cart/assets/js/frontend.js',
		'/wp-content/plugins/mp-sticky_custom_cart/assets/js/cart-ui-shell-state.js',
	);

	/**
	 * Regex fragment matching any JS/CSS under the plugin's `assets/` directory.
	 */
	private const ASSETS_WILDCARD = '/wp-content/plugins/mp-sticky_custom_cart/assets/(.*)';

	/**
	 * Wire filters.
	 */
	public static function register() {
		// WP Rocket: minify + combine (CSS/JS) and delay JS execution.
		add_filter( 'rocket_exclude_css', array( self::class, 'filter_wp_rocket_exclude_css' ) );
		add_filter( 'rocket_exclude_js', array( self::class, 'filter_wp_rocket_exclude_js' ) );
		add_filter( 'rocket_delay_js_exclusions', array( self::class, 'filter_wp_rocket_delay_js' ) );
		add_filter( 'rocket_minify_excluded_external_js', array( self::class, 'filter_wp_rocket_external_js' ) );
		add_filter( 'rocket_rucss_excluded_selectors', array( self::class, 'filter_wp_rocket_rucss_selectors' ) );

		// LiteSpeed Cache.
		add_filter( 'litespeed_optimize_css_excludes', array( self::class, 'filter_litespeed_css_excludes' ) );
		add_filter( 'litespeed_optimize_js_excludes', array( self::class, 'filter_litespeed_js_excludes' ) );
		add_filter( 'litespeed_optm_js_defer_exc', array( self::class, 'filter_litespeed_js_excludes' ) );

		// Autoptimize.
		add_filter( 'autoptimize_filter_css_exclude', array( self::class, 'filter_autoptimize_css' ) );
		add_filter( 'autoptimize_filter_js_exclude', array( self::class, 'filter_autoptimize_js' ) );

		/**
		 * Fires after optimizer-compat filters are registered.
		 */
		do_action( 'mp_sticky_custom_cart_optimizer_compat_registered' );
	}

	/**
	 * Full list of plugin asset paths to keep untouched.
	 *
	 * @return string[]
	 */
	public static function get_excluded_asset_paths() {
		/**
		 * Filters the list of plugin asset paths that third-party optimizers must leave alone.
		 *
		 * @param string[] $paths Site-relative paths (query string already stripped).
		 */
		return (array) apply_filters(
			'mp_sticky_custom_cart_optimizer_excluded_paths',
			self::RELATIVE_ASSET_PATHS
		);
	}

	/**
	 * WP Rocket: minify/combine CSS exclusion. Accepts regex-style entries with `(.*)`.
	 *
	 * @param mixed $excluded Existing exclusions (array or falsy).
	 * @return string[]
	 */
	public static function filter_wp_rocket_exclude_css( $excluded ) {
		return self::append_wildcard( $excluded );
	}

	/**
	 * WP Rocket: minify/combine JS exclusion.
	 *
	 * @param mixed $excluded Existing exclusions.
	 * @return string[]
	 */
	public static function filter_wp_rocket_exclude_js( $excluded ) {
		return self::append_wildcard( $excluded );
	}

	/**
	 * WP Rocket: Delay JavaScript Execution exclusion — sticky cart / catalog hit layers must
	 * run without waiting for user interaction.
	 *
	 * @param mixed $excluded Existing exclusions.
	 * @return string[]
	 */
	public static function filter_wp_rocket_delay_js( $excluded ) {
		return self::append_wildcard( $excluded );
	}

	/**
	 * WP Rocket: external JS minify exclusion (same syntax as `rocket_exclude_js`).
	 *
	 * @param mixed $excluded Existing exclusions.
	 * @return string[]
	 */
	public static function filter_wp_rocket_external_js( $excluded ) {
		return self::append_wildcard( $excluded );
	}

	/**
	 * WP Rocket Remove Unused CSS: keep selectors we ship in CSS alive even without a DOM match,
	 * since they only appear after JS hydrates catalog cards / sticky shell.
	 *
	 * @param mixed $selectors Existing excluded CSS selector patterns.
	 * @return string[]
	 */
	public static function filter_wp_rocket_rucss_selectors( $selectors ) {
		if ( ! is_array( $selectors ) ) {
			$selectors = array();
		}
		$selectors[] = '.mp-scc-';
		$selectors[] = '.mp-scc-sticky-bar';
		$selectors[] = '.mp-scc-catalog-';
		$selectors[] = '[data-mp-scc-';
		return $selectors;
	}

	/**
	 * LiteSpeed: CSS optimizer exclusion list (array or newline-separated string).
	 *
	 * @param mixed $excluded Existing exclusions.
	 * @return string[]|string
	 */
	public static function filter_litespeed_css_excludes( $excluded ) {
		return self::merge_list_like( $excluded, self::get_excluded_asset_paths() );
	}

	/**
	 * LiteSpeed: JS optimizer / defer exclusion list.
	 *
	 * @param mixed $excluded Existing exclusions.
	 * @return string[]|string
	 */
	public static function filter_litespeed_js_excludes( $excluded ) {
		return self::merge_list_like( $excluded, self::get_excluded_asset_paths() );
	}

	/**
	 * Autoptimize: CSS exclusions — Autoptimize expects a comma-separated string of substrings.
	 *
	 * @param mixed $excluded Existing CSV string.
	 * @return string
	 */
	public static function filter_autoptimize_css( $excluded ) {
		return self::append_csv( $excluded, self::get_excluded_asset_paths() );
	}

	/**
	 * Autoptimize: JS exclusions.
	 *
	 * @param mixed $excluded Existing CSV string.
	 * @return string
	 */
	public static function filter_autoptimize_js( $excluded ) {
		return self::append_csv( $excluded, self::get_excluded_asset_paths() );
	}

	/**
	 * Append the WP Rocket-style wildcard entry once.
	 *
	 * @param mixed $excluded Existing exclusions.
	 * @return string[]
	 */
	private static function append_wildcard( $excluded ) {
		if ( ! is_array( $excluded ) ) {
			$excluded = array();
		}
		if ( ! in_array( self::ASSETS_WILDCARD, $excluded, true ) ) {
			$excluded[] = self::ASSETS_WILDCARD;
		}
		return $excluded;
	}

	/**
	 * Merge concrete paths into a list-or-string optimizer setting without duplicates.
	 *
	 * @param mixed    $excluded Existing value (array or newline string).
	 * @param string[] $paths    Paths to ensure are present.
	 * @return string[]|string
	 */
	private static function merge_list_like( $excluded, $paths ) {
		if ( is_array( $excluded ) ) {
			foreach ( $paths as $p ) {
				if ( ! in_array( $p, $excluded, true ) ) {
					$excluded[] = $p;
				}
			}
			return $excluded;
		}

		$text = is_string( $excluded ) ? $excluded : '';
		foreach ( $paths as $p ) {
			if ( false === strpos( $text, $p ) ) {
				$text .= ( '' === $text ? '' : "\n" ) . $p;
			}
		}
		return $text;
	}

	/**
	 * Append to Autoptimize's CSV "exclude contains" string.
	 *
	 * @param mixed    $excluded Existing CSV.
	 * @param string[] $paths    Paths to ensure are present.
	 * @return string
	 */
	private static function append_csv( $excluded, $paths ) {
		$csv = is_string( $excluded ) ? $excluded : '';
		foreach ( $paths as $p ) {
			if ( false === strpos( $csv, $p ) ) {
				$csv .= ( '' === $csv ? '' : ', ' ) . $p;
			}
		}
		return $csv;
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
