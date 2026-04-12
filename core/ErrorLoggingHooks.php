<?php
/**
 * Server endpoints and hooks for AJAX/JS error diagnostics.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

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
	 * Store a short client event (e.g. out-of-stock click) in {@see Constants::OPTION_ERROR_LOG}.
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

		$entry = array(
			't'          => time(),
			'type'       => 'client_' . $event,
			'product_id' => $product_id,
			'context'    => $context,
		);

		/**
		 * Filters a client log entry before storage.
		 *
		 * @param array<string, mixed> $entry Log entry.
		 */
		$entry = apply_filters( 'mp_sticky_custom_cart_client_log_entry', $entry );

		$log = get_option( Constants::OPTION_ERROR_LOG, array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		$log[] = $entry;
		if ( count( $log ) > 100 ) {
			$log = array_slice( $log, -100 );
		}
		update_option( Constants::OPTION_ERROR_LOG, $log, false );

		wp_send_json_success( array( 'logged' => true ) );
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
