<?php
/**
 * Export / import plugin settings and feature flags as JSON (e.g. staging → production).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Admin;

use MpStickyCustomCart\Core\Constants;
use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Envelope: `mp_scc_config_version`, `plugin_version`, `exported_at`, `site`, `settings`, `feature_flags`.
 */
final class ConfigImportExportHandler {

	public static function register() {
		add_action( 'admin_post_' . Constants::ADMIN_POST_EXPORT_CONFIG, array( self::class, 'handle_export' ) );
		add_action( 'admin_post_' . Constants::ADMIN_POST_IMPORT_CONFIG, array( self::class, 'handle_import' ) );
	}

	/**
	 * Renders standalone forms (outside options.php — file upload cannot target Settings API).
	 */
	public static function render_panel() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$export_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . rawurlencode( Constants::ADMIN_POST_EXPORT_CONFIG ) ),
			Constants::NONCE_CONFIG_IMPORT_EXPORT
		);

		echo '<div class="mp-scc-config-io">';
		echo '<h2>' . esc_html__( 'Экспорт и импорт конфигурации', 'mp-sticky-custom-cart' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Сохраните настройки плагина (все вкладки) и feature flags в JSON-файл на тестовом сайте и загрузите его на боевом. Журнал ошибок и счётчики не выгружаются.', 'mp-sticky-custom-cart' ) . '</p>';

		echo '<h3>' . esc_html__( 'Экспорт', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p>';
		echo '<a href="' . esc_url( $export_url ) . '" class="button button-secondary">' . esc_html__( 'Скачать mp-scc-config.json', 'mp-sticky-custom-cart' ) . '</a>';
		echo '</p>';

		echo '<h3>' . esc_html__( 'Импорт', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" enctype="multipart/form-data" class="mp-scc-config-import-form">';
		wp_nonce_field( Constants::NONCE_CONFIG_IMPORT_EXPORT, 'mp_scc_config_nonce' );
		echo '<input type="hidden" name="action" value="' . esc_attr( Constants::ADMIN_POST_IMPORT_CONFIG ) . '" />';
		echo '<p><input type="file" name="mp_scc_config_file" accept=".json,application/json" required /></p>';
		echo '<p class="description">' . esc_html__( 'Рекомендуется сделать резервную копию сайта перед заменой настроек. Файл должен быть создан этим же плагином (формат JSON).', 'mp-sticky-custom-cart' ) . '</p>';
		submit_button( __( 'Импортировать из файла', 'mp-sticky-custom-cart' ), 'primary', 'submit', false, array( 'onclick' => 'return confirm(' . wp_json_encode( __( 'Заменить текущие настройки плагина и feature flags содержимым файла?', 'mp-sticky-custom-cart' ) ) . ');' ) );
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Streams JSON download.
	 */
	public static function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mp-sticky-custom-cart' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( Constants::NONCE_CONFIG_IMPORT_EXPORT );

		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$flags = get_option( Constants::OPTION_FEATURE_FLAGS, array() );
		if ( ! is_array( $flags ) ) {
			$flags = array();
		}

		$payload = array(
			'mp_scc_config_version' => Constants::CONFIG_EXPORT_VERSION,
			'plugin_version'        => defined( 'MP_STICKY_CUSTOM_CART_VERSION' ) ? MP_STICKY_CUSTOM_CART_VERSION : '',
			'exported_at'           => gmdate( 'c' ),
			'site'                  => home_url( '/' ),
			'settings'              => $settings,
			'feature_flags'         => $flags,
		);

		$json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( false === $json ) {
			wp_die( esc_html__( 'Не удалось сформировать JSON.', 'mp-sticky-custom-cart' ), '', array( 'response' => 500 ) );
		}

		nocache_headers();
		$fname = 'mp-scc-config-' . gmdate( 'Y-m-d-His' ) . '.json';
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $fname . '"' );
		header( 'Content-Length: ' . (string) strlen( $json ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary-safe JSON body
		echo $json;
		exit;
	}

	/**
	 * Parses uploaded JSON, validates envelope, saves options.
	 */
	public static function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mp-sticky-custom-cart' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( Constants::NONCE_CONFIG_IMPORT_EXPORT, 'mp_scc_config_nonce' );

		$url = self::import_redirect_base();

		if ( empty( $_FILES['mp_scc_config_file'] ) || ! is_array( $_FILES['mp_scc_config_file'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			self::redirect_with_status( $url, 'no_file' );
		}

		$file = $_FILES['mp_scc_config_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
			self::redirect_with_status( $url, 'upload_error' );
		}
		if ( empty( $file['tmp_name'] ) || ! is_string( $file['tmp_name'] ) ) {
			self::redirect_with_status( $url, 'no_file' );
		}
		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			self::redirect_with_status( $url, 'invalid_upload' );
		}
		if ( isset( $file['size'] ) && (int) $file['size'] > Constants::CONFIG_IMPORT_MAX_BYTES ) {
			self::redirect_with_status( $url, 'file_too_large' );
		}

		$raw = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw || '' === $raw ) {
			self::redirect_with_status( $url, 'empty_file' );
		}

		$data = json_decode( $raw, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
			self::redirect_with_status( $url, 'invalid_json' );
		}

		if ( ! isset( $data['mp_scc_config_version'] ) || (int) $data['mp_scc_config_version'] !== Constants::CONFIG_EXPORT_VERSION ) {
			self::redirect_with_status( $url, 'bad_format' );
		}
		if ( ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
			self::redirect_with_status( $url, 'bad_format' );
		}
		if ( ! isset( $data['feature_flags'] ) || ! is_array( $data['feature_flags'] ) ) {
			self::redirect_with_status( $url, 'bad_format' );
		}

		$settings = SettingsSanitizer::sanitize_settings_for_import( $data['settings'] );
		$flags    = SettingsSanitizer::sanitize_feature_flags_for_import( $data['feature_flags'] );

		update_option( Constants::OPTION_SETTINGS, $settings, false );
		update_option( Constants::OPTION_FEATURE_FLAGS, $flags, false );
		OptionResolver::flush_cache();

		self::redirect_with_status( $url, 'ok' );
	}

	/**
	 * @return string Admin URL (diagnostics tab).
	 */
	private static function import_redirect_base() {
		return admin_url( 'admin.php?page=' . rawurlencode( Constants::SLUG ) . '&tab=diagnostics' );
	}

	/**
	 * @param string $base URL.
	 * @param string $code Short status code for query arg.
	 */
	private static function redirect_with_status( $base, $code ) {
		wp_safe_redirect( add_query_arg( 'mp-scc-config-import', (string) $code, $base ) );
		exit;
	}

	private function __construct() {
	}
}
