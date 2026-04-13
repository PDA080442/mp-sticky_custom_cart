<?php
/**
 * Per-tab reset to defaults (admin-post handler).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Admin;

use MpStickyCustomCart\Core\Config\FeatureFlagsDefaults;
use MpStickyCustomCart\Core\Config\UiLabelsDefaults;
use MpStickyCustomCart\Core\Config\UiSettingsDefaults;
use MpStickyCustomCart\Core\Constants;
use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Restores default values for settings sections mapped to the active admin tab.
 */
final class SettingsTabResetHandler {

	public const ACTION = 'mp_scc_reset_settings_tab';

	public static function register() {
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'handle' ) );
	}

	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mp-sticky-custom-cart' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION, 'mp_scc_reset_nonce' );

		$tab = isset( $_POST['mp_scc_tab'] ) ? sanitize_key( wp_unslash( $_POST['mp_scc_tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$map = self::tab_to_sections();
		if ( '' === $tab || ! isset( $map[ $tab ] ) ) {
			self::redirect_invalid_tab();
		}

		$sections = $map[ $tab ];
		self::reset_settings_sections( $sections );

		if ( in_array( 'diagnostics', $sections, true ) ) {
			self::reset_feature_flags();
		}

		OptionResolver::flush_cache();

		self::redirect_ok( $tab );
	}

	/**
	 * @return array<string, array<int, string>> Tab id => list of top-level keys in OPTION_SETTINGS.
	 */
	private static function tab_to_sections() {
		return array(
			'catalog'     => array( 'catalog', 'labels' ),
			'cart'        => array( 'sticky_cart', 'cart_route', 'notices' ),
			'wishlist'    => array( 'wishlist_ui' ),
			'styles'      => array( 'styles' ),
			'diagnostics' => array( 'diagnostics' ),
		);
	}

	/**
	 * @param string[] $sections Top-level keys to replace with defaults.
	 */
	private static function reset_settings_sections( array $sections ) {
		$defaults = self::full_defaults_tree();
		$saved    = get_option( Constants::OPTION_SETTINGS, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		foreach ( $sections as $section ) {
			$section = sanitize_key( $section );
			if ( isset( $defaults[ $section ] ) && is_array( $defaults[ $section ] ) ) {
				$saved[ $section ] = $defaults[ $section ];
			}
		}

		update_option( Constants::OPTION_SETTINGS, $saved, false );
	}

	private static function reset_feature_flags() {
		$defaults = FeatureFlagsDefaults::get();
		$saved    = get_option( Constants::OPTION_FEATURE_FLAGS, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		foreach ( $defaults as $key => $value ) {
			$saved[ $key ] = (bool) $value;
		}
		update_option( Constants::OPTION_FEATURE_FLAGS, $saved, false );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function full_defaults_tree() {
		$tree = UiSettingsDefaults::get();
		if ( ! isset( $tree['labels'] ) || ! is_array( $tree['labels'] ) ) {
			$tree['labels'] = UiLabelsDefaults::get_raw();
		}
		return $tree;
	}

	/**
	 * @param string $tab Tab id.
	 */
	private static function redirect_ok( $tab ) {
		$url = admin_url( 'admin.php?page=' . rawurlencode( Constants::SLUG ) );
		$url = add_query_arg(
			array(
				'tab'          => sanitize_key( $tab ),
				'mp-scc-reset' => '1',
			),
			$url
		);
		wp_safe_redirect( $url );
		exit;
	}

	private static function redirect_invalid_tab() {
		$url = admin_url( 'admin.php?page=' . rawurlencode( Constants::SLUG ) );
		$url = add_query_arg(
			array(
				'tab'                 => 'catalog',
				'mp-scc-reset-error' => '1',
			),
			$url
		);
		wp_safe_redirect( $url );
		exit;
	}

	private function __construct() {
	}
}
