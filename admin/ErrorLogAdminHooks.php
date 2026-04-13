<?php
/**
 * Admin AJAX: read error log as JSON (manage_options).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Admin;

use MpStickyCustomCart\Core\Constants;
use MpStickyCustomCart\Core\ErrorLogService;

defined( 'ABSPATH' ) || exit;

/**
 * Registers {@see Constants::AJAX_ACTION_ADMIN_GET_ERROR_LOGS}.
 */
final class ErrorLogAdminHooks {

	public static function register() {
		add_action( 'wp_ajax_' . Constants::AJAX_ACTION_ADMIN_GET_ERROR_LOGS, array( self::class, 'handle_get_logs' ) );

		/**
		 * Fires when admin error-log AJAX hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_error_log_admin_hooks_registered' );
	}

	public static function handle_get_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Недостаточно прав.', 'mp-sticky-custom-cart' ),
					'code'    => 'forbidden',
				),
				403
			);
		}

		if ( ! check_ajax_referer( Constants::NONCE_ADMIN_ERROR_LOG, '_wpnonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Неверный nonce.', 'mp-sticky-custom-cart' ),
					'code'    => 'invalid_nonce',
				),
				403
			);
		}

		$limit  = isset( $_GET['limit'] ) ? (int) wp_unslash( $_GET['limit'] ) : 100; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$offset = isset( $_GET['offset'] ) ? (int) wp_unslash( $_GET['offset'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$level  = isset( $_GET['level'] ) ? sanitize_key( wp_unslash( $_GET['level'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$args = array(
			'limit'  => $limit,
			'offset' => $offset,
		);
		if ( '' !== $level ) {
			$args['level'] = $level;
		}

		$entries = ErrorLogService::instance()->query( $args );

		wp_send_json_success(
			array(
				'entries' => $entries,
				'limit'   => $limit,
				'offset'  => $offset,
			)
		);
	}

	private function __construct() {
	}
}
