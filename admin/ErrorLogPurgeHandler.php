<?php
/**
 * Clears {@see Constants::OPTION_ERROR_LOG} from admin.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Admin;

use MpStickyCustomCart\Core\Constants;
use MpStickyCustomCart\Core\ErrorLogService;
use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * admin-post handler for log purge.
 */
final class ErrorLogPurgeHandler {

	public static function register() {
		add_action( 'admin_post_' . Constants::ADMIN_POST_PURGE_ERROR_LOG, array( self::class, 'handle' ) );
	}

	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mp-sticky-custom-cart' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( Constants::ADMIN_POST_PURGE_ERROR_LOG );

		ErrorLogService::instance()->purge();
		OptionResolver::flush_cache();

		$tab = isset( $_POST['mp_scc_return_tab'] ) ? sanitize_key( wp_unslash( $_POST['mp_scc_return_tab'] ) ) : 'diagnostics'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$url = admin_url( 'admin.php?page=' . rawurlencode( Constants::SLUG ) );
		$url = add_query_arg(
			array(
				'tab'                => $tab,
				'mp-scc-log-purged' => '1',
			),
			$url
		);
		wp_safe_redirect( $url );
		exit;
	}

	private function __construct() {
	}
}
