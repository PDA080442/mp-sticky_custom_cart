<?php
/**
 * Server endpoints and hooks for AJAX/JS error diagnostics.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Placeholders for client/server log ingestion (implemented in diagnostics phase).
 */
final class ErrorLoggingHooks {

	public static function register() {
		add_action( 'wp_ajax_mp_scc_log_client_error', array( self::class, 'handle_client_log' ) );
		add_action( 'wp_ajax_nopriv_mp_scc_log_client_error', array( self::class, 'handle_client_log' ) );

		/**
		 * Fires when error logging hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_error_logging_hooks_registered' );
	}

	/**
	 * Stub: accept client reports later (nonce + sanitization).
	 */
	public static function handle_client_log() {
		wp_die( '', '', array( 'response' => 403 ) );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
