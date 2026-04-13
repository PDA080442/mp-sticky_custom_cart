<?php
/**
 * Plugin slug, namespace, option names, and storage key helpers.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Central registry for public identifiers and DB key naming.
 */
final class Constants {

	/**
	 * Plugin slug (directory name, screen ids, REST route segment).
	 */
	public const SLUG = 'mp-sticky-custom-cart';

	/**
	 * Text domain for translations.
	 */
	public const TEXT_DOMAIN = 'mp-sticky-custom-cart';

	/**
	 * Root PHP namespace (sub-namespaces: Core, Admin, Frontend, Integrations).
	 */
	public const PHP_NAMESPACE = 'MpStickyCustomCart';

	/**
	 * Unified prefix for wp_options keys, user/post meta keys, and transients.
	 * Keeps DB keys short and collision-resistant.
	 */
	public const STORAGE_PREFIX = 'mp_scc_';

	/**
	 * Stored schema / migration marker (semver string, aligned with plugin releases).
	 */
	public const OPTION_DB_VERSION = self::STORAGE_PREFIX . 'db_version';

	/**
	 * Main plugin settings stored in wp_options (typically a single array).
	 */
	public const OPTION_SETTINGS = self::STORAGE_PREFIX . 'settings';

	/**
	 * Feature flags storage (wp_options).
	 */
	public const OPTION_FEATURE_FLAGS = self::STORAGE_PREFIX . 'feature_flags';

	/**
	 * AJAX / JS error log payload (wp_options or migrated later).
	 */
	public const OPTION_ERROR_LOG = self::STORAGE_PREFIX . 'error_log';

	/**
	 * Running count of cart-page requests that had {@see $_GET['add-to-cart']} before redirect (optional tracking).
	 */
	public const OPTION_EXTERNAL_CART_LINK_HITS = self::STORAGE_PREFIX . 'external_cart_link_hits';

	/**
	 * Nonce action for storefront AJAX ({@see check_ajax_referer} / {@see wp_verify_nonce}).
	 */
	public const AJAX_NONCE_ACTION = 'mp_scc_frontend';

	/**
	 * Registered {@see wp_ajax_*} action: cart snapshot for sticky UI.
	 */
	public const AJAX_ACTION_CART_SNAPSHOT = 'mp_scc_cart_snapshot';

	/**
	 * Registered {@see wp_ajax_*} action: set line quantity (debounced from storefront JS).
	 */
	public const AJAX_ACTION_SET_LINE_QUANTITY = 'mp_scc_set_line_quantity';

	/**
	 * Registered {@see wp_ajax_*} action: add simple product (catalog image click).
	 */
	public const AJAX_ACTION_ADD_SIMPLE_PRODUCT = 'mp_scc_add_simple_product';

	/**
	 * Registered {@see wp_ajax_*} action: client-side diagnostics (e.g. out-of-stock click).
	 */
	public const AJAX_ACTION_LOG_CLIENT_EVENT = 'mp_scc_log_client_error';

	/**
	 * Registered {@see wp_ajax_*} action: empty cart from sticky UI.
	 */
	public const AJAX_ACTION_CLEAR_CART = 'mp_scc_clear_cart';

	/**
	 * Registered {@see wp_ajax_*} action: remove one cart line from sticky drawer.
	 */
	public const AJAX_ACTION_REMOVE_CART_LINE = 'mp_scc_remove_cart_line';

	/**
	 * Admin-only: JSON list of {@see Constants::OPTION_ERROR_LOG} entries.
	 */
	public const AJAX_ACTION_ADMIN_GET_ERROR_LOGS = 'mp_scc_admin_get_error_logs';

	/**
	 * Admin-only: download filtered logs as CSV or JSON.
	 */
	public const AJAX_ACTION_ADMIN_EXPORT_ERROR_LOGS = 'mp_scc_admin_export_error_logs';

	/**
	 * Capability for diagnostics log UI, export, and purge (granted to administrators on install).
	 */
	public const CAPABILITY_MANAGE_DIAGNOSTICS = 'manage_mp_scc_diagnostics';

	/**
	 * One-time flag: diagnostics capability granted to administrator role.
	 */
	public const OPTION_DIAG_CAP_BOOT = self::STORAGE_PREFIX . 'diag_cap_boot';

	/**
	 * Nonce action for admin error log AJAX / purge.
	 */
	public const NONCE_ADMIN_ERROR_LOG = 'mp_scc_admin_error_log';

	/**
	 * Admin-post: purge error log option.
	 */
	public const ADMIN_POST_PURGE_ERROR_LOG = 'mp_scc_purge_error_log';

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}

	/**
	 * Additional top-level option name under the unified prefix.
	 *
	 * @param string $suffix Logical suffix (e.g. `cache_version`).
	 * @return string
	 */
	public static function option_key( $suffix ) {
		return self::STORAGE_PREFIX . sanitize_key( (string) $suffix );
	}

	/**
	 * Post or user meta key.
	 *
	 * @param string $name Logical name without prefix.
	 * @return string
	 */
	public static function meta_key( $name ) {
		return self::STORAGE_PREFIX . 'm_' . sanitize_key( (string) $name );
	}

	/**
	 * Transient key (max 172 chars including prefix; keep $name short).
	 *
	 * @param string $name Logical name without prefix.
	 * @return string
	 */
	public static function transient_key( $name ) {
		return self::STORAGE_PREFIX . 't_' . sanitize_key( (string) $name );
	}
}
