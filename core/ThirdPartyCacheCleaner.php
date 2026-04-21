<?php
/**
 * Purges known third-party page/minify caches when the plugin is upgraded.
 *
 * Many hosts stack WP Rocket / LiteSpeed / Autoptimize / WP Fastest Cache on top of WordPress,
 * and those minifiers key their cache on their own `filemtime()` at build time. After a plugin
 * update the cached (often combined & minified) `frontend.css` / `frontend.js` served to visitors
 * is stale until someone hits "Clear cache" in the admin. We forcibly purge those caches right
 * after our files land on disk so shop owners don't have to babysit the update.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Best-effort purge across popular cache / minify plugins after plugin upgrade.
 */
final class ThirdPartyCacheCleaner {

	/**
	 * Option that stores the last version that triggered a purge — used for the admin_init guard.
	 */
	private const OPTION_LAST_PURGED_FOR = Constants::STORAGE_PREFIX . 'last_asset_purge_for_version';

	/**
	 * Wire hooks.
	 */
	public static function register() {
		add_action( 'upgrader_process_complete', array( self::class, 'on_upgrader_complete' ), 10, 2 );
		add_action( 'admin_init', array( self::class, 'maybe_purge_on_version_change' ), 20 );

		/**
		 * Fires when third-party cache cleaner hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_cache_cleaner_registered' );
	}

	/**
	 * Fires once per WP Updates run. Only reacts to this plugin being installed/updated.
	 *
	 * @param \WP_Upgrader $upgrader Upgrader instance (unused, kept for hook signature).
	 * @param array        $hook_extra Context from core describing what was updated.
	 */
	public static function on_upgrader_complete( $upgrader, $hook_extra ) {
		unset( $upgrader );
		if ( ! is_array( $hook_extra ) ) {
			return;
		}
		if ( empty( $hook_extra['type'] ) || 'plugin' !== $hook_extra['type'] ) {
			return;
		}

		$slug     = dirname( MP_STICKY_CUSTOM_CART_BASENAME );
		$basename = MP_STICKY_CUSTOM_CART_BASENAME;

		$touched = array();
		if ( ! empty( $hook_extra['plugins'] ) && is_array( $hook_extra['plugins'] ) ) {
			$touched = $hook_extra['plugins'];
		} elseif ( ! empty( $hook_extra['plugin'] ) ) {
			$touched = array( $hook_extra['plugin'] );
		}

		$hit = false;
		foreach ( $touched as $entry ) {
			if ( ! is_string( $entry ) ) {
				continue;
			}
			if ( $entry === $basename || 0 === strpos( $entry, $slug . '/' ) ) {
				$hit = true;
				break;
			}
		}

		if ( ! $hit && empty( $hook_extra['plugins'] ) && empty( $hook_extra['plugin'] ) ) {
			// Bulk install/update without an explicit list — be conservative and purge.
			$hit = true;
		}

		if ( ! $hit ) {
			return;
		}

		self::purge_all( 'upgrader_process_complete' );
		update_option( self::OPTION_LAST_PURGED_FOR, MP_STICKY_CUSTOM_CART_VERSION, false );
	}

	/**
	 * Admin fallback: when the installed plugin version advances but no upgrader event fired
	 * (manual FTP / git deploy / staging sync), purge once per new version on first admin pageload.
	 */
	public static function maybe_purge_on_version_change() {
		if ( ! is_admin() ) {
			return;
		}

		$last = get_option( self::OPTION_LAST_PURGED_FOR, '' );
		if ( is_string( $last ) && '' !== $last && version_compare( $last, MP_STICKY_CUSTOM_CART_VERSION, '>=' ) ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) && ! wp_doing_cron() ) {
			return;
		}

		self::purge_all( 'admin_init_version_change' );
		update_option( self::OPTION_LAST_PURGED_FOR, MP_STICKY_CUSTOM_CART_VERSION, false );
	}

	/**
	 * Invoke every known purge entry point. Each branch is guarded; the handful of plugins
	 * actually installed on the site will respond, the rest are no-ops.
	 *
	 * @param string $trigger Context label for the `mp_sticky_custom_cart_assets_purged` hook.
	 */
	public static function purge_all( $trigger = 'manual' ) {
		// WP Rocket.
		if ( function_exists( 'rocket_clean_minify' ) ) {
			rocket_clean_minify();
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'rocket_clean_cache_busting' ) ) {
			rocket_clean_cache_busting();
		}

		// LiteSpeed Cache.
		if ( function_exists( 'do_action' ) ) {
			do_action( 'litespeed_purge_all' );
			do_action( 'litespeed_purge_css' );
			do_action( 'litespeed_purge_js' );
		}

		// Autoptimize.
		if ( class_exists( '\\autoptimizeCache' ) && method_exists( '\\autoptimizeCache', 'clearall' ) ) {
			\autoptimizeCache::clearall();
		}

		// WP Fastest Cache.
		if ( function_exists( 'do_action' ) ) {
			do_action( 'wpfc_clear_all_cache', true );
		}
		if ( isset( $GLOBALS['wp_fastest_cache'] ) && is_object( $GLOBALS['wp_fastest_cache'] ) && method_exists( $GLOBALS['wp_fastest_cache'], 'deleteCache' ) ) {
			$GLOBALS['wp_fastest_cache']->deleteCache( true );
		}

		// W3 Total Cache.
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		} elseif ( function_exists( 'w3tc_pgcache_flush' ) ) {
			w3tc_pgcache_flush();
			if ( function_exists( 'w3tc_minify_flush' ) ) {
				w3tc_minify_flush();
			}
		}

		// SiteGround Optimizer.
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}

		// WP Super Cache.
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		} elseif ( function_exists( 'wp_cache_clean_cache' ) ) {
			// phpcs:ignore
			global $file_prefix;
			wp_cache_clean_cache( $file_prefix, true );
		}

		// Cloudflare plugin and cache-control proxies listening on this hook.
		if ( function_exists( 'do_action' ) ) {
			do_action( 'cloudflare_purge_everything' );
		}

		// Hosting-level object cache (don't touch by default — it would nuke every cart session).
		// Keep this intentionally narrow: we only care about CSS/JS minifiers that build from disk.

		/**
		 * Fires after the plugin asks adjacent caches to purge.
		 *
		 * @param string $trigger Which entry point invoked the purge.
		 */
		do_action( 'mp_sticky_custom_cart_assets_purged', $trigger );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
