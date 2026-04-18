<?php
/**
 * Path and URL helpers for the plugin root.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves filesystem paths and public URLs under the plugin directory.
 */
final class PluginPaths {

	/**
	 * Absolute path inside the plugin directory.
	 *
	 * @param string $relative Path relative to the plugin root (e.g. `assets/js/frontend.js`).
	 * @return string
	 */
	public static function path( $relative = '' ) {
		$root = untrailingslashit( MP_STICKY_CUSTOM_CART_PATH );
		$rel  = ltrim( str_replace( '\\', '/', (string) $relative ), '/' );

		if ( '' === $rel ) {
			return $root;
		}

		return $root . '/' . $rel;
	}

	/**
	 * URL inside the plugin directory.
	 *
	 * @param string $relative Path relative to the plugin root.
	 * @return string
	 */
	public static function url( $relative = '' ) {
		$root = untrailingslashit( MP_STICKY_CUSTOM_CART_URL );
		$rel  = ltrim( str_replace( '\\', '/', (string) $relative ), '/' );

		if ( '' === $rel ) {
			return $root;
		}

		return $root . '/' . $rel;
	}

	/**
	 * Version string for wp_enqueue_* (styles/scripts).
	 *
	 * @return string
	 */
	public static function asset_version() {
		/**
		 * Filters the asset version string used for cache busting.
		 *
		 * @param string $version Default asset version.
		 */
		return (string) apply_filters( 'mp_sticky_custom_cart_asset_version', MP_STICKY_CUSTOM_CART_ASSET_VERSION );
	}
}
