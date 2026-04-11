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
