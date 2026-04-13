<?php
/**
 * Server endpoints and hooks for AJAX/JS error diagnostics.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

use MpStickyCustomCart\Core\Contracts\LoggingServiceInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Client log ingestion when {@see OptionResolver::get_setting} `diagnostics.client_error_logging` is enabled.
 */
final class ErrorLoggingHooks {

	public static function register() {
		add_action( 'wp_ajax_' . Constants::AJAX_ACTION_LOG_CLIENT_EVENT, array( self::class, 'handle_client_log' ) );
		add_action( 'wp_ajax_nopriv_' . Constants::AJAX_ACTION_LOG_CLIENT_EVENT, array( self::class, 'handle_client_log' ) );

		/**
		 * Fires when error logging hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_error_logging_hooks_registered' );
	}

	/**
	 * Store client diagnostics in {@see Constants::OPTION_ERROR_LOG} (single POST or JSON `batch` array).
	 */
	public static function handle_client_log() {
		if ( ! check_ajax_referer( Constants::AJAX_NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Неверный запрос безопасности.', 'mp-sticky-custom-cart' ),
					'code'    => 'invalid_nonce',
				)
			);
		}

		if ( ! OptionResolver::get_setting( 'diagnostics.client_error_logging', true ) ) {
			wp_send_json_success( array( 'logged' => false ) );
		}

		if ( isset( $_POST['batch'] ) && is_string( $_POST['batch'] ) ) {
			self::handle_client_log_batch();
			return;
		}

		$event = isset( $_POST['event'] ) ? sanitize_key( wp_unslash( $_POST['event'] ) ) : '';
		if ( '' === $event || strlen( $event ) > 80 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Некорректное событие.', 'mp-sticky-custom-cart' ),
					'code'    => 'bad_event',
				)
			);
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$context    = isset( $_POST['context'] ) ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : '';
		if ( strlen( $context ) > 200 ) {
			$context = substr( $context, 0, 200 );
		}
		$page_url = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';
		if ( strlen( $page_url ) > 500 ) {
			$page_url = substr( $page_url, 0, 500 );
		}
		$user_agent = isset( $_POST['user_agent'] ) ? sanitize_text_field( wp_unslash( $_POST['user_agent'] ) ) : '';
		if ( strlen( $user_agent ) > 400 ) {
			$user_agent = substr( $user_agent, 0, 400 );
		}

		$payload = array(
			'product_id' => $product_id,
			'context'    => $context,
		);
		if ( '' !== $page_url ) {
			$payload['page_url'] = $page_url;
		}
		if ( '' !== $user_agent ) {
			$payload['user_agent'] = $user_agent;
		}

		ErrorLogService::instance()->log(
			LoggingServiceInterface::LEVEL_INFO,
			'client_' . $event,
			array(
				'source'   => 'client_event',
				'endpoint' => Constants::AJAX_ACTION_LOG_CLIENT_EVENT,
				'code'     => $event,
				'payload'  => array_filter( $payload ),
			)
		);

		wp_send_json_success( array( 'logged' => true ) );
	}

	/**
	 * Process buffered client rows: [{ event, level?, message?, context?, detail?, page_url?, user_agent?, product_id? }, ...]
	 */
	private static function handle_client_log_batch() {
		$raw = wp_unslash( $_POST['batch'] );
		$items = json_decode( $raw, true );
		if ( ! is_array( $items ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Некорректный batch.', 'mp-sticky-custom-cart' ),
					'code'    => 'bad_batch',
				)
			);
		}

		$items = array_slice( $items, 0, 25 );
		$count = 0;

		$level_map = array(
			'debug' => LoggingServiceInterface::LEVEL_DEBUG,
			'info'  => LoggingServiceInterface::LEVEL_INFO,
			'warn'  => LoggingServiceInterface::LEVEL_WARN,
			'error' => LoggingServiceInterface::LEVEL_ERROR,
		);

		foreach ( $items as $it ) {
			if ( ! is_array( $it ) ) {
				continue;
			}
			$event = isset( $it['event'] ) ? sanitize_key( wp_unslash( $it['event'] ) ) : '';
			if ( '' === $event || strlen( $event ) > 80 ) {
				continue;
			}

			$lvl_key = isset( $it['level'] ) ? sanitize_key( wp_unslash( $it['level'] ) ) : 'info';
			if ( ! isset( $level_map[ $lvl_key ] ) ) {
				$lvl_key = 'info';
			}
			$level = $level_map[ $lvl_key ];

			$message = isset( $it['message'] ) ? sanitize_text_field( wp_unslash( $it['message'] ) ) : '';
			if ( strlen( $message ) > 500 ) {
				$message = substr( $message, 0, 500 );
			}
			if ( '' === $message ) {
				$message = 'client_' . $event;
			}

			$page_url = isset( $it['page_url'] ) ? esc_url_raw( wp_unslash( $it['page_url'] ) ) : '';
			if ( strlen( $page_url ) > 500 ) {
				$page_url = substr( $page_url, 0, 500 );
			}
			$user_agent = isset( $it['user_agent'] ) ? sanitize_text_field( wp_unslash( $it['user_agent'] ) ) : '';
			if ( strlen( $user_agent ) > 400 ) {
				$user_agent = substr( $user_agent, 0, 400 );
			}
			$context = isset( $it['context'] ) ? sanitize_text_field( wp_unslash( $it['context'] ) ) : '';
			if ( strlen( $context ) > 300 ) {
				$context = substr( $context, 0, 300 );
			}
			$detail = isset( $it['detail'] ) ? sanitize_text_field( wp_unslash( $it['detail'] ) ) : '';
			if ( strlen( $detail ) > 500 ) {
				$detail = substr( $detail, 0, 500 );
			}
			$product_id = isset( $it['product_id'] ) ? absint( $it['product_id'] ) : 0;

			$payload = array(
				'page_url'   => $page_url,
				'user_agent' => $user_agent,
				'context'    => $context,
				'detail'     => $detail,
			);
			if ( $product_id > 0 ) {
				$payload['product_id'] = $product_id;
			}
			$payload = array_filter(
				$payload,
				function ( $v ) {
					return null !== $v && '' !== $v;
				}
			);

			ErrorLogService::instance()->log(
				$level,
				$message,
				array(
					'source'   => 'client_batch',
					'endpoint' => Constants::AJAX_ACTION_LOG_CLIENT_EVENT,
					'code'     => $event,
					'payload'  => $payload,
				)
			);
			++$count;
		}

		wp_send_json_success( array( 'logged' => $count > 0, 'count' => $count ) );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
