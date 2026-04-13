<?php
/**
 * Admin AJAX: read/export error log (diagnostics capability).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Admin;

use MpStickyCustomCart\Core\Constants;
use MpStickyCustomCart\Core\ErrorLogService;

defined( 'ABSPATH' ) || exit;

/**
 * Registers {@see Constants::AJAX_ACTION_ADMIN_GET_ERROR_LOGS} and export action.
 */
final class ErrorLogAdminHooks {

	public static function register() {
		add_action( 'wp_ajax_' . Constants::AJAX_ACTION_ADMIN_GET_ERROR_LOGS, array( self::class, 'handle_get_logs' ) );
		add_action( 'wp_ajax_' . Constants::AJAX_ACTION_ADMIN_EXPORT_ERROR_LOGS, array( self::class, 'handle_export_logs' ) );

		/**
		 * Fires when admin error-log AJAX hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_error_log_admin_hooks_registered' );
	}

	public static function handle_get_logs() {
		if ( ! DiagnosticsAccess::can_manage() ) {
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

		$args = self::collect_filter_args( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$svc    = ErrorLogService::instance();
		$result = $svc->query_with_meta( $args );

		wp_send_json_success(
			array(
				'entries' => $result['entries'],
				'total'   => $result['total'],
				'limit'   => isset( $args['limit'] ) ? (int) $args['limit'] : 100,
				'offset'  => isset( $args['offset'] ) ? (int) $args['offset'] : 0,
			)
		);
	}

	/**
	 * POST download: CSV or JSON (same filters as list).
	 */
	public static function handle_export_logs() {
		if ( ! DiagnosticsAccess::can_manage() ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'mp-sticky-custom-cart' ), '', array( 'response' => 403 ) );
		}

		check_ajax_referer( Constants::NONCE_ADMIN_ERROR_LOG, '_wpnonce' );

		$format = isset( $_POST['format'] ) ? sanitize_key( wp_unslash( $_POST['format'] ) ) : 'csv'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! in_array( $format, array( 'csv', 'json' ), true ) ) {
			$format = 'csv';
		}

		$args   = self::collect_filter_args( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$svc    = ErrorLogService::instance();
		$rows   = $svc->query_for_export( $args );
		$stamp  = gmdate( 'Y-m-d-His' );
		$prefix = 'mp-scc-error-log-' . $stamp;

		nocache_headers();

		if ( 'json' === $format ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $prefix . '.json"' );
			echo wp_json_encode( $rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE );
			exit;
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $prefix . '.csv"' );

		$out = fopen( 'php://output', 'w' );
		if ( false === $out ) {
			exit;
		}

		echo "\xEF\xBB\xBF";

		fputcsv(
			$out,
			array(
				'ts_utc',
				'level',
				'source',
				'endpoint',
				'code',
				'message',
				'user_id',
				'ip_hash',
				'payload_json',
			)
		);

		foreach ( $rows as $e ) {
			if ( ! is_array( $e ) ) {
				continue;
			}
			$ts = isset( $e['ts'] ) ? (int) $e['ts'] : ( isset( $e['t'] ) ? (int) $e['t'] : 0 );
			$lvl = $svc->infer_level( $e );
			fputcsv(
				$out,
				array(
					$ts ? gmdate( 'c', $ts ) : '',
					$lvl,
					isset( $e['source'] ) ? (string) $e['source'] : '',
					isset( $e['endpoint'] ) ? (string) $e['endpoint'] : '',
					isset( $e['code'] ) ? (string) $e['code'] : '',
					isset( $e['message'] ) ? (string) $e['message'] : '',
					isset( $e['user_id'] ) ? (string) (int) $e['user_id'] : '',
					isset( $e['ip_hash'] ) ? (string) $e['ip_hash'] : '',
					isset( $e['payload'] ) && is_array( $e['payload'] ) ? wp_json_encode( $e['payload'] ) : '',
				)
			);
		}

		fclose( $out );
		exit;
	}

	/**
	 * @param array<string, mixed> $req GET or POST fragment.
	 * @return array<string, mixed>
	 */
	private static function collect_filter_args( array $req ) {
		$args = array();

		if ( isset( $req['limit'] ) ) {
			$args['limit'] = (int) wp_unslash( $req['limit'] );
		}
		if ( isset( $req['offset'] ) ) {
			$args['offset'] = (int) wp_unslash( $req['offset'] );
		}
		if ( isset( $req['level'] ) ) {
			$args['level'] = sanitize_key( wp_unslash( $req['level'] ) );
		}
		if ( isset( $req['date_from'] ) ) {
			$args['date_from'] = sanitize_text_field( wp_unslash( $req['date_from'] ) );
		}
		if ( isset( $req['date_to'] ) ) {
			$args['date_to'] = sanitize_text_field( wp_unslash( $req['date_to'] ) );
		}
		if ( isset( $req['source'] ) ) {
			$args['source'] = sanitize_text_field( wp_unslash( $req['source'] ) );
		}
		if ( isset( $req['search'] ) ) {
			$args['search'] = sanitize_text_field( wp_unslash( $req['search'] ) );
		}

		return $args;
	}

	private function __construct() {
	}
}
