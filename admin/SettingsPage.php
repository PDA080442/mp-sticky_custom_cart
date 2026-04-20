<?php
/**
 * Admin settings screen: tabs Каталог, Корзина, Избранное, Стили, Служебное.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Admin;

use MpStickyCustomCart\Core\Config\CssVariablesContract;
use MpStickyCustomCart\Core\Config\FeatureFlagDefinitions;
use MpStickyCustomCart\Core\Config\FeatureFlagsDefaults;
use MpStickyCustomCart\Core\CatalogCartIconAppearance;
use MpStickyCustomCart\Core\CatalogCartIconPresets;
use MpStickyCustomCart\Core\Config\UiLabelsDefaults;
use MpStickyCustomCart\Core\Constants;
use MpStickyCustomCart\Core\ErrorLogService;
use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the menu page and renders option fields bound to Settings API.
 */
final class SettingsPage {

	/**
	 * Standalone admin-post form id for error log purge (must never be nested inside the options.php form).
	 */
	private const ERROR_LOG_PURGE_FORM_ID = 'mp-scc-purge-error-log-form';

	/**
	 * Hook suffix returned by add_menu_page.
	 *
	 * @var string
	 */
	private static $hook_suffix = '';

	public static function register() {
		add_action( 'admin_menu', array( self::class, 'add_menu_page' ), 99 );
		add_filter( 'wp_redirect', array( self::class, 'preserve_settings_tab_in_redirect' ), 10, 1 );
	}

	/**
	 * Submenu under WooCommerce when Woo is active; otherwise top-level so settings stay reachable.
	 */
	public static function add_menu_page() {
		$cap  = 'manage_options';
		$slug = Constants::SLUG;

		if ( class_exists( '\WooCommerce', false ) && function_exists( 'WC' ) ) {
			$hook = add_submenu_page(
				'woocommerce',
				__( 'Sticky Cart', 'mp-sticky-custom-cart' ),
				__( 'Sticky Cart', 'mp-sticky-custom-cart' ),
				$cap,
				$slug,
				array( self::class, 'render' )
			);
		} else {
			$hook = add_menu_page(
				__( 'Sticky Cart', 'mp-sticky-custom-cart' ),
				__( 'Sticky Cart', 'mp-sticky-custom-cart' ),
				$cap,
				$slug,
				array( self::class, 'render' ),
				'dashicons-cart',
				56
			);
		}
		self::$hook_suffix = false === $hook ? '' : (string) $hook;
	}

	/**
	 * After saving via options.php, keep the active tab if Referer dropped the query string.
	 *
	 * @param string $location Redirect target.
	 * @return string
	 */
	public static function preserve_settings_tab_in_redirect( $location ) {
		if ( ! is_string( $location ) || '' === $location ) {
			return $location;
		}
		if ( empty( $_POST['option_page'] ) || empty( $_POST['mp_scc_active_tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $location;
		}
		$expected = Constants::SLUG . '_settings';
		if ( $expected !== (string) wp_unslash( $_POST['option_page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $location;
		}
		$tab = sanitize_key( wp_unslash( $_POST['mp_scc_active_tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( self::tabs()[ $tab ] ) ) {
			return $location;
		}
		if ( false === strpos( $location, 'page=' . Constants::SLUG ) ) {
			return $location;
		}
		$location = remove_query_arg( array( 'tab', 'settings-updated', 'mp-scc-reset', 'mp-scc-reset-error' ), $location );
		return add_query_arg(
			array(
				'settings-updated' => 'true',
				'tab'              => $tab,
			),
			$location
		);
	}

	/**
	 * @return string
	 */
	public static function get_hook_suffix() {
		return self::$hook_suffix;
	}

	/**
	 * Render the tabbed settings UI.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'catalog'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tabs = self::tabs();
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'catalog';
		}

		$s = OptionResolver::get_settings();
		$f = OptionResolver::get_feature_flags();

		$opt = Constants::OPTION_SETTINGS;
		$fg  = Constants::OPTION_FEATURE_FLAGS;

		?>
		<div class="wrap mp-scc-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php self::render_admin_notices(); ?>

			<h2 class="nav-tab-wrapper">
				<?php
				foreach ( $tabs as $id => $label ) {
					$url   = admin_url( 'admin.php?page=' . rawurlencode( Constants::SLUG ) . '&tab=' . rawurlencode( $id ) );
					$class = $tab === $id ? 'nav-tab nav-tab-active' : 'nav-tab';
					printf(
						'<a href="%s" class="%s">%s</a>',
						esc_url( $url ),
						esc_attr( $class ),
						esc_html( $label )
					);
				}
				?>
			</h2>

			<?php
			$intro_map = self::tab_intro_descriptions();
			if ( isset( $intro_map[ $tab ] ) ) {
				echo '<p class="mp-scc-tab-intro description">' . esc_html( $intro_map[ $tab ] ) . '</p>';
			}
			?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mp-scc-reset-tab-form">
				<?php wp_nonce_field( SettingsTabResetHandler::ACTION, 'mp_scc_reset_nonce' ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( SettingsTabResetHandler::ACTION ); ?>" />
				<input type="hidden" name="mp_scc_tab" value="<?php echo esc_attr( $tab ); ?>" />
				<?php
				submit_button(
					__( 'Сбросить только эту вкладку к значениям по умолчанию', 'mp-sticky-custom-cart' ),
					'secondary',
					'submit',
					false,
					array(
						'onclick' => 'return confirm(' . wp_json_encode( __( 'Заменить настройки этой вкладки на значения по умолчанию? На вкладке «Служебное» также сбросятся feature flags.', 'mp-sticky-custom-cart' ) ) . ');',
					)
				);
				?>
			</form>

			<?php if ( 'diagnostics' === $tab ) : ?>
			<form
				id="<?php echo esc_attr( self::ERROR_LOG_PURGE_FORM_ID ); ?>"
				class="mp-scc-hidden-post-form"
				method="post"
				action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				hidden
				aria-hidden="true"
			>
				<?php wp_nonce_field( Constants::ADMIN_POST_PURGE_ERROR_LOG ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( Constants::ADMIN_POST_PURGE_ERROR_LOG ); ?>" />
				<input type="hidden" name="mp_scc_return_tab" value="diagnostics" />
			</form>
			<?php endif; ?>

			<form action="options.php" method="post" class="mp-scc-settings-form" id="mp-scc-settings-form">
				<?php settings_fields( Constants::SLUG . '_settings' ); ?>
				<input type="hidden" name="mp_scc_active_tab" value="<?php echo esc_attr( $tab ); ?>" />

				<?php
				switch ( $tab ) {
					case 'catalog':
						self::render_catalog_tab( $s, $opt );
						break;
					case 'cart':
						self::render_cart_tab( $s, $opt );
						break;
					case 'wishlist':
						self::render_wishlist_tab( $s, $opt );
						break;
					case 'styles':
						self::render_styles_tab( $s, $opt );
						break;
					case 'diagnostics':
						self::render_diagnostics_tab( $s, $f, $opt, $fg );
						break;
				}

				submit_button();
				?>
			</form>
			<?php if ( 'diagnostics' === $tab ) : ?>
				<?php ConfigImportExportHandler::render_panel(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @return array<string, string>
	 */
	private static function tabs() {
		return array(
			'catalog'     => __( 'Каталог', 'mp-sticky-custom-cart' ),
			'cart'        => __( 'Корзина', 'mp-sticky-custom-cart' ),
			'wishlist'    => __( 'Избранное', 'mp-sticky-custom-cart' ),
			'styles'      => __( 'Стили', 'mp-sticky-custom-cart' ),
			'diagnostics' => __( 'Служебное', 'mp-sticky-custom-cart' ),
		);
	}

	/**
	 * Short context under the tab bar (one line per tab).
	 *
	 * @return array<string, string>
	 */
	private static function tab_intro_descriptions() {
		return array(
			'catalog'     => __( 'Карточки в лупе WooCommerce: hover «Подробнее», клик по изображению, селекторы и подписи интерфейса.', 'mp-sticky-custom-cart' ),
			'cart'        => __( 'Поведение нижней панели и drawer, редирект страницы корзины, уведомления. Внешний вид — вкладка «Стили».', 'mp-sticky-custom-cart' ),
			'wishlist'    => __( 'Отступы и слой иконки избранного относительно overlay каталога (см. docs/wishlist-integration.md).', 'mp-sticky-custom-cart' ),
			'styles'      => __( 'Палитра, эффекты подложки, типографика и отступы нижней панели; живой предпросмотр и CSS-переменные на витрине.', 'mp-sticky-custom-cart' ),
			'diagnostics' => __( 'Логи с клиента, срок хранения, переключатели функций (feature flags), экспорт и импорт конфигурации JSON.', 'mp-sticky-custom-cart' ),
		);
	}

	private static function render_admin_notices() {
		if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Настройки сохранены.', 'mp-sticky-custom-cart' ) . '</p></div>';
		}
		if ( isset( $_GET['mp-scc-reset'] ) && '1' === $_GET['mp-scc-reset'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Настройки этой вкладки сброшены к значениям по умолчанию.', 'mp-sticky-custom-cart' ) . '</p></div>';
		}
		if ( isset( $_GET['mp-scc-reset-error'] ) && '1' === $_GET['mp-scc-reset-error'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Не удалось сбросить вкладку: неверный запрос.', 'mp-sticky-custom-cart' ) . '</p></div>';
		}
		if ( isset( $_GET['mp-scc-log-purged'] ) && '1' === $_GET['mp-scc-log-purged'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Журнал ошибок очищен.', 'mp-sticky-custom-cart' ) . '</p></div>';
		}

		if ( isset( $_GET['mp-scc-config-import'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$code = sanitize_key( wp_unslash( $_GET['mp-scc-config-import'] ) );
			$msg  = '';
			$ok   = false;
			switch ( $code ) {
				case 'ok':
					$msg = __( 'Конфигурация импортирована. Настройки и feature flags обновлены.', 'mp-sticky-custom-cart' );
					$ok  = true;
					break;
				case 'no_file':
					$msg = __( 'Файл не выбран или не получен.', 'mp-sticky-custom-cart' );
					break;
				case 'upload_error':
					$msg = __( 'Ошибка загрузки файла.', 'mp-sticky-custom-cart' );
					break;
				case 'invalid_upload':
					$msg = __( 'Недопустимый временный файл загрузки.', 'mp-sticky-custom-cart' );
					break;
				case 'file_too_large':
					$msg = __( 'Файл слишком большой (лимит 512 КБ).', 'mp-sticky-custom-cart' );
					break;
				case 'empty_file':
					$msg = __( 'Файл пустой или не прочитан.', 'mp-sticky-custom-cart' );
					break;
				case 'invalid_json':
					$msg = __( 'Невалидный JSON. Убедитесь, что выбран файл, экспортированный этим плагином.', 'mp-sticky-custom-cart' );
					break;
				case 'bad_format':
					$msg = __( 'Неверный формат конфигурации (ожидается mp_scc_config_version и блоки settings / feature_flags).', 'mp-sticky-custom-cart' );
					break;
				default:
					$msg = __( 'Импорт не выполнен.', 'mp-sticky-custom-cart' );
			}
			$class = $ok ? 'notice-success' : 'notice-error';
			echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		}
	}

	/**
	 * JSON payload for live style preview (styles tab). Consumed by admin/js/settings-page.js.
	 *
	 * @param array{var:string,fmt:string,suffix?:string,omit_if_zero?:bool} $meta Preview contract.
	 * @return string Empty or space + data-mp-scc-preview="...".
	 */
	private static function preview_data_attr( array $meta ) {
		if ( ! isset( $meta['fmt'] ) ) {
			return '';
		}
		if ( 'typography_scale' === $meta['fmt'] ) {
			return ' data-mp-scc-preview="' . esc_attr( wp_json_encode( $meta, JSON_UNESCAPED_UNICODE ) ) . '"';
		}
		if ( ! isset( $meta['var'] ) ) {
			return '';
		}
		return ' data-mp-scc-preview="' . esc_attr( wp_json_encode( $meta, JSON_UNESCAPED_UNICODE ) ) . '"';
	}

	private static function help_tip_button( $text ) {
		$text = is_string( $text ) ? trim( $text ) : '';
		if ( '' === $text ) {
			return '';
		}
		return ' <button type="button" class="button-link mp-scc-help-tip" aria-label="' . esc_attr( $text ) . '" title="' . esc_attr( $text ) . '" data-mp-scc-help="' . esc_attr( $text ) . '"><span class="dashicons dashicons-editor-help" aria-hidden="true"></span></button>';
	}

	/**
	 * Copy-paste examples for CSS font-family (web fonts often named Montserrat, Playfair Display, Raleway).
	 *
	 * @param string $lead Intro sentence (translated), shown before the code samples.
	 */
	private static function render_font_family_examples_line( $lead ) {
		echo '<p class="description">' . esc_html( $lead ) . ' ';
		echo '<code>' . esc_html( 'Montserrat, sans-serif' ) . '</code>; ';
		echo '<code>' . esc_html( '"Playfair Display", serif' ) . '</code>; ';
		echo '<code>' . esc_html( 'Raleway, sans-serif' ) . '</code>.</p>';
	}

	/**
	 * How catalog options map to the storefront (for admins).
	 */
	private static function render_catalog_impact_notes() {
		echo '<div class="mp-scc-catalog-impact-notes">';
		echo '<p><strong>' . esc_html__( 'Как это влияет на витрину', 'mp-sticky-custom-cart' ) . '</strong></p>';
		echo '<ul class="ul-disc">';
		echo '<li>' . esc_html__( 'Режим «иконка корзины» / «клик по миниатюре»: выбор одного из встроенных SVG-значков, смещения и размеры, толщина линии; числа попадают в CSS-переменные и в расчёт позиции на витрине.', 'mp-sticky-custom-cart' ) . '</li>';
		echo '<li>' . esc_html__( 'Поведение клика по миниатюре и селекторы задают, будет ли изображение добавлять simple-товар в корзину без перехода на страницу товара (только в режиме «по миниатюре»).', 'mp-sticky-custom-cart' ) . '</li>';
		echo '<li>' . esc_html__( 'Блок «Подробнее» и анимация: длительность, easing и пресет попадают в CSS-переменные (--mp-scc-catalog-*) и управляют появлением полосы с текстом «Подробнее».', 'mp-sticky-custom-cart' ) . '</li>';
		echo '<li>' . esc_html__( 'Тексты «Подробнее» и «Нет в наличии» подставляются в overlay и в тосты на карточке; пустые значения заменяются дефолтами плагина.', 'mp-sticky-custom-cart' ) . '</li>';
		echo '<li>' . esc_html__( 'Остальные подписи в таблице ниже используются в sticky-корзине и на странице товара, а не только в каталоге.', 'mp-sticky-custom-cart' ) . '</li>';
		echo '</ul></div>';
	}

	/**
	 * Static preview of the catalog overlay (approximate look on shop loop).
	 *
	 * @param array<string, mixed> $s Full settings.
	 * @param array<string, mixed> $c Catalog section.
	 */
	private static function render_catalog_card_preview( array $s, array $c ) {
		$labels = OptionResolver::get_labels();
		$label  = isset( $labels[ UiLabelsDefaults::KEY_MORE_INFO ] ) ? (string) $labels[ UiLabelsDefaults::KEY_MORE_INFO ] : '';

		$surface = isset( $c['catalog_add_surface'] ) ? (string) $c['catalog_add_surface'] : 'image_click';
		if ( ! in_array( $surface, array( 'image_click', 'cart_icon' ), true ) ) {
			$surface = 'image_click';
		}
		$icon_off_top = isset( $c['catalog_cart_icon_offset_top_px'] ) ? max( 0, min( 64, (int) $c['catalog_cart_icon_offset_top_px'] ) ) : 8;
		$icon_off_left = isset( $c['catalog_cart_icon_offset_left_px'] ) ? max( 0, min( 64, (int) $c['catalog_cart_icon_offset_left_px'] ) ) : 8;
		$icon_hit = isset( $c['catalog_cart_icon_hit_size_px'] ) ? max( 28, min( 56, (int) $c['catalog_cart_icon_hit_size_px'] ) ) : 36;
		$icon_glyph = isset( $c['catalog_cart_icon_glyph_size_px'] ) ? max( 14, min( 28, (int) $c['catalog_cart_icon_glyph_size_px'] ) ) : 20;
		$icon_stroke_prev = isset( $c['catalog_cart_icon_stroke_width'] ) ? (float) $c['catalog_cart_icon_stroke_width'] : 1.75;
		if ( $icon_stroke_prev < 1.0 || $icon_stroke_prev > 3.0 ) {
			$icon_stroke_prev = 1.75;
		}
		$icon_preset_prev = CatalogCartIconPresets::normalize(
			isset( $c['catalog_cart_icon_preset'] ) ? (string) $c['catalog_cart_icon_preset'] : CatalogCartIconPresets::DEFAULT
		);

		$motion = isset( $c['hover_motion_preset'] ) ? (string) $c['hover_motion_preset'] : 'fade_slide';
		if ( ! in_array( $motion, array( 'fade_slide', 'fade', 'slide' ), true ) ) {
			$motion = 'fade_slide';
		}
		$mobile_always = ! empty( $c['hover_overlay_mobile_always'] );

		$props   = CssVariablesContract::build_properties( $s );
		$preview = array();
		$extra   = array(
			CssVariablesContract::PREFIX . 'color-button-primary',
			CssVariablesContract::PREFIX . 'color-button-primary-text',
			CssVariablesContract::PREFIX . 'wishlist-heart-reserve-right',
			CssVariablesContract::PREFIX . 'wishlist-overlay-clearance',
		);
		foreach ( $props as $name => $value ) {
			if ( 0 === strpos( $name, CssVariablesContract::PREFIX . 'catalog-' ) || in_array( $name, $extra, true ) ) {
				$preview[ $name ] = $value;
			}
		}
		$style = '';
		foreach ( $preview as $name => $value ) {
			$style .= $name . ':' . $value . ';';
		}

		$cls = 'mp-scc-catalog-overlay mp-scc-catalog-overlay--floating mp-scc-catalog-overlay--motion-' . $motion;
		if ( $mobile_always ) {
			$cls .= ' mp-scc-catalog-overlay--mobile-always';
		}

		echo '<h3>' . esc_html__( 'Предпросмотр кнопки на карточке', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Упрощённый макет: на сайте вид зависит от темы и ширины колонки. Ниже — подпись «Подробнее», при режиме «иконка корзины» — квадрат зоны нажатия и значок в углу (по текущим отступам и размерам).', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<div class="mp-scc-admin-catalog-preview" style="' . esc_attr( $style ) . '">';
		echo '<div class="mp-scc-admin-catalog-preview__card" role="presentation">';
		echo '<div class="mp-scc-admin-catalog-preview__thumb">';
		echo '<div class="mp-scc-admin-catalog-preview__fake-img" aria-hidden="true"></div>';
		if ( 'cart_icon' === $surface ) {
			$slot_style = sprintf(
				'top:%dpx;left:%dpx;width:%dpx;height:%dpx;',
				$icon_off_top,
				$icon_off_left,
				$icon_hit,
				$icon_hit
			);
			$glyph_style = sprintf( 'width:%dpx;height:%dpx;', $icon_glyph, $icon_glyph );
			echo '<span class="mp-scc-admin-catalog-preview__cart-slot" style="' . esc_attr( $slot_style ) . '" aria-hidden="true">';
			echo '<span class="mp-scc-admin-catalog-preview__cart-svg-wrap" style="' . esc_attr( $glyph_style ) . '">';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted SVG from plugin presets.
			echo CatalogCartIconPresets::svg_markup( $icon_preset_prev, $icon_glyph, $icon_glyph, $icon_stroke_prev );
			echo '</span>';
			echo '</span>';
		}
		printf(
			'<a href="#" class="%s" onclick="return false;">',
			esc_attr( $cls )
		);
		echo '<span class="mp-scc-catalog-overlay__label">' . esc_html( $label ) . '</span>';
		echo '</a>';
		echo '</div></div></div>';
	}

	/**
	 * @param array<string, mixed> $s   Settings tree.
	 * @param string               $opt Option key (name prefix).
	 */
	private static function render_catalog_tab( array $s, $opt ) {
		$c = isset( $s['catalog'] ) && is_array( $s['catalog'] ) ? $s['catalog'] : array();
		$l = isset( $s['labels'] ) && is_array( $s['labels'] ) ? $s['labels'] : array();
		$f = OptionResolver::get_feature_flags();
		$img_atc_on = ! empty( $f[ FeatureFlagsDefaults::KEY_PRODUCT_IMAGE_ADD_TO_CART ] );

		echo '<h2>' . esc_html__( 'Каталог', 'mp-sticky-custom-cart' ) . '</h2>';
		self::render_catalog_impact_notes();

		$behavior = isset( $c['image_click_behavior'] ) ? (string) $c['image_click_behavior'] : 'add_to_cart';
		$surface  = isset( $c['catalog_add_surface'] ) ? (string) $c['catalog_add_surface'] : 'image_click';
		if ( ! in_array( $surface, array( 'image_click', 'cart_icon' ), true ) ) {
			$surface = 'image_click';
		}
		if ( ! $img_atc_on && ( 'cart_icon' === $surface || ( 'add_to_cart' === $behavior && 'image_click' === $surface ) ) ) {
			echo '<div class="notice notice-warning inline"><p>';
			echo esc_html__( 'На вкладке «Служебное» выключен feature flag «Клик по изображению добавляет в корзину» — без него не работает AJAX-добавление из каталога (ни по миниатюре, ни по иконке корзины).', 'mp-sticky-custom-cart' );
			echo '</p></div>';
		}

		echo '<h3>' . esc_html__( 'Режим добавления в каталоге', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Мягкая миграция: переключатель ниже меняет только способ добавления из карточки лупы. Старый сценарий — клик по миниатюре; новый — отдельная иконка (картинка ведёт на страницу товара, как в теме).', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_select(
			$opt,
			'catalog',
			'catalog_add_surface',
			__( 'Способ добавления в корзину из карточки', 'mp-sticky-custom-cart' ),
			$surface,
			array(
				'image_click' => __( 'Устар.: по клику на миниатюру (перехват изображения)', 'mp-sticky-custom-cart' ),
				'cart_icon'   => __( 'Иконка корзины (рекомендуется)', 'mp-sticky-custom-cart' ),
			),
			__( 'Один и тот же AJAX. В режиме иконки клик по фото не добавляет в корзину — переход по ссылке темы / Woo.', 'mp-sticky-custom-cart' )
		);
		if ( 'cart_icon' === $surface ) {
			$icon_desk = isset( $c['catalog_cart_icon_desktop'] ) ? (string) $c['catalog_cart_icon_desktop'] : 'hover';
			if ( ! in_array( $icon_desk, array( 'hover', 'always' ), true ) ) {
				$icon_desk = 'hover';
			}
			$icon_touch = isset( $c['catalog_cart_icon_touch'] ) ? (string) $c['catalog_cart_icon_touch'] : 'always';
			if ( ! in_array( $icon_touch, array( 'always', 'tap_reveal' ), true ) ) {
				$icon_touch = 'always';
			}
			self::field_select(
				$opt,
				'catalog',
				'catalog_cart_icon_desktop',
				__( 'Иконка корзины: десктоп', 'mp-sticky-custom-cart' ),
				$icon_desk,
				array(
					'hover'   => __( 'Показывать при наведении на карточку', 'mp-sticky-custom-cart' ),
					'always'  => __( 'Всегда видна', 'mp-sticky-custom-cart' ),
				),
				__( 'На широкой витрине без coarse pointer. Фокус клавиатуры на карточке тоже открывает иконку (focus-within).', 'mp-sticky-custom-cart' )
			);
			self::field_select(
				$opt,
				'catalog',
				'catalog_cart_icon_touch',
				__( 'Иконка корзины: тач / узкий экран', 'mp-sticky-custom-cart' ),
				$icon_touch,
				array(
					'always'     => __( 'Всегда видна', 'mp-sticky-custom-cart' ),
					'tap_reveal' => __( 'Показать после тапа по карточке (не по ссылке/кнопке)', 'mp-sticky-custom-cart' ),
				),
				__( 'Срабатывает при max-width 768px или pointer: coarse. Тап по ссылке на товар не открывает иконку.', 'mp-sticky-custom-cart' )
			);
			$icon_mobile = isset( $c['catalog_cart_icon_mobile_mode'] ) ? (string) $c['catalog_cart_icon_mobile_mode'] : 'inherit';
			if ( ! in_array( $icon_mobile, array( 'inherit', 'force_visible' ), true ) ) {
				$icon_mobile = 'inherit';
			}
			self::field_select(
				$opt,
				'catalog',
				'catalog_cart_icon_mobile_mode',
				__( 'Мобильный режим иконки', 'mp-sticky-custom-cart' ),
				$icon_mobile,
				array(
					'inherit'        => __( 'Как настройки «тач / узкий экран» выше', 'mp-sticky-custom-cart' ),
					'force_visible'  => __( 'Всегда показывать иконку на таче/узком экране (игнор «тап для показа»)', 'mp-sticky-custom-cart' ),
				),
				__( 'Удобно, если включён «тап для показа», но нужно всегда видеть кнопку на телефоне.', 'mp-sticky-custom-cart' )
			);

			$appearance = isset( $c['catalog_cart_icon_appearance_preset'] ) ? (string) $c['catalog_cart_icon_appearance_preset'] : CatalogCartIconAppearance::PRESET_BLACK_CART_WHITE_BG;
			$appearance = CatalogCartIconAppearance::normalize_preset( $appearance );
			self::field_select(
				$opt,
				'catalog',
				'catalog_cart_icon_appearance_preset',
				__( 'Стиль кнопки с иконкой', 'mp-sticky-custom-cart' ),
				$appearance,
				array(
					CatalogCartIconAppearance::PRESET_BLACK_CART_WHITE_BG => __( 'Тёмная корзина, светлая кнопка (классика)', 'mp-sticky-custom-cart' ),
					CatalogCartIconAppearance::PRESET_WHITE_CART_BLACK_BG => __( 'Светлая корзина, тёмная кнопка', 'mp-sticky-custom-cart' ),
				),
				__( 'Два готовых сочетания цвета значка и подложки. Сохраните настройки и обновите витрину (кэш темы/плагина при необходимости).', 'mp-sticky-custom-cart' )
			);

			$icon_preset = isset( $c['catalog_cart_icon_preset'] ) ? (string) $c['catalog_cart_icon_preset'] : CatalogCartIconPresets::DEFAULT;
			self::field_catalog_cart_icon_preset_grid( $opt, $icon_preset );

			$o_top = isset( $c['catalog_cart_icon_offset_top_px'] ) ? (int) $c['catalog_cart_icon_offset_top_px'] : 8;
			$o_left = isset( $c['catalog_cart_icon_offset_left_px'] ) ? (int) $c['catalog_cart_icon_offset_left_px'] : 8;
			$hit_sz = isset( $c['catalog_cart_icon_hit_size_px'] ) ? (int) $c['catalog_cart_icon_hit_size_px'] : 36;
			$glyph_sz = isset( $c['catalog_cart_icon_glyph_size_px'] ) ? (int) $c['catalog_cart_icon_glyph_size_px'] : 20;
			$icon_stroke = isset( $c['catalog_cart_icon_stroke_width'] ) ? (float) $c['catalog_cart_icon_stroke_width'] : 1.75;
			if ( $icon_stroke < 1.0 || $icon_stroke > 3.0 ) {
				$icon_stroke = 1.75;
			}
			$bg_radius = isset( $c['catalog_cart_icon_bg_border_radius_px'] ) ? (int) $c['catalog_cart_icon_bg_border_radius_px'] : 10;
			if ( $bg_radius < 0 || $bg_radius > 28 ) {
				$bg_radius = 10;
			}
			$inner_pad = isset( $c['catalog_cart_icon_inner_padding_px'] ) ? (int) $c['catalog_cart_icon_inner_padding_px'] : 0;
			if ( $inner_pad < 0 || $inner_pad > 12 ) {
				$inner_pad = 0;
			}
			$delay_ms = isset( $c['catalog_cart_icon_transition_delay_ms'] ) ? (int) $c['catalog_cart_icon_transition_delay_ms'] : 0;

			echo '<tr><td colspan="2"><p class="description"><strong>' . esc_html__( 'Геометрия и задержка иконки', 'mp-sticky-custom-cart' ) . '</strong> — ';
			echo esc_html__( 'отступы от угла первой миниатюры в карточке, размер кнопки и глифа, задержка перед анимацией появления. Попадают в CSS-переменные --mp-scc-catalog-cart-icon-* и в JS-позиционирование.', 'mp-sticky-custom-cart' );
			echo '</p></td></tr>';

			self::field_number(
				$opt,
				'catalog',
				'catalog_cart_icon_offset_top_px',
				__( 'Отступ иконки сверху (px)', 'mp-sticky-custom-cart' ),
				$o_top,
				__( 'От верхнего края области изображения товара в лупе (0–64).', 'mp-sticky-custom-cart' )
			);
			self::field_number(
				$opt,
				'catalog',
				'catalog_cart_icon_offset_left_px',
				__( 'Отступ иконки слева (px)', 'mp-sticky-custom-cart' ),
				$o_left,
				__( 'От левого края области изображения (0–64).', 'mp-sticky-custom-cart' )
			);
			self::field_number(
				$opt,
				'catalog',
				'catalog_cart_icon_hit_size_px',
				__( 'Размер кликабельной зоны (px)', 'mp-sticky-custom-cart' ),
				$hit_sz,
				__( 'Квадратная кнопка: 28–56 px (удобство нажатия).', 'mp-sticky-custom-cart' )
			);
			self::field_number(
				$opt,
				'catalog',
				'catalog_cart_icon_glyph_size_px',
				__( 'Размер значка корзины (px)', 'mp-sticky-custom-cart' ),
				$glyph_sz,
				__( 'Ширина/высота SVG внутри кнопки (14–28).', 'mp-sticky-custom-cart' )
			);
			self::field_number(
				$opt,
				'catalog',
				'catalog_cart_icon_stroke_width',
				__( 'Толщина линии значка', 'mp-sticky-custom-cart' ),
				$icon_stroke,
				__( 'Толщина обводки для контурных элементов (1–3; по умолчанию 1.75). У залитых кругов-колёс масштаб не меняется.', 'mp-sticky-custom-cart' ),
				array(
					'min'  => 1,
					'max'  => 3,
					'step' => '0.05',
				)
			);
			self::field_number(
				$opt,
				'catalog',
				'catalog_cart_icon_bg_border_radius_px',
				__( 'Скругление фона кнопки (px)', 'mp-sticky-custom-cart' ),
				$bg_radius,
				__( 'Радиус углов подложки иконки (квадрат клика). 0 — без скругления; по умолчанию 10.', 'mp-sticky-custom-cart' ),
				array(
					'min'  => 0,
					'max'  => 28,
					'step' => 1,
				)
			);
			self::field_number(
				$opt,
				'catalog',
				'catalog_cart_icon_inner_padding_px',
				__( 'Внутренний отступ значка (px)', 'mp-sticky-custom-cart' ),
				$inner_pad,
				__( 'Одинаковый отступ глифа от краёв кнопки со всех сторон (0–12). Увеличивает «воздух» вокруг SVG внутри фона.', 'mp-sticky-custom-cart' ),
				array(
					'min'  => 0,
					'max'  => 12,
					'step' => 1,
				)
			);
			self::field_number(
				$opt,
				'catalog',
				'catalog_cart_icon_transition_delay_ms',
				__( 'Задержка появления (мс)', 'mp-sticky-custom-cart' ),
				$delay_ms,
				__( 'Пауза перед transition opacity/visibility слота (0–2000). Полезно вместе с режимом «по наведению».', 'mp-sticky-custom-cart' )
			);
		}
		self::field_select(
			$opt,
			'catalog',
			'image_click_behavior',
			__( 'Поведение клика по миниатюре', 'mp-sticky-custom-cart' ),
			in_array( $behavior, array( 'add_to_cart', 'theme_default' ), true ) ? $behavior : 'add_to_cart',
			array(
				'add_to_cart'    => __( 'Добавить в корзину (AJAX, при включённом feature flag)', 'mp-sticky-custom-cart' ),
				'theme_default' => __( 'Как в теме / Woo (ссылки и стандартное поведение)', 'mp-sticky-custom-cart' ),
			),
			__( 'При «Добавить в корзину» клик перехватывается для img по селектору ниже. «Как в теме» отключает скрипт плагина для картинки.', 'mp-sticky-custom-cart' )
		);
		echo '<tr><td colspan="2"><p class="description">' . esc_html__( 'Если селектор пустой, на витрине подставляется стандартный селектор миниатюры WooCommerce.', 'mp-sticky-custom-cart' ) . '</p></td></tr>';
		self::field_text(
			$opt,
			'catalog',
			'image_click_selector',
			__( 'Селектор изображения карточки (CSS)', 'mp-sticky-custom-cart' ),
			isset( $c['image_click_selector'] ) ? (string) $c['image_click_selector'] : '',
			__( 'Пустое значение: используется встроенный селектор Woo для миниатюры в лупе. Укажите свой, если тема меняет разметку.', 'mp-sticky-custom-cart' )
		);
		self::field_text(
			$opt,
			'catalog',
			'card_root_selector',
			__( 'Корень карточки для состояний (closest, CSS)', 'mp-sticky-custom-cart' ),
			isset( $c['card_root_selector'] ) ? (string) $c['card_root_selector'] : 'li.product',
			__( 'Предок товара для классов «загрузка / добавлено / ошибка»; обычно li.product или кастомный контейнер темы.', 'mp-sticky-custom-cart' )
		);
		echo '<tr><td colspan="2"><p class="description">';
		echo esc_html(
			__( 'Вариант «обёртка карточки в одну ссылку» из советов для functions.php: он работает только в стандартном цикле WooCommerce (хуки woocommerce_before_shop_loop_item). Виджеты Elementor / Liquid (ld_woo_products_list и т.п.) эти хуки не вызывают — там разметку нужно менять в шаблоне виджета или в дочерней теме. Вложенные ссылки внутри обёртки (заголовок, кнопки) дают невалидный HTML и ломают вёрстку в части темах.', 'mp-sticky-custom-cart' )
		);
		echo '</p></td></tr>';
		self::field_checkbox(
			$opt,
			'catalog',
			'wrap_loop_item_add_to_cart',
			__( 'Эксперимент: одна ссылка на карточку в стандартном цикле Woo (как в functions.php)', 'mp-sticky-custom-cart' ),
			! empty( $c['wrap_loop_item_add_to_cart'] )
		);
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Кнопка «Подробнее» и анимация hover', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_checkbox( $opt, 'catalog', 'hover_overlay_mobile_always', __( 'Показывать кнопку «Подробнее» на мобильных всегда', 'mp-sticky-custom-cart' ), ! empty( $c['hover_overlay_mobile_always'] ) );
		self::field_number( $opt, 'catalog', 'hover_animation_duration_ms', __( 'Длительность анимации hover (мс)', 'mp-sticky-custom-cart' ), isset( $c['hover_animation_duration_ms'] ) ? (int) $c['hover_animation_duration_ms'] : 220 );
		self::field_text(
			$opt,
			'catalog',
			'hover_animation_easing',
			__( 'Easing (CSS)', 'mp-sticky-custom-cart' ),
			isset( $c['hover_animation_easing'] ) ? (string) $c['hover_animation_easing'] : '',
			__( 'Допустимы буквы, цифры, пробелы и символы для cubic-bezier() / ease / linear. Опасные символы отбрасываются при сохранении.', 'mp-sticky-custom-cart' )
		);
		self::field_select(
			$opt,
			'catalog',
			'hover_motion_preset',
			__( 'Тип / пресет анимации', 'mp-sticky-custom-cart' ),
			isset( $c['hover_motion_preset'] ) ? (string) $c['hover_motion_preset'] : 'fade_slide',
			array(
				'fade_slide' => 'fade + slide',
				'fade'       => 'fade',
				'slide'      => 'slide',
			),
			__( 'Комбинация fade и сдвига для overlay «Подробнее» в каталоге.', 'mp-sticky-custom-cart' )
		);
		self::field_number( $opt, 'catalog', 'hover_slide_offset_px', __( 'Смещение slide (px)', 'mp-sticky-custom-cart' ), isset( $c['hover_slide_offset_px'] ) ? (int) $c['hover_slide_offset_px'] : 8 );
		self::field_number( $opt, 'catalog', 'hover_hide_delay_ms', __( 'Задержка перед скрытием overlay (мс)', 'mp-sticky-custom-cart' ), isset( $c['hover_hide_delay_ms'] ) ? (int) $c['hover_hide_delay_ms'] : 50 );
		self::field_checkbox( $opt, 'catalog', 'more_info_new_tab', __( 'Открывать «Подробнее» в новой вкладке', 'mp-sticky-custom-cart' ), ! empty( $c['more_info_new_tab'] ) );
		self::field_number(
			$opt,
			'catalog',
			'catalog_overlay_z_index',
			__( 'Z-index слоя «Подробнее»', 'mp-sticky-custom-cart' ),
			isset( $c['catalog_overlay_z_index'] ) ? (int) $c['catalog_overlay_z_index'] : 4,
			__( 'Слой должен быть выше изображения карточки и ниже иконки избранного (см. вкладку «Избранное»).', 'mp-sticky-custom-cart' )
		);
		echo '</tbody></table>';

		self::render_catalog_card_preview( $s, $c );

		echo '<h3>' . esc_html__( 'Тексты интерфейса', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Пустое поле на сайте заменяется стандартной фразой из плагина. HTML удаляется при сохранении; не более 500 символов для полей каталога ниже.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_text(
			$opt,
			'labels',
			'catalog_cart_icon',
			__( 'Подпись кнопки-иконки корзины в лупе (aria)', 'mp-sticky-custom-cart' ),
			isset( $l['catalog_cart_icon'] ) ? (string) $l['catalog_cart_icon'] : '',
			__( 'Используется при режиме «иконка корзины»; пустое значение — стандартная фраза плагина.', 'mp-sticky-custom-cart' ),
			array( 'maxlength' => 500 )
		);
		self::field_text(
			$opt,
			'labels',
			'more_info',
			__( 'Текст кнопки «Подробнее о товаре»', 'mp-sticky-custom-cart' ),
			isset( $l['more_info'] ) ? (string) $l['more_info'] : '',
			__( 'Отображается на полосе поверх миниатюры в каталоге.', 'mp-sticky-custom-cart' ),
			array( 'maxlength' => 500 )
		);
		self::field_text(
			$opt,
			'labels',
			'out_of_stock',
			__( 'Сообщение «Товара нет в наличии»', 'mp-sticky-custom-cart' ),
			isset( $l['out_of_stock'] ) ? (string) $l['out_of_stock'] : '',
			__( 'Тост на карточке при клике по картинке, если товар не в наличии (DOM), и при ответе сервера out_of_stock.', 'mp-sticky-custom-cart' ),
			array( 'maxlength' => 500 )
		);
		self::field_text( $opt, 'labels', 'clear_cart', __( 'Текст кнопки «Очистить корзину»', 'mp-sticky-custom-cart' ), isset( $l['clear_cart'] ) ? (string) $l['clear_cart'] : '' );
		self::field_text( $opt, 'labels', 'clear_cart_in_progress', __( 'Текст при очистке (иконки, aria-busy)', 'mp-sticky-custom-cart' ), isset( $l['clear_cart_in_progress'] ) ? (string) $l['clear_cart_in_progress'] : '' );
		self::field_text( $opt, 'labels', 'cart_cleared', __( 'Сообщение после очистки корзины', 'mp-sticky-custom-cart' ), isset( $l['cart_cleared'] ) ? (string) $l['cart_cleared'] : '' );
		self::field_text( $opt, 'labels', 'checkout', __( 'Текст кнопки «Оформить заказ»', 'mp-sticky-custom-cart' ), isset( $l['checkout'] ) ? (string) $l['checkout'] : '' );
		self::field_text( $opt, 'labels', 'variation_required', __( 'Сообщение «Выберите вариацию товара»', 'mp-sticky-custom-cart' ), isset( $l['variation_required'] ) ? (string) $l['variation_required'] : '' );
		self::field_text( $opt, 'labels', 'single_add_success', __( 'Сообщение после добавления со страницы товара', 'mp-sticky-custom-cart' ), isset( $l['single_add_success'] ) ? (string) $l['single_add_success'] : '' );
		self::field_text( $opt, 'labels', 'drawer_empty', __( 'Пустая корзина (drawer)', 'mp-sticky-custom-cart' ), isset( $l['drawer_empty'] ) ? (string) $l['drawer_empty'] : '' );
		self::field_text( $opt, 'labels', 'drawer_empty_hint', __( 'Подсказка под пустой корзиной (drawer)', 'mp-sticky-custom-cart' ), isset( $l['drawer_empty_hint'] ) ? (string) $l['drawer_empty_hint'] : '' );
		self::field_text( $opt, 'labels', 'drawer_remove_line', __( 'Кнопка удаления позиции (aria)', 'mp-sticky-custom-cart' ), isset( $l['drawer_remove_line'] ) ? (string) $l['drawer_remove_line'] : '' );
		self::field_text( $opt, 'labels', 'line_removed', __( 'Сообщение после удаления позиции', 'mp-sticky-custom-cart' ), isset( $l['line_removed'] ) ? (string) $l['line_removed'] : '' );
		echo '</tbody></table>';

		self::render_labels_preview_panel();
		self::render_drawer_empty_preview();
	}

	/**
	 * @param array<string, mixed> $s
	 * @param string               $opt
	 */
	private static function render_sticky_impact_notes() {
		echo '<div class="mp-scc-sticky-impact-notes">';
		echo '<p><strong>' . esc_html__( 'Влияние на витрину', 'mp-sticky-custom-cart' ) . '</strong></p>';
		echo '<ul class="ul-disc">';
		echo '<li>' . esc_html__( 'Числа и цвета (вкладка «Стили») превращаются в --mp-scc-* и сразу меняют нижнюю панель и drawer.', 'mp-sticky-custom-cart' ) . '</li>';
		echo '<li>' . esc_html__( 'Отступы и зазоры задают сетку на мобильных (колонка) и от ~783px (ряд summary + кнопки).', 'mp-sticky-custom-cart' ) . '</li>';
		echo '<li>' . esc_html__( 'Макс. высота drawer и внутренние отступы ограничивают список позиций: при переполнении появляется прокрутка внутри drawer.', 'mp-sticky-custom-cart' ) . '</li>';
		echo '<li>' . esc_html__( 'Подписи «Очистить» и «Оформить заказ» задаются на вкладке «Каталог» → тексты интерфейса.', 'mp-sticky-custom-cart' ) . '</li>';
		echo '</ul></div>';
	}

	/**
	 * Notes for the Styles tab (live preview + tokens).
	 */
	private static function render_styles_impact_notes() {
		echo '<div class="mp-scc-styles-impact-notes">';
		echo '<p><strong>' . esc_html__( 'Как устроено', 'mp-sticky-custom-cart' ) . '</strong></p>';
		echo '<ul class="ul-disc">';
		echo '<li>' . esc_html__( 'Поля ниже задают палитру, «стекло», типографику и отступы; значения сохраняются в настройках и попадают в --mp-scc-* на витрине.', 'mp-sticky-custom-cart' ) . '</li>';
		echo '<li>' . esc_html__( 'Предпросмотр обновляется при вводе (до нажатия «Сохранить»); кнопка «Сбросить предпросмотр» возвращает макет к значениям полей на момент загрузки страницы.', 'mp-sticky-custom-cart' ) . '</li>';
		echo '<li>' . esc_html__( '«Мобильная ширина» сужает блок предпросмотра и включает колоночную сетку, близкую к узкому экрану.', 'mp-sticky-custom-cart' ) . '</li>';
		echo '</ul></div>';
	}

	/**
	 * Toolbar: mobile preview toggle + revert preview to loaded field values.
	 */
	private static function render_style_preview_toolbar() {
		echo '<div class="mp-scc-style-preview-toolbar">';
		echo '<button type="button" class="button" id="mp-scc-style-preview-mobile" aria-pressed="false">';
		echo esc_html__( 'Мобильная ширина', 'mp-sticky-custom-cart' );
		echo '</button> ';
		echo '<button type="button" class="button" id="mp-scc-style-preview-revert">';
		echo esc_html__( 'Сбросить предпросмотр', 'mp-sticky-custom-cart' );
		echo '</button>';
		echo '<p class="description mp-scc-style-preview-toolbar__hint">';
		echo esc_html__( 'Предпросмотр не сохраняет настройки. Сохранение — кнопка внизу страницы.', 'mp-sticky-custom-cart' );
		echo '</p></div>';
	}

	/**
	 * Mini sticky bar using runtime CSS variables (approximate storefront look).
	 *
	 * @param array<string, mixed> $s            Full settings.
	 * @param string               $wrapper_id   Optional id on the outer preview wrapper (live preview JS).
	 * @param bool                 $with_heading Show title and description (Cart tab used to; Styles tab uses its own heading).
	 */
	private static function render_sticky_cart_preview( array $s, $wrapper_id = '', $with_heading = true ) {
		$labels = OptionResolver::get_labels();
		$clear  = isset( $labels[ UiLabelsDefaults::KEY_CLEAR_CART ] ) ? (string) $labels[ UiLabelsDefaults::KEY_CLEAR_CART ] : __( 'Очистить', 'mp-sticky-custom-cart' );
		$co     = isset( $labels[ UiLabelsDefaults::KEY_CHECKOUT ] ) ? (string) $labels[ UiLabelsDefaults::KEY_CHECKOUT ] : __( 'Оформить', 'mp-sticky-custom-cart' );

		$props   = CssVariablesContract::build_properties( $s );
		$preview = array();
		foreach ( $props as $name => $value ) {
			if ( 0 === strpos( $name, CssVariablesContract::PREFIX . 'catalog-' ) ) {
				continue;
			}
			if ( 0 === strpos( $name, CssVariablesContract::PREFIX . 'wishlist-' ) ) {
				continue;
			}
			$preview[ $name ] = $value;
		}
		$style = '';
		foreach ( $preview as $name => $value ) {
			$style .= $name . ':' . $value . ';';
		}

		if ( $with_heading ) {
			echo '<h3>' . esc_html__( 'Предпросмотр панели', 'mp-sticky-custom-cart' ) . '</h3>';
			echo '<p class="description">' . esc_html__( 'Упрощённый макет без реального drawer; масштаб уменьшен. Полный набор внешнего вида — вкладка «Стили».', 'mp-sticky-custom-cart' ) . '</p>';
		}

		$outer = '<div class="mp-scc-admin-sticky-preview"';
		if ( is_string( $wrapper_id ) && '' !== $wrapper_id ) {
			$outer .= ' id="' . esc_attr( $wrapper_id ) . '"';
			$outer .= ' data-mp-scc-preview-baseline="' . esc_attr( $style ) . '"';
		}
		$outer .= ' style="' . esc_attr( $style ) . '">';
		echo $outer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- assembled with esc_attr
		echo '<div class="mp-scc-admin-sticky-preview__bar mp-scc-root">';
		echo '<div class="mp-scc-sticky-inner">';
		echo '<section class="mp-scc-sticky-summary" aria-hidden="true">';
		echo '<span class="mp-scc-admin-sticky-preview__chev" aria-hidden="true"></span>';
		echo '<div class="mp-scc-sticky-summary-text">';
		echo '<span class="mp-scc-cart-count">2</span>';
		echo '<span class="mp-scc-cart-total"><span class="woocommerce-Price-amount amount">2&nbsp;560&nbsp;<span class="woocommerce-Price-currencySymbol">₽</span></span></span>';
		echo '</div></section>';
		echo '<div class="mp-scc-sticky-actions">';
		echo '<button type="button" class="mp-scc-btn mp-scc-btn--ghost mp-scc-clear-cart" disabled>' . esc_html( $clear ) . '</button>';
		echo '<span class="mp-scc-btn mp-scc-btn--primary mp-scc-checkout">' . esc_html( $co ) . '</span>';
		echo '</div></div></div></div>';
	}

	private static function render_cart_tab( array $s, $opt ) {
		$c  = isset( $s['sticky_cart'] ) && is_array( $s['sticky_cart'] ) ? $s['sticky_cart'] : array();
		$cr = isset( $s['cart_route'] ) && is_array( $s['cart_route'] ) ? $s['cart_route'] : array();
		$n  = isset( $s['notices'] ) && is_array( $s['notices'] ) ? $s['notices'] : array();
		echo '<h2>' . esc_html__( 'Нижняя корзина (sticky)', 'mp-sticky-custom-cart' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Поведение drawer, стекинг и маршруты. Внешний вид (цвета, отступы, типографика) — вкладка «Стили».', 'mp-sticky-custom-cart' ) . '</p>';
		self::render_sticky_impact_notes();

		echo '<h3>' . esc_html__( 'Стекинг панели', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number(
			$opt,
			'sticky_cart',
			'z_index',
			__( 'z-index панели', 'mp-sticky-custom-cart' ),
			isset( $c['z_index'] ) ? (int) $c['z_index'] : 100050,
			__( 'Панель должна быть поверх контента, но не перекрывать важные модальные окна темы; при конфликте уменьшите или увеличьте значение.', 'mp-sticky-custom-cart' )
		);
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Drawer и количество: анимация и debounce', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'drawer_toggle_duration_ms', __( 'Длительность анимации открытия/закрытия (мс)', 'mp-sticky-custom-cart' ), isset( $c['drawer_toggle_duration_ms'] ) ? (int) $c['drawer_toggle_duration_ms'] : 260 );
		self::field_text(
			$opt,
			'sticky_cart',
			'drawer_toggle_easing',
			__( 'Easing анимации drawer (CSS)', 'mp-sticky-custom-cart' ),
			isset( $c['drawer_toggle_easing'] ) ? (string) $c['drawer_toggle_easing'] : 'cubic-bezier(0.4, 0, 0.2, 1)',
			__( 'Только безопасные символы для transition-timing-function.', 'mp-sticky-custom-cart' )
		);
		self::field_number( $opt, 'sticky_cart', 'quantity_debounce_ms', __( 'Debounce изменения количества в списке (мс)', 'mp-sticky-custom-cart' ), isset( $c['quantity_debounce_ms'] ) ? (int) $c['quantity_debounce_ms'] : 320 );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Видимость плавающей корзины', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'CQ 91–93: политика показа состояния A (FAB) при непустой корзине и список URL-исключений. По умолчанию корзина показывается на всех шаблонах витрины.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_checkbox(
			$opt,
			'sticky_cart',
			'visibility_show_on_all_templates',
			__( 'Показывать на всех шаблонах при непустой корзине', 'mp-sticky-custom-cart' ),
			! isset( $c['visibility_show_on_all_templates'] ) || ! empty( $c['visibility_show_on_all_templates'] ),
			__( 'Если выключить, sticky будет выводиться только на Woo-шаблонах (shop/product/cart/checkout/account и связанных endpoint).', 'mp-sticky-custom-cart' )
		);
		self::field_textarea(
			$opt,
			'sticky_cart',
			'visibility_excluded_urls',
			__( 'URL-исключения (по одному на строку)', 'mp-sticky-custom-cart' ),
			isset( $c['visibility_excluded_urls'] ) ? (string) $c['visibility_excluded_urls'] : '',
			__(
				"Примеры: /checkout/*, /cart/*, /my-account/orders/*, https://example.com/landing. Поддерживается '*' как wildcard; строки с # в начале игнорируются.",
				'mp-sticky-custom-cart'
			),
			array(
				'rows'      => 6,
				'maxlength' => 4000,
			)
		);
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Маршрут страницы корзины WooCommerce', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Редирект URL страницы корзины (например /cart/) на главную сайта. Не включайте, если страница корзины задана как главная или нужны ссылки с параметрами удаления позиций.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_checkbox(
			$opt,
			'cart_route',
			'redirect_to_home',
			__( 'Редирект страницы корзины на главную', 'mp-sticky-custom-cart' ),
			! empty( $cr['redirect_to_home'] ),
			__( 'Не включайте, если страница корзины = главная или нужны прямые ссылки с параметрами удаления позиций с URL корзины.', 'mp-sticky-custom-cart' )
		);
		$status = isset( $cr['redirect_status_code'] ) ? (int) $cr['redirect_status_code'] : 302;
		self::field_select(
			$opt,
			'cart_route',
			'redirect_status_code',
			__( 'HTTP-код редиректа', 'mp-sticky-custom-cart' ),
			(string) $status,
			array(
				'301' => '301 ' . __( 'Moved Permanently', 'mp-sticky-custom-cart' ),
				'302' => '302 ' . __( 'Found', 'mp-sticky-custom-cart' ),
				'303' => '303 ' . __( 'See Other', 'mp-sticky-custom-cart' ),
				'307' => '307 ' . __( 'Temporary Redirect', 'mp-sticky-custom-cart' ),
			),
			__( '302/303/307 — для временного поведения; 301 кешируется агрессивнее и реже подходит для «скрытой» корзины.', 'mp-sticky-custom-cart' )
		);
		self::field_checkbox(
			$opt,
			'cart_route',
			'log_redirect_events',
			__( 'Писать события редиректа в debug.log', 'mp-sticky-custom-cart' ),
			! empty( $cr['log_redirect_events'] )
		);
		self::field_checkbox(
			$opt,
			'cart_route',
			'preserve_marketing_params_on_redirect',
			__( 'Сохранять UTM/рекламные параметры при редиректе на главную', 'mp-sticky-custom-cart' ),
			! isset( $cr['preserve_marketing_params_on_redirect'] ) || ! empty( $cr['preserve_marketing_params_on_redirect'] )
		);
		self::field_checkbox(
			$opt,
			'cart_route',
			'track_external_cart_link_hits',
			__( 'Считать заходы на /cart/ с ?add-to-cart в URL', 'mp-sticky-custom-cart' ),
			! empty( $cr['track_external_cart_link_hits'] )
		);
		$hits = (int) get_option( Constants::OPTION_EXTERNAL_CART_LINK_HITS, 0 );
		echo '<tr><th scope="row">' . esc_html__( 'Счётчик add-to-cart на странице корзины', 'mp-sticky-custom-cart' ) . '</th><td>';
		echo '<p class="description">' . esc_html( (string) $hits ) . '</p>';
		echo '<p class="description">' . esc_html__( 'Увеличивается при включённом редиректе и учёте, если в запросе был параметр add-to-cart (типичные внешние ссылки).', 'mp-sticky-custom-cart' ) . '</p>';
		echo '</td></tr>';
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Уведомления после добавления в корзину', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'WooCommerce показывает ссылку «View cart» в тексте успеха. Для нижней корзины её обычно скрывают.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_checkbox(
			$opt,
			'notices',
			'remove_view_cart_link',
			__( 'Убрать ссылку «View cart» из уведомления', 'mp-sticky-custom-cart' ),
			! isset( $n['remove_view_cart_link'] ) || ! empty( $n['remove_view_cart_link'] )
		);
		echo '</tbody></table>';

		echo '<p class="description">' . esc_html__( 'Анимация карточки каталога (hover) настраивается на вкладке «Каталог».', 'mp-sticky-custom-cart' ) . '</p>';
	}

	/**
	 * @param array<string, mixed> $s
	 * @param string               $opt
	 */
	private static function render_wishlist_tab( array $s, $opt ) {
		$c = isset( $s['wishlist_ui'] ) && is_array( $s['wishlist_ui'] ) ? $s['wishlist_ui'] : array();
		echo '<h2>' . esc_html__( 'Избранное (UI)', 'mp-sticky-custom-cart' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'wishlist_ui', 'heart_reserve_top_px', __( 'Отступ сердечка сверху (px)', 'mp-sticky-custom-cart' ), isset( $c['heart_reserve_top_px'] ) ? (int) $c['heart_reserve_top_px'] : 10 );
		self::field_number( $opt, 'wishlist_ui', 'heart_reserve_right_px', __( 'Отступ сердечка справа (px)', 'mp-sticky-custom-cart' ), isset( $c['heart_reserve_right_px'] ) ? (int) $c['heart_reserve_right_px'] : 10 );
		self::field_number( $opt, 'wishlist_ui', 'overlay_clearance_heart_px', __( 'Зазор overlay от сердечка (px)', 'mp-sticky-custom-cart' ), isset( $c['overlay_clearance_heart_px'] ) ? (int) $c['overlay_clearance_heart_px'] : 8 );
		self::field_number(
			$opt,
			'wishlist_ui',
			'heart_icon_z_index',
			__( 'Z-index иконки избранного (выше overlay)', 'mp-sticky-custom-cart' ),
			isset( $c['heart_icon_z_index'] ) ? (int) $c['heart_icon_z_index'] : 6,
			__( 'Должен быть выше z-index overlay «Подробнее» на вкладке «Каталог», иначе сердечко уйдёт под слой.', 'mp-sticky-custom-cart' )
		);
		echo '</tbody></table>';
		echo '<p class="description">' . esc_html__( 'Рекомендуется: z-index избранного больше, чем у overlay «Подробнее». Подробности — docs/wishlist-integration.md в каталоге плагина.', 'mp-sticky-custom-cart' ) . '</p>';
	}

	/**
	 * @param array<string, mixed> $s
	 * @param string               $opt
	 */
	private static function render_styles_tab( array $s, $opt ) {
		$st = isset( $s['styles'] ) && is_array( $s['styles'] ) ? $s['styles'] : array();
		$c  = isset( $s['sticky_cart'] ) && is_array( $s['sticky_cart'] ) ? $s['sticky_cart'] : array();
		$p  = CssVariablesContract::PREFIX;

		echo '<h2>' . esc_html__( 'Внешний вид нижней панели', 'mp-sticky-custom-cart' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Цвета, «стекло», типографика и отступы попадают в CSS-переменные (--mp-scc-*) на витрине. Предпросмотр ниже обновляется при редактировании (до сохранения).', 'mp-sticky-custom-cart' ) . '</p>';
		self::render_styles_impact_notes();

		echo '<h3>' . esc_html__( 'Живой предпросмотр', 'mp-sticky-custom-cart' ) . '</h3>';
		self::render_style_preview_toolbar();
		self::render_sticky_cart_preview( $s, 'mp-scc-style-live-preview', false );

		echo '<h3>' . esc_html__( 'Текст и подложка нижней полосы', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_color( $opt, 'styles', 'color_text_primary', __( 'Основной цвет текста', 'mp-sticky-custom-cart' ), isset( $st['color_text_primary'] ) ? (string) $st['color_text_primary'] : '#1a1a1a', '', array( 'var' => $p . 'color-text-primary', 'fmt' => 'color' ) );
		self::field_color( $opt, 'styles', 'color_surface_tint', __( 'Нижняя полоса: оттенок подложки (вместе с прозрачностью ниже)', 'mp-sticky-custom-cart' ), isset( $st['color_surface_tint'] ) ? (string) $st['color_surface_tint'] : '#ffffff', '', array( 'var' => $p . 'color-surface-tint', 'fmt' => 'color' ) );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Кнопка «Оформить заказ»', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Обычное состояние и при наведении (курсор на кнопке).', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_color( $opt, 'styles', 'color_button_primary', __( 'Фон', 'mp-sticky-custom-cart' ), isset( $st['color_button_primary'] ) ? (string) $st['color_button_primary'] : '#111111', '', array( 'var' => $p . 'color-button-primary', 'fmt' => 'color' ) );
		self::field_color( $opt, 'styles', 'color_button_primary_text', __( 'Текст', 'mp-sticky-custom-cart' ), isset( $st['color_button_primary_text'] ) ? (string) $st['color_button_primary_text'] : '#ffffff', '', array( 'var' => $p . 'color-button-primary-text', 'fmt' => 'color' ) );
		self::field_color( $opt, 'styles', 'color_button_primary_hover', __( 'Фон при наведении', 'mp-sticky-custom-cart' ), isset( $st['color_button_primary_hover'] ) ? (string) $st['color_button_primary_hover'] : '#333333', '', array( 'var' => $p . 'color-button-primary-hover', 'fmt' => 'color' ) );
		self::field_color( $opt, 'styles', 'color_button_primary_text_hover', __( 'Текст при наведении', 'mp-sticky-custom-cart' ), isset( $st['color_button_primary_text_hover'] ) ? (string) $st['color_button_primary_text_hover'] : '#ffffff', '', array( 'var' => $p . 'color-button-primary-text-hover', 'fmt' => 'color' ) );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Кнопка «Очистить корзину»', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Фон, обводка и текст — в покое и при наведении.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_color( $opt, 'styles', 'color_clear_button_bg', __( 'Фон', 'mp-sticky-custom-cart' ), isset( $st['color_clear_button_bg'] ) ? (string) $st['color_clear_button_bg'] : '#ffffff', '', array( 'var' => $p . 'color-clear-button-bg', 'fmt' => 'color' ) );
		self::field_color( $opt, 'styles', 'color_clear_button_bg_hover', __( 'Фон при наведении', 'mp-sticky-custom-cart' ), isset( $st['color_clear_button_bg_hover'] ) ? (string) $st['color_clear_button_bg_hover'] : '#f0f0f0', '', array( 'var' => $p . 'color-clear-button-bg-hover', 'fmt' => 'color' ) );
		self::field_color( $opt, 'styles', 'color_clear_button_border', __( 'Обводка', 'mp-sticky-custom-cart' ), isset( $st['color_clear_button_border'] ) ? (string) $st['color_clear_button_border'] : '#cfcfcf', '', array( 'var' => $p . 'color-clear-button-border', 'fmt' => 'color' ) );
		self::field_color( $opt, 'styles', 'color_clear_button_border_hover', __( 'Обводка при наведении', 'mp-sticky-custom-cart' ), isset( $st['color_clear_button_border_hover'] ) ? (string) $st['color_clear_button_border_hover'] : '#b0b0b0', '', array( 'var' => $p . 'color-clear-button-border-hover', 'fmt' => 'color' ) );
		self::field_color( $opt, 'styles', 'color_clear_button_text', __( 'Текст', 'mp-sticky-custom-cart' ), isset( $st['color_clear_button_text'] ) ? (string) $st['color_clear_button_text'] : '#1a1a1a', '', array( 'var' => $p . 'color-clear-button-text', 'fmt' => 'color' ) );
		self::field_color( $opt, 'styles', 'color_clear_button_text_hover', __( 'Текст при наведении', 'mp-sticky-custom-cart' ), isset( $st['color_clear_button_text_hover'] ) ? (string) $st['color_clear_button_text_hover'] : '#1a1a1a', '', array( 'var' => $p . 'color-clear-button-text-hover', 'fmt' => 'color' ) );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Раскрывающаяся панель (drawer): подложка', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_color( $opt, 'styles', 'color_drawer_surface_tint', __( 'Оттенок подложки', 'mp-sticky-custom-cart' ), isset( $st['color_drawer_surface_tint'] ) ? (string) $st['color_drawer_surface_tint'] : '#ffffff', '', array( 'var' => $p . 'color-drawer-surface-tint', 'fmt' => 'color' ) );
		self::field_text(
			$opt,
			'sticky_cart',
			'drawer_surface_background_alpha',
			__( 'Прозрачность подложки (0–1)', 'mp-sticky-custom-cart' ),
			isset( $c['drawer_surface_background_alpha'] ) ? (string) $c['drawer_surface_background_alpha'] : '0.94',
			__( 'Независимо от нижней полосы. Вместе с оттенком задаёт «стекло» drawer.', 'mp-sticky-custom-cart' ),
			array(
				'preview' => array(
					'var' => $p . 'sticky-drawer-surface-alpha',
					'fmt' => 'alpha',
				),
			)
		);
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Кнопки количества в списке (+ / − / удалить строку)', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Стили блока +/− и кнопки удаления позиции в drawer — в покое и при наведении.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_color( $opt, 'styles', 'color_qty_button_bg', __( 'Фон', 'mp-sticky-custom-cart' ), isset( $st['color_qty_button_bg'] ) ? (string) $st['color_qty_button_bg'] : '#ffffff', '', array( 'var' => $p . 'color-qty-button-bg', 'fmt' => 'color' ) );
		self::field_color( $opt, 'styles', 'color_qty_button_bg_hover', __( 'Фон при наведении', 'mp-sticky-custom-cart' ), isset( $st['color_qty_button_bg_hover'] ) ? (string) $st['color_qty_button_bg_hover'] : '#ececec', '', array( 'var' => $p . 'color-qty-button-bg-hover', 'fmt' => 'color' ) );
		self::field_color( $opt, 'styles', 'color_qty_button_border', __( 'Обводка', 'mp-sticky-custom-cart' ), isset( $st['color_qty_button_border'] ) ? (string) $st['color_qty_button_border'] : '#cccccc', '', array( 'var' => $p . 'color-qty-button-border', 'fmt' => 'color' ) );
		self::field_color( $opt, 'styles', 'color_qty_button_border_hover', __( 'Обводка при наведении', 'mp-sticky-custom-cart' ), isset( $st['color_qty_button_border_hover'] ) ? (string) $st['color_qty_button_border_hover'] : '#9a9a9a', '', array( 'var' => $p . 'color-qty-button-border-hover', 'fmt' => 'color' ) );
		self::field_color( $opt, 'styles', 'color_qty_button_text', __( 'Текст и символы', 'mp-sticky-custom-cart' ), isset( $st['color_qty_button_text'] ) ? (string) $st['color_qty_button_text'] : '#1a1a1a', '', array( 'var' => $p . 'color-qty-button-text', 'fmt' => 'color' ) );
		self::field_color( $opt, 'styles', 'color_qty_button_text_hover', __( 'Текст при наведении', 'mp-sticky-custom-cart' ), isset( $st['color_qty_button_text_hover'] ) ? (string) $st['color_qty_button_text_hover'] : '#1a1a1a', '', array( 'var' => $p . 'color-qty-button-text-hover', 'fmt' => 'color' ) );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Строка товара в drawer', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_color( $opt, 'styles', 'color_drawer_line_title', __( 'Название: цвет', 'mp-sticky-custom-cart' ), isset( $st['color_drawer_line_title'] ) ? (string) $st['color_drawer_line_title'] : '#1a1a1a', '', array( 'var' => $p . 'color-drawer-line-title', 'fmt' => 'color' ) );
		self::field_number( $opt, 'sticky_cart', 'drawer_line_title_font_size_px', __( 'Название: размер (px)', 'mp-sticky-custom-cart' ), isset( $c['drawer_line_title_font_size_px'] ) ? (int) $c['drawer_line_title_font_size_px'] : 15, '', array( 'var' => $p . 'sticky-drawer-line-title-font-size', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'drawer_line_title_font_weight', __( 'Название: начертание (100–900)', 'mp-sticky-custom-cart' ), isset( $c['drawer_line_title_font_weight'] ) ? (int) $c['drawer_line_title_font_weight'] : 500, '', array( 'var' => $p . 'sticky-drawer-line-title-font-weight', 'fmt' => 'integer' ) );
		self::field_color( $opt, 'styles', 'color_drawer_line_unit', __( 'Подпись (за единицу): цвет', 'mp-sticky-custom-cart' ), isset( $st['color_drawer_line_unit'] ) ? (string) $st['color_drawer_line_unit'] : '#5c5c5c', '', array( 'var' => $p . 'color-drawer-line-unit', 'fmt' => 'color' ) );
		self::field_number( $opt, 'sticky_cart', 'drawer_line_unit_font_size_px', __( 'Подпись: размер (px)', 'mp-sticky-custom-cart' ), isset( $c['drawer_line_unit_font_size_px'] ) ? (int) $c['drawer_line_unit_font_size_px'] : 13, '', array( 'var' => $p . 'sticky-drawer-line-unit-font-size', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_color( $opt, 'styles', 'color_drawer_line_price', __( 'Цена строки: цвет', 'mp-sticky-custom-cart' ), isset( $st['color_drawer_line_price'] ) ? (string) $st['color_drawer_line_price'] : '#1a1a1a', '', array( 'var' => $p . 'color-drawer-line-price', 'fmt' => 'color' ) );
		self::field_number( $opt, 'sticky_cart', 'drawer_line_price_font_size_px', __( 'Цена строки: размер (px)', 'mp-sticky-custom-cart' ), isset( $c['drawer_line_price_font_size_px'] ) ? (int) $c['drawer_line_price_font_size_px'] : 15, '', array( 'var' => $p . 'sticky-drawer-line-price-font-size', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'drawer_line_price_font_weight', __( 'Цена строки: начертание (100–900)', 'mp-sticky-custom-cart' ), isset( $c['drawer_line_price_font_weight'] ) ? (int) $c['drawer_line_price_font_weight'] : 600, '', array( 'var' => $p . 'sticky-drawer-line-price-font-weight', 'fmt' => 'integer' ) );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Шрифт', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Гарнитура применяется к нижней панели и drawer.', 'mp-sticky-custom-cart' ) . '</p>';
		self::render_font_family_examples_line(
			__( 'Свой стек в поле ниже вводите как в CSS: через запятую; имена с пробелами — в двойных кавычках (как у Playfair Display); в конце generic family. Подключите шрифты на сайте (тема, плагин или @font-face). Примеры одной гарнитуры:', 'mp-sticky-custom-cart' )
		);
		echo '<table class="form-table" role="presentation"><tbody>';
		$ff_preset   = isset( $st['font_family_preset'] ) ? (string) $st['font_family_preset'] : 'inherit';
		$ff_custom   = isset( $st['font_family_custom'] ) ? (string) $st['font_family_custom'] : '';
		$opt_name    = Constants::OPTION_SETTINGS;
		$custom_name = $opt_name . '[styles][font_family_custom]';
		self::field_select(
			$opt,
			'styles',
			'font_family_preset',
			__( 'Гарнитура', 'mp-sticky-custom-cart' ),
			$ff_preset,
			array(
				'inherit' => __( 'Как у темы (inherit)', 'mp-sticky-custom-cart' ),
				'system'  => __( 'Системный sans-serif', 'mp-sticky-custom-cart' ),
				'serif'   => __( 'Антиква (serif)', 'mp-sticky-custom-cart' ),
				'mono'    => __( 'Моноширинный', 'mp-sticky-custom-cart' ),
				'custom'  => __( 'Свой стек (поле ниже)', 'mp-sticky-custom-cart' ),
			),
			__(
				'«Как у темы» — наследует шрифт страницы. Пресеты — готовые стопки без файлов. «Свой стек» — ввод в поле ниже.',
				'mp-sticky-custom-cart'
			),
			array(
				'var'        => $p . 'sticky-font-family',
				'fmt'        => 'font_family',
				'customName' => $custom_name,
				'presetName' => $opt_name . '[styles][font_family_preset]',
			)
		);
		self::field_text(
			$opt,
			'styles',
			'font_family_custom',
			__( 'Свой CSS font-family', 'mp-sticky-custom-cart' ),
			$ff_custom,
			__(
				'Только при выборе «Свой стек». Формат как в CSS; примеры см. в абзаце над таблицей.',
				'mp-sticky-custom-cart'
			),
			array(
				'maxlength'   => 500,
				'input_class' => 'large-text code mp-scc-font-family-custom',
			)
		);
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Подложка и эффекты', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'surface_backdrop_blur_px', __( 'Blur подложки (px)', 'mp-sticky-custom-cart' ), isset( $c['surface_backdrop_blur_px'] ) ? (int) $c['surface_backdrop_blur_px'] : 14, '', array( 'var' => $p . 'sticky-backdrop-blur', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_text(
			$opt,
			'sticky_cart',
			'surface_background_alpha',
			__( 'Прозрачность фона (0–1)', 'mp-sticky-custom-cart' ),
			isset( $c['surface_background_alpha'] ) ? (string) $c['surface_background_alpha'] : '0.78',
			__( 'Дробь от 0 до 1; вместе с blur задаёт «стекло». Нечисловое значение заменяется дефолтом (см. предупреждение при сохранении).', 'mp-sticky-custom-cart' ),
			array(
				'preview' => array(
					'var' => $p . 'sticky-surface-alpha',
					'fmt' => 'alpha',
				),
			)
		);
		self::field_number( $opt, 'sticky_cart', 'border_radius_px', __( 'Радиус скругления углов панели (px)', 'mp-sticky-custom-cart' ), isset( $c['border_radius_px'] ) ? (int) $c['border_radius_px'] : 14, '', array( 'var' => $p . 'sticky-border-radius', 'fmt' => 'unit', 'suffix' => 'px' ) );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Отступы панели', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<h4 class="title">' . esc_html__( 'Desktop (от ~783px)', 'mp-sticky-custom-cart' ) . '</h4>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'padding_x_desktop_px', __( 'Горизонтальный отступ (px)', 'mp-sticky-custom-cart' ), isset( $c['padding_x_desktop_px'] ) ? (int) $c['padding_x_desktop_px'] : 20, '', array( 'var' => $p . 'sticky-padding-x-desktop', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'padding_y_desktop_px', __( 'Вертикальный отступ (px)', 'mp-sticky-custom-cart' ), isset( $c['padding_y_desktop_px'] ) ? (int) $c['padding_y_desktop_px'] : 14, '', array( 'var' => $p . 'sticky-padding-y-desktop', 'fmt' => 'unit', 'suffix' => 'px' ) );
		echo '</tbody></table>';
		echo '<h4 class="title">' . esc_html__( 'Mobile', 'mp-sticky-custom-cart' ) . '</h4>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'padding_x_mobile_px', __( 'Горизонтальный отступ (px)', 'mp-sticky-custom-cart' ), isset( $c['padding_x_mobile_px'] ) ? (int) $c['padding_x_mobile_px'] : 14, '', array( 'var' => $p . 'sticky-padding-x-mobile', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'padding_y_mobile_px', __( 'Вертикальный отступ (px)', 'mp-sticky-custom-cart' ), isset( $c['padding_y_mobile_px'] ) ? (int) $c['padding_y_mobile_px'] : 12, '', array( 'var' => $p . 'sticky-padding-y-mobile', 'fmt' => 'unit', 'suffix' => 'px' ) );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Сетка: зазоры между блоками', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'sticky_inner_gap_mobile_px', __( 'Зазор колонки (mobile, px)', 'mp-sticky-custom-cart' ), isset( $c['sticky_inner_gap_mobile_px'] ) ? (int) $c['sticky_inner_gap_mobile_px'] : 10, __( 'Между строкой summary и кнопками в узкой вёрстке.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'sticky-inner-gap-mobile', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'sticky_inner_gap_desktop_row_px', __( 'Зазор ряда desktop (px)', 'mp-sticky-custom-cart' ), isset( $c['sticky_inner_gap_desktop_row_px'] ) ? (int) $c['sticky_inner_gap_desktop_row_px'] : 16, __( 'Строка flex-gap между summary и кнопками.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'sticky-inner-gap-desktop-row', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'sticky_inner_gap_desktop_col_px', __( 'Зазор колонки desktop (px)', 'mp-sticky-custom-cart' ), isset( $c['sticky_inner_gap_desktop_col_px'] ) ? (int) $c['sticky_inner_gap_desktop_col_px'] : 24, __( 'При переносе элементов в одном ряду.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'sticky-inner-gap-desktop-col', 'fmt' => 'unit', 'suffix' => 'px' ) );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Блок summary (счётчик и сумма)', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'summary_font_size_px', __( 'Размер шрифта (px)', 'mp-sticky-custom-cart' ), isset( $c['summary_font_size_px'] ) ? (int) $c['summary_font_size_px'] : 15, '', array( 'var' => $p . 'sticky-summary-font-size', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'summary_font_weight', __( 'Начертание (100–900)', 'mp-sticky-custom-cart' ), isset( $c['summary_font_weight'] ) ? (int) $c['summary_font_weight'] : 600, '', array( 'var' => $p . 'sticky-summary-font-weight', 'fmt' => 'integer' ) );
		self::field_line_height(
			$opt,
			'sticky_cart',
			'summary_line_height',
			__( 'Межстрочный интервал (без единиц)', 'mp-sticky-custom-cart' ),
			isset( $c['summary_line_height'] ) && is_numeric( $c['summary_line_height'] ) ? (float) $c['summary_line_height'] : 1.35,
			__( 'Типично 1.2–1.5.', 'mp-sticky-custom-cart' ),
			array( 'var' => $p . 'sticky-summary-line-height', 'fmt' => 'float' )
		);
		self::field_number( $opt, 'sticky_cart', 'summary_gap_px', __( 'Зазор внутри summary (иконка drawer ↔ текст, px)', 'mp-sticky-custom-cart' ), isset( $c['summary_gap_px'] ) ? (int) $c['summary_gap_px'] : 10, '', array( 'var' => $p . 'sticky-summary-gap', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'summary_text_gap_row_px', __( 'Зазор между счётчиком и суммой: строка (px)', 'mp-sticky-custom-cart' ), isset( $c['summary_text_gap_row_px'] ) ? (int) $c['summary_text_gap_row_px'] : 12, __( 'На desktop влияет на разрядку между числом позиций и суммой.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'sticky-summary-text-gap-row', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'summary_text_gap_column_px', __( 'Зазор между счётчиком и суммой: колонка (px)', 'mp-sticky-custom-cart' ), isset( $c['summary_text_gap_column_px'] ) ? (int) $c['summary_text_gap_column_px'] : 20, '', array( 'var' => $p . 'sticky-summary-text-gap-column', 'fmt' => 'unit', 'suffix' => 'px' ) );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Кнопки «Очистить» и «Оформить заказ»', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Тексты кнопок настраиваются на вкладке «Каталог» → «Тексты интерфейса».', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'button_font_size_px', __( 'Размер шрифта кнопок (px)', 'mp-sticky-custom-cart' ), isset( $c['button_font_size_px'] ) ? (int) $c['button_font_size_px'] : 14, '', array( 'var' => $p . 'sticky-button-font-size', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'button_font_weight', __( 'Начертание кнопок (100–900)', 'mp-sticky-custom-cart' ), isset( $c['button_font_weight'] ) ? (int) $c['button_font_weight'] : 600, '', array( 'var' => $p . 'sticky-button-font-weight', 'fmt' => 'integer' ) );
		self::field_line_height(
			$opt,
			'sticky_cart',
			'button_line_height',
			__( 'Межстрочный интервал кнопок (без единиц)', 'mp-sticky-custom-cart' ),
			isset( $c['button_line_height'] ) && is_numeric( $c['button_line_height'] ) ? (float) $c['button_line_height'] : 1.2,
			'',
			array( 'var' => $p . 'sticky-button-line-height', 'fmt' => 'float' )
		);
		self::field_number( $opt, 'sticky_cart', 'actions_gap_px', __( 'Зазор между кнопками (px)', 'mp-sticky-custom-cart' ), isset( $c['actions_gap_px'] ) ? (int) $c['actions_gap_px'] : 10, '', array( 'var' => $p . 'sticky-actions-gap', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'clear_button_min_width_px', __( 'Мин. ширина «Очистить» (px, 0 = авто)', 'mp-sticky-custom-cart' ), isset( $c['clear_button_min_width_px'] ) ? (int) $c['clear_button_min_width_px'] : 0, '', array( 'var' => $p . 'sticky-clear-min-width', 'fmt' => 'unit', 'suffix' => 'px', 'omit_if_zero' => true ) );
		self::field_number( $opt, 'sticky_cart', 'checkout_button_min_width_px', __( 'Мин. ширина «Оформить» (px, 0 = авто)', 'mp-sticky-custom-cart' ), isset( $c['checkout_button_min_width_px'] ) ? (int) $c['checkout_button_min_width_px'] : 0, '', array( 'var' => $p . 'sticky-checkout-min-width', 'fmt' => 'unit', 'suffix' => 'px', 'omit_if_zero' => true ) );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Масштаб типографики панели', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Умножает размер текста summary и кнопок (поля выше). 100% — без изменений. Полезно слегка увеличить или уменьшить весь блок, не трогая базовые px.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		$scale_pct = isset( $st['typography_scale_percent'] ) ? (int) $st['typography_scale_percent'] : 100;
		self::field_number(
			$opt,
			'styles',
			'typography_scale_percent',
			__( 'Масштаб (%)', 'mp-sticky-custom-cart' ),
			$scale_pct,
			'',
			array(
				'fmt'          => 'typography_scale',
				'varSummary'   => $p . 'sticky-summary-font-size',
				'varButton'    => $p . 'sticky-button-font-size',
				'summaryField' => Constants::OPTION_SETTINGS . '[sticky_cart][summary_font_size_px]',
				'buttonField'  => Constants::OPTION_SETTINGS . '[sticky_cart][button_font_size_px]',
			)
		);
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Drawer: высота и внутренние отступы', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'drawer_max_height_vh', __( 'Макс. высота drawer (vh)', 'mp-sticky-custom-cart' ), isset( $c['drawer_max_height_vh'] ) ? (int) $c['drawer_max_height_vh'] : 55, __( 'Ограничивает высоту блока со списком; лишнее уходит во внутренний скролл.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'sticky-drawer-max-height', 'fmt' => 'unit', 'suffix' => 'vh' ) );
		self::field_number( $opt, 'sticky_cart', 'drawer_padding_x_px', __( 'Внутренний отступ drawer по горизонтали (px)', 'mp-sticky-custom-cart' ), isset( $c['drawer_padding_x_px'] ) ? (int) $c['drawer_padding_x_px'] : 16, '', array( 'var' => $p . 'sticky-drawer-padding-x', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'drawer_padding_y_px', __( 'Внутренний отступ drawer по вертикали (px)', 'mp-sticky-custom-cart' ), isset( $c['drawer_padding_y_px'] ) ? (int) $c['drawer_padding_y_px'] : 12, '', array( 'var' => $p . 'sticky-drawer-padding-y', 'fmt' => 'unit', 'suffix' => 'px' ) );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Три состояния: панель B (краткая сводка)', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Важно: нижняя полоса с текстовыми кнопками «Очистить» / «Оформить» остаётся, пока на вкладке «Служебное» в блоке Feature flags не включён пункт «Корзина: режим «иконка» (три состояния)» (и включён drawer). Поля ниже задают внешний вид уже режима FAB + панелей B/C.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'tristate_panel_b_max_height_px', __( 'Макс. высота панели B (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_panel_b_max_height_px'] ) ? (int) $c['tristate_panel_b_max_height_px'] : 368, __( 'Ограничивает блок; при превышении контент обрезается по краю (без скролла внутри панели).', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'tristate-panel-b-max-height', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_panel_b_width_px', __( 'Ширина панели B (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_panel_b_width_px'] ) ? (int) $c['tristate_panel_b_width_px'] : 280, '', array( 'var' => $p . 'tristate-panel-b-width', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_panel_b_gap_bottom_px', __( 'Зазор между FAB и панелью B (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_panel_b_gap_bottom_px'] ) ? (int) $c['tristate_panel_b_gap_bottom_px'] : 10, '', array( 'var' => $p . 'tristate-panel-b-gap-bottom', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_panel_b_padding_px', __( 'Внутренний отступ панели B (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_panel_b_padding_px'] ) ? (int) $c['tristate_panel_b_padding_px'] : 12, '', array( 'var' => $p . 'tristate-panel-b-padding', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_panel_b_border_radius_px', __( 'Скругление панели B (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_panel_b_border_radius_px'] ) ? (int) $c['tristate_panel_b_border_radius_px'] : 12, '', array( 'var' => $p . 'tristate-panel-b-border-radius', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_panel_b_actions_gap_px', __( 'Зазор между иконками в панели B (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_panel_b_actions_gap_px'] ) ? (int) $c['tristate_panel_b_actions_gap_px'] : 8, '', array( 'var' => $p . 'tristate-panel-b-actions-gap', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_actions_toolbar_padding_top_px', __( 'Панель B и drawer C: отступ строки иконок сверху (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_actions_toolbar_padding_top_px'] ) ? (int) $c['tristate_actions_toolbar_padding_top_px'] : 24, __( 'Роль toolbar: .mp-scc-shell-panel-b__actions. CSS: --mp-scc-tristate-actions-toolbar-padding-top.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'tristate-actions-toolbar-padding-top', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_actions_toolbar_padding_right_px', __( 'Панель B и drawer C: отступ строки иконок справа (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_actions_toolbar_padding_right_px'] ) ? (int) $c['tristate_actions_toolbar_padding_right_px'] : 0, '', array( 'var' => $p . 'tristate-actions-toolbar-padding-right', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_actions_toolbar_padding_bottom_px', __( 'Панель B и drawer C: отступ строки иконок снизу (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_actions_toolbar_padding_bottom_px'] ) ? (int) $c['tristate_actions_toolbar_padding_bottom_px'] : 0, '', array( 'var' => $p . 'tristate-actions-toolbar-padding-bottom', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_actions_toolbar_padding_left_px', __( 'Панель B и drawer C: отступ строки иконок слева (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_actions_toolbar_padding_left_px'] ) ? (int) $c['tristate_actions_toolbar_padding_left_px'] : 0, '', array( 'var' => $p . 'tristate-actions-toolbar-padding-left', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_action_icon_hit_px', __( 'Размер зоны нажатия иконок «очистить / оформить» (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_action_icon_hit_px'] ) ? (int) $c['tristate_action_icon_hit_px'] : 44, __( 'Квадратная кнопка в панели B и в drawer C (три состояния).', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'tristate-action-icon-hit', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_action_icon_glyph_px', __( 'Размер SVG-иконки внутри зоны (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_action_icon_glyph_px'] ) ? (int) $c['tristate_action_icon_glyph_px'] : 22, '', array( 'var' => $p . 'tristate-action-icon-glyph', 'fmt' => 'unit', 'suffix' => 'px' ) );
		echo '<tr><td colspan="2"><p class="description" style="margin:0 0 4px;"><strong>' . esc_html__( 'Крестик закрытия (панель B и список C)', 'mp-sticky-custom-cart' ) . '</strong> — ';
		echo esc_html__( 'Кнопка «×» сверху справа: панель B — position внутри блока метрик; в C — в шапке строки рядом с суммой. Переменные --mp-scc-tristate-dismiss-* на :root.', 'mp-sticky-custom-cart' ) . '</p></td></tr>';
		self::field_number( $opt, 'sticky_cart', 'tristate_dismiss_hit_px', __( 'Крестик: внешний размер кнопки (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_hit_px'] ) ? (int) $c['tristate_dismiss_hit_px'] : 36, __( 'Квадрат: ширина и высота области нажатия.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'tristate-dismiss-hit', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_dismiss_glyph_px', __( 'Крестик: размер значка × внутри (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_glyph_px'] ) ? (int) $c['tristate_dismiss_glyph_px'] : 16, '', array( 'var' => $p . 'tristate-dismiss-glyph', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_dismiss_margin_top_px', __( 'Крестик: внешний отступ сверху (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_margin_top_px'] ) ? (int) $c['tristate_dismiss_margin_top_px'] : 0, __( 'У B: от верхнего края области тела панели. У C: от верхнего края строки шапки.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'tristate-dismiss-margin-top', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_dismiss_margin_right_px', __( 'Крестик: внешний отступ справа (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_margin_right_px'] ) ? (int) $c['tristate_dismiss_margin_right_px'] : 0, '', array( 'var' => $p . 'tristate-dismiss-margin-right', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_dismiss_margin_bottom_px', __( 'Крестик: внешний отступ снизу (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_margin_bottom_px'] ) ? (int) $c['tristate_dismiss_margin_bottom_px'] : 0, __( 'Для C в основном влияет в flex-строке; у B при absolute обычно 0.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'tristate-dismiss-margin-bottom', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_dismiss_margin_left_px', __( 'Крестик: внешний отступ слева (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_margin_left_px'] ) ? (int) $c['tristate_dismiss_margin_left_px'] : 0, '', array( 'var' => $p . 'tristate-dismiss-margin-left', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_dismiss_padding_top_px', __( 'Крестик: внутренний отступ сверху (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_padding_top_px'] ) ? (int) $c['tristate_dismiss_padding_top_px'] : 2, '', array( 'var' => $p . 'tristate-dismiss-padding-top', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_dismiss_padding_right_px', __( 'Крестик: внутренний отступ справа (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_padding_right_px'] ) ? (int) $c['tristate_dismiss_padding_right_px'] : 2, '', array( 'var' => $p . 'tristate-dismiss-padding-right', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_dismiss_padding_bottom_px', __( 'Крестик: внутренний отступ снизу (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_padding_bottom_px'] ) ? (int) $c['tristate_dismiss_padding_bottom_px'] : 2, '', array( 'var' => $p . 'tristate-dismiss-padding-bottom', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_dismiss_padding_left_px', __( 'Крестик: внутренний отступ слева (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_padding_left_px'] ) ? (int) $c['tristate_dismiss_padding_left_px'] : 2, '', array( 'var' => $p . 'tristate-dismiss-padding-left', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_dismiss_border_width_px', __( 'Крестик: толщина обводки (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_border_width_px'] ) ? (int) $c['tristate_dismiss_border_width_px'] : 1, '', array( 'var' => $p . 'tristate-dismiss-border-width', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_dismiss_border_radius_px', __( 'Крестик: скругление углов (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_border_radius_px'] ) ? (int) $c['tristate_dismiss_border_radius_px'] : 8, '', array( 'var' => $p . 'tristate-dismiss-border-radius', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_color( $opt, 'sticky_cart', 'tristate_dismiss_color', __( 'Крестик: цвет значка (обычно)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_color'] ) ? (string) $c['tristate_dismiss_color'] : '#1a1a1a', '', array( 'var' => $p . 'tristate-dismiss-color', 'fmt' => 'color' ) );
		self::field_color( $opt, 'sticky_cart', 'tristate_dismiss_bg_color', __( 'Крестик: фон (обычно)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_bg_color'] ) ? (string) $c['tristate_dismiss_bg_color'] : '#ffffff', '', array( 'var' => $p . 'tristate-dismiss-bg', 'fmt' => 'color' ) );
		self::field_color( $opt, 'sticky_cart', 'tristate_dismiss_border_color', __( 'Крестик: цвет обводки (обычно)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_border_color'] ) ? (string) $c['tristate_dismiss_border_color'] : '#d0d0d0', '', array( 'var' => $p . 'tristate-dismiss-border-color', 'fmt' => 'color' ) );
		self::field_color( $opt, 'sticky_cart', 'tristate_dismiss_hover_color', __( 'Крестик: цвет значка при наведении', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_hover_color'] ) ? (string) $c['tristate_dismiss_hover_color'] : '#1a1a1a', '', array( 'var' => $p . 'tristate-dismiss-hover-color', 'fmt' => 'color' ) );
		self::field_color( $opt, 'sticky_cart', 'tristate_dismiss_hover_bg_color', __( 'Крестик: фон при наведении', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_hover_bg_color'] ) ? (string) $c['tristate_dismiss_hover_bg_color'] : '#f0f0f0', '', array( 'var' => $p . 'tristate-dismiss-hover-bg', 'fmt' => 'color' ) );
		self::field_color( $opt, 'sticky_cart', 'tristate_dismiss_hover_border_color', __( 'Крестик: цвет обводки при наведении', 'mp-sticky-custom-cart' ), isset( $c['tristate_dismiss_hover_border_color'] ) ? (string) $c['tristate_dismiss_hover_border_color'] : '#b0b0b0', '', array( 'var' => $p . 'tristate-dismiss-hover-border-color', 'fmt' => 'color' ) );
		echo '<tr><td colspan="2"><p class="description" style="margin:8px 0 0;"><strong>' . esc_html__( 'Панель C (drawer)', 'mp-sticky-custom-cart' ) . '</strong></p></td></tr>';
		self::field_number( $opt, 'sticky_cart', 'tristate_panel_c_width_px', __( 'Ширина панели C / drawer (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_panel_c_width_px'] ) ? (int) $c['tristate_panel_c_width_px'] : 600, __( 'Расширение влево от правого края; высота совпадает с макс. высотой панели B.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'tristate-panel-c-width', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_panel_c_height_px', __( 'Высота панели C / drawer (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_panel_c_height_px'] ) ? (int) $c['tristate_panel_c_height_px'] : 368, __( 'Фиксированная высота состояния C.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'tristate-panel-c-height', 'fmt' => 'unit', 'suffix' => 'px' ) );
		echo '</tbody></table>';

		$dock_r = isset( $c['tristate_dock_inset_right_px'] ) ? (int) $c['tristate_dock_inset_right_px'] : 32;
		$dock_l = isset( $c['tristate_dock_inset_left_px'] ) ? (int) $c['tristate_dock_inset_left_px'] : $dock_r;
		$a_r    = isset( $c['tristate_state_a_dock_inset_right_px'] ) ? (int) $c['tristate_state_a_dock_inset_right_px'] : $dock_r;
		$a_l    = isset( $c['tristate_state_a_dock_inset_left_px'] ) ? (int) $c['tristate_state_a_dock_inset_left_px'] : $a_r;

		echo '<h3>' . esc_html__( 'Три состояния: A — только FAB', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Отступы кнопки корзины от краёв окна, пока панель B и drawer C закрыты (состояние A). После открытия B или C действуют отступы колонки ниже.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'tristate_state_a_dock_inset_top_px', __( 'A: отступ сверху (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_state_a_dock_inset_top_px'] ) ? (int) $c['tristate_state_a_dock_inset_top_px'] : ( isset( $c['tristate_dock_inset_top_px'] ) ? (int) $c['tristate_dock_inset_top_px'] : 0 ), '', array( 'var' => $p . 'tristate-state-a-dock-inset-top', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_state_a_dock_inset_right_px', __( 'A: отступ справа (px)', 'mp-sticky-custom-cart' ), $a_r, '', array( 'var' => $p . 'tristate-state-a-dock-inset-right', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_state_a_dock_inset_bottom_px', __( 'A: отступ снизу (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_state_a_dock_inset_bottom_px'] ) ? (int) $c['tristate_state_a_dock_inset_bottom_px'] : ( isset( $c['tristate_dock_inset_bottom_px'] ) ? (int) $c['tristate_dock_inset_bottom_px'] : 32 ), __( 'Над safe-area снизу.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'tristate-state-a-dock-inset-bottom', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_state_a_dock_inset_left_px', __( 'A: отступ слева (px)', 'mp-sticky-custom-cart' ), $a_l, '', array( 'var' => $p . 'tristate-state-a-dock-inset-left', 'fmt' => 'unit', 'suffix' => 'px' ) );
		echo '</tbody></table>';

		echo '<h4 class="title">' . esc_html__( 'A: бейдж количества на FAB', 'mp-sticky-custom-cart' ) . '</h4>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number(
			$opt,
			'sticky_cart',
			'tristate_fab_badge_size_px',
			__( 'Размер бейджа (px)', 'mp-sticky-custom-cart' ),
			isset( $c['tristate_fab_badge_size_px'] ) ? (int) $c['tristate_fab_badge_size_px'] : 20,
			__( 'Диаметр красного кружка на FAB (14–36).', 'mp-sticky-custom-cart' ),
			array( 'var' => $p . 'tristate-fab-badge-size', 'fmt' => 'unit', 'suffix' => 'px' )
		);
		self::field_number(
			$opt,
			'sticky_cart',
			'tristate_fab_badge_offset_top_px',
			__( 'Смещение бейджа сверху (px)', 'mp-sticky-custom-cart' ),
			isset( $c['tristate_fab_badge_offset_top_px'] ) ? (int) $c['tristate_fab_badge_offset_top_px'] : -8,
			__( 'Относительно правого верхнего угла FAB. Отрицательное значение выводит кружок наружу.', 'mp-sticky-custom-cart' ),
			array( 'var' => $p . 'tristate-fab-badge-offset-top', 'fmt' => 'unit', 'suffix' => 'px' )
		);
		self::field_number(
			$opt,
			'sticky_cart',
			'tristate_fab_badge_offset_right_px',
			__( 'Смещение бейджа справа (px)', 'mp-sticky-custom-cart' ),
			isset( $c['tristate_fab_badge_offset_right_px'] ) ? (int) $c['tristate_fab_badge_offset_right_px'] : -8,
			'',
			array( 'var' => $p . 'tristate-fab-badge-offset-right', 'fmt' => 'unit', 'suffix' => 'px' )
		);
		self::field_number(
			$opt,
			'sticky_cart',
			'tristate_fab_badge_font_size_px',
			__( 'Размер цифры в бейдже (px)', 'mp-sticky-custom-cart' ),
			isset( $c['tristate_fab_badge_font_size_px'] ) ? (int) $c['tristate_fab_badge_font_size_px'] : 11,
			'',
			array( 'var' => $p . 'tristate-fab-badge-font-size', 'fmt' => 'unit', 'suffix' => 'px' )
		);
		self::field_color(
			$opt,
			'sticky_cart',
			'tristate_fab_badge_bg_color',
			__( 'Цвет фона бейджа', 'mp-sticky-custom-cart' ),
			isset( $c['tristate_fab_badge_bg_color'] ) ? (string) $c['tristate_fab_badge_bg_color'] : '#e53935',
			'',
			array( 'var' => $p . 'tristate-fab-badge-bg', 'fmt' => 'color' )
		);
		self::field_color(
			$opt,
			'sticky_cart',
			'tristate_fab_badge_text_color',
			__( 'Цвет цифры в бейдже', 'mp-sticky-custom-cart' ),
			isset( $c['tristate_fab_badge_text_color'] ) ? (string) $c['tristate_fab_badge_text_color'] : '#ffffff',
			'',
			array( 'var' => $p . 'tristate-fab-badge-text', 'fmt' => 'color' )
		);
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Три состояния: пристыковка, стекло и тень (B/C)', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Отступы колонки (панель B и drawer C) от краёв viewport, blur и тень. Цвет заливки и альфа — из «Стили» / drawer (как у основного drawer). Z-index панелей привязан к z-index корзины.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'tristate_dock_inset_top_px', __( 'B/C: отступ колонки сверху (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dock_inset_top_px'] ) ? (int) $c['tristate_dock_inset_top_px'] : 0, __( 'Когда открыта панель B или drawer C.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'tristate-dock-inset-top', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_dock_inset_right_px', __( 'B/C: отступ колонки справа (px)', 'mp-sticky-custom-cart' ), $dock_r, '', array( 'var' => $p . 'tristate-dock-inset-right', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_dock_inset_bottom_px', __( 'B/C: отступ колонки снизу (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_dock_inset_bottom_px'] ) ? (int) $c['tristate_dock_inset_bottom_px'] : 32, __( 'Над safe-area снизу.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'tristate-dock-inset-bottom', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_dock_inset_left_px', __( 'B/C: отступ колонки слева (px)', 'mp-sticky-custom-cart' ), $dock_l, __( 'Независимо от правого.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'tristate-dock-inset-left', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_column_backdrop_blur_px', __( 'Blur панелей B/C (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_column_backdrop_blur_px'] ) ? (int) $c['tristate_column_backdrop_blur_px'] : 14, __( '0 — без размытия фона за панелью.', 'mp-sticky-custom-cart' ), array( 'var' => $p . 'tristate-column-backdrop-blur', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_column_shadow_blur_px', __( 'Размытие тени B/C (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_column_shadow_blur_px'] ) ? (int) $c['tristate_column_shadow_blur_px'] : 28, '', array( 'var' => $p . 'tristate-column-shadow-blur', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_column_shadow_offset_y_px', __( 'Смещение тени вверх (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_column_shadow_offset_y_px'] ) ? (int) $c['tristate_column_shadow_offset_y_px'] : 8, '', array( 'var' => $p . 'tristate-column-shadow-offset-y', 'fmt' => 'unit', 'suffix' => 'px' ) );
		self::field_number( $opt, 'sticky_cart', 'tristate_column_shadow_opacity_percent', __( 'Непрозрачность тени (%)', 'mp-sticky-custom-cart' ), isset( $c['tristate_column_shadow_opacity_percent'] ) ? (int) $c['tristate_column_shadow_opacity_percent'] : 12, __( '4–28%: преобразуется в alpha для rgba (см. --mp-scc-tristate-column-shadow-alpha в :root).', 'mp-sticky-custom-cart' ) );
		$ts_preset = isset( $c['tristate_mobile_layout_preset'] ) && is_string( $c['tristate_mobile_layout_preset'] ) ? $c['tristate_mobile_layout_preset'] : 'right_docked';
		if ( ! in_array( $ts_preset, array( 'right_docked', 'full_bottom' ), true ) ) {
			$ts_preset = 'right_docked';
		}
		self::field_select(
			$opt,
			'sticky_cart',
			'tristate_mobile_layout_preset',
			__( 'Мобильный пресет B/C (узкий экран)', 'mp-sticky-custom-cart' ),
			$ts_preset,
			array(
				'right_docked' => __( 'У правого края (колонка)', 'mp-sticky-custom-cart' ),
				'full_bottom'  => __( 'Широкая полоса между отступами (ниже breakpoint)', 'mp-sticky-custom-cart' ),
			),
			__( 'Связь с фазой 20: при «широкой полосе» подключается отдельный CSS ниже заданной ширины viewport.', 'mp-sticky-custom-cart' )
		);
		self::field_number( $opt, 'sticky_cart', 'tristate_mobile_breakpoint_max_px', __( 'Макс. ширина экрана для пресета (px)', 'mp-sticky-custom-cart' ), isset( $c['tristate_mobile_breakpoint_max_px'] ) ? (int) $c['tristate_mobile_breakpoint_max_px'] : 782, __( 'Используется только для пресета «широкая полоса» (media max-width). Обычно 782 — как break у админ-бара WP.', 'mp-sticky-custom-cart' ), array() );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Три состояния: полный CSS override (A/B/C)', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Для тонкой настройки любых недостающих стилей. Поля ниже печатаются как CSS в footer. Можно задавать любые селекторы/правила без ограничений визуального конструктора.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<details class="mp-scc-tristate-css-cheatsheet" style="margin: 10px 0 16px;">';
		echo '<summary style="cursor:pointer;">' . esc_html__( 'Справочник селекторов (копируй и правь)', 'mp-sticky-custom-cart' ) . '</summary>';
		echo '<div class="mp-scc-tristate-css-cheatsheet__body" style="margin-top:10px;">';
		echo '<p class="description">' . esc_html__( 'Состояние A/B/C выставляется на корне как data-mp-scc-shell-state. Поля «State A/B/C CSS» автоматически оборачиваются в селектор корня для этого состояния — внутри можно писать только декларации или вложенные селекторы по потомкам.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Корень (всегда в DOM)', 'mp-sticky-custom-cart' ) . '</strong><br />';
		echo '<code>#mp-scc-sticky-root</code> <code>.mp-scc-root</code> <code>.mp-scc-sticky-bar</code> <code>.mp-scc-sticky--tristate</code><br />';
		echo '<code>data-mp-scc-sticky-root</code>, <code>data-mp-scc-cart-empty</code> (<code>0|1</code>), <code>data-mp-scc-sticky-tristate</code> (<code>0|1</code>), <code>data-mp-scc-shell-state</code> (<code>A|B|C</code>)</p>';
		echo '<p><strong>' . esc_html__( 'Обёртка колонки над нижней полосой', 'mp-sticky-custom-cart' ) . '</strong><br /><code>.mp-scc-sticky-stack</code></p>';

		echo '<h4 style="margin:1em 0 0.35em;">' . esc_html__( 'Состояние A (только FAB / свёрнута панель)', 'mp-sticky-custom-cart' ) . '</h4>';
		echo '<p><code>#mp-scc-sticky-root[data-mp-scc-shell-state="A"]</code> …</p>';
		echo '<p><code>.mp-scc-sticky-inner</code> — ' . esc_html__( 'нижняя полоса', 'mp-sticky-custom-cart' ) . '<br />';
		echo '<code>.mp-scc-sticky-summary</code> — ' . esc_html__( 'область FAB', 'mp-sticky-custom-cart' ) . '<br />';
		echo '<code>#mp-scc-drawer-toggle</code> <code>.mp-scc-drawer-toggle</code> <code>.mp-scc-drawer-toggle--fab</code> — <code>data-mp-scc-drawer-toggle</code>, <code>aria-controls</code><br />';
		echo '<code>.mp-scc-drawer-toggle-icon</code>, <code>.mp-scc-drawer-toggle-badge</code> (<code>data-mp-scc-cart-line-count</code>)<br />';
		echo '<code>.mp-scc-sticky-summary-text</code>, <code>.mp-scc-cart-count</code>, <code>.mp-scc-cart-total</code>, <code>data-mp-scc-cart-qty-total</code> — ' . esc_html__( 'в tri-state часть скрыта a11y/CSS', 'mp-sticky-custom-cart' ) . '<br />';
		echo '<code>.mp-scc-sticky-actions</code> <code>data-mp-scc-actions</code> — ' . esc_html__( 'текстовые кнопки в tri-state обычно скрыты', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<p><code>#mp-scc-shell-panel-b</code> … — ' . esc_html__( 'есть в DOM, при A обычно', 'mp-sticky-custom-cart' ) . ' <code>hidden</code>. ';
		echo '<code>#mp-scc-drawer</code> … — ' . esc_html__( 'при A обычно', 'mp-sticky-custom-cart' ) . ' <code>hidden</code>.</p>';

		echo '<h4 style="margin:1em 0 0.35em;">' . esc_html__( 'Состояние B (панель суммы)', 'mp-sticky-custom-cart' ) . '</h4>';
		echo '<p><code>#mp-scc-sticky-root[data-mp-scc-shell-state="B"]</code> …</p>';
		echo '<p><code>#mp-scc-shell-panel-b</code> <code>.mp-scc-shell-panel-b</code> <code>data-mp-scc-shell-panel-b</code><br />';
		echo '<code>.mp-scc-shell-panel-b__body</code><br />';
		echo '<code>.mp-scc-shell-panel-b__metrics</code> (<code>aria-live</code>)<br />';
		echo '<code>.mp-scc-shell-panel-b__row</code> <code>.mp-scc-shell-panel-b__row--lines</code> <code>.mp-scc-shell-panel-b__row--qty</code> <code>.mp-scc-shell-panel-b__row--total</code><br />';
		echo '<code>.mp-scc-shell-panel-b__label</code> <code>.mp-scc-shell-panel-b__value</code> <code>.mp-scc-shell-panel-b__subtotal</code> + <code>data-mp-scc-cart-line-count</code> / <code>data-mp-scc-cart-qty-count</code> / <code>data-mp-scc-cart-total</code><br />';
		echo '<code>.mp-scc-shell-panel-b__actions</code> (<code>role="toolbar"</code>)<br />';
		echo '<code>.mp-scc-tristate-dismiss</code> <code>data-mp-scc-shell-dismiss="b"</code> <code>.mp-scc-tristate-dismiss__glyph</code> <code>.mp-scc-tristate-dismiss__svg</code><br />';
		echo '<code>.mp-scc-shell-panel-b__icon-btn</code> <code>.mp-scc-btn</code> <code>.mp-scc-btn--ghost</code> / <code>.mp-scc-btn--primary</code><br />';
		echo '<code>.mp-scc-clear-cart</code> <code>data-mp-scc-clear-cart</code> <code>data-mp-scc-clear-label</code> <code>data-mp-scc-clear-aria-disabled</code><br />';
		echo '<code>.mp-scc-checkout</code> <code>data-mp-scc-checkout</code> <code>data-mp-scc-checkout-label</code> <code>data-mp-scc-checkout-base</code> <code>data-mp-scc-checkout-aria-disabled</code><br />';
		echo '<code>.mp-scc-shell-panel-b__icon-svg</code> <code>.mp-scc-tristate-action-svg</code> <code>.mp-scc-tristate-action-svg--trash</code> <code>.mp-scc-tristate-action-svg--cart</code><br />';
		echo '<code>.mp-scc-shell-panel-b__toggle-c</code> <code>data-mp-scc-toggle-c</code> <code>data-mp-scc-toggle-c-mode="open"</code> + <code>.mp-scc-tristate-action-svg--chevron-up</code></p>';

		echo '<h4 style="margin:1em 0 0.35em;">' . esc_html__( 'Состояние C (drawer со списком строк)', 'mp-sticky-custom-cart' ) . '</h4>';
		echo '<p><code>#mp-scc-sticky-root[data-mp-scc-shell-state="C"]</code> …</p>';
		echo '<p><code>#mp-scc-drawer</code> <code>.mp-scc-drawer</code> <code>.mp-scc-drawer--tristate-c</code> <code>.mp-scc-drawer--empty</code> <code>data-mp-scc-drawer</code><br />';
		echo esc_html__( 'Классы от JS при переключении списка:', 'mp-sticky-custom-cart' ) . ' <code>.mp-scc-drawer--show-items</code> / <code>.mp-scc-drawer--show-empty</code><br />';
		echo '<code>.mp-scc-drawer-inner</code> <code>.mp-scc-drawer-inner--empty</code><br />';
		echo '<code>.mp-scc-drawer-c</code> <code>data-mp-scc-drawer-tristate-c</code><br />';
		echo '<code>.mp-scc-drawer-c__top</code> — <code>.mp-scc-tristate-dismiss</code> <code>data-mp-scc-shell-dismiss="c"</code><br />';
		echo '<code>.mp-scc-drawer-c__collapse</code> <code>.mp-scc-shell-panel-b__toggle-c</code> <code>data-mp-scc-toggle-c</code> <code>data-mp-scc-toggle-c-mode="close"</code> + <code>.mp-scc-tristate-action-svg--chevron-down</code><br />';
		echo '<code>.mp-scc-drawer-c__metrics</code> — <code>.mp-scc-drawer-c__row</code> <code>.mp-scc-drawer-c__row--lines</code> <code>.mp-scc-drawer-c__row--qty</code> <code>.mp-scc-drawer-c__row--total</code> <code>.mp-scc-drawer-c__label</code> <code>.mp-scc-drawer-c__value</code> <code>.mp-scc-drawer-c__subtotal</code><br />';
		echo '<code>.mp-scc-drawer-c__scroll</code> <code>data-mp-scc-drawer-lines-scroll</code><br />';
		echo '<code>#mp-scc-drawer-items</code> <code>.mp-scc-drawer-items</code> <code>data-mp-scc-drawer-items</code><br />';
		echo '<code>#mp-scc-drawer-empty</code> <code>.mp-scc-drawer-empty</code> <code>.mp-scc-drawer-empty--off</code> <code>data-mp-scc-drawer-empty</code><br />';
		echo '<code>.mp-scc-drawer-empty-visual</code> <code>.mp-scc-drawer-empty-icon</code> <code>.mp-scc-drawer-empty-title</code> <code>.mp-scc-drawer-empty-hint</code><br />';
		echo '<code>.mp-scc-shell-panel-b__actions</code> — ' . esc_html__( 'в C только очистка + оформление (без третьей стрелки в toolbar)', 'mp-sticky-custom-cart' ) . '</p>';

		echo '<h4 style="margin:1em 0 0.35em;">' . esc_html__( 'Строки корзины (рендер JS, все состояния где открыт список)', 'mp-sticky-custom-cart' ) . '</h4>';
		echo '<p><code>li.mp-scc-line</code> <code>.mp-scc-line--warn</code> <code>.mp-scc-line--removing</code><br />';
		echo '<code>data-mp-scc-line-key</code> <code>data-cart-item-key</code> <code>data-mp-scc-snapshot-line-id</code> <code>data-mp-scc-product-id</code> <code>data-mp-scc-variation-id</code> <code>data-mp-scc-max-qty</code><br />';
		echo '<code>.mp-scc-line__row</code> <code>.mp-scc-line__thumb</code> <code>.mp-scc-line__thumb-fallback</code> <code>.mp-scc-line__main</code> <code>.mp-scc-line__subtotal</code><br />';
		echo '<code>.mp-scc-line__warning</code> <code>.mp-scc-line__name</code> (<code>a</code> или <code>span</code>) <code>.mp-scc-line__unit</code><br />';
		echo '<code>.mp-scc-line__controls</code> <code>.mp-scc-line__qty</code> <code>.mp-scc-line__qty-val</code> <code>data-mp-scc-qty-display</code><br />';
		echo '<code>.mp-scc-qty-btn</code> <code>data-mp-scc-qty-dec</code> <code>data-mp-scc-qty-inc</code><br />';
		echo '<code>.mp-scc-line__remove</code> <code>data-mp-scc-line-remove</code></p>';

		echo '<p class="description">' . esc_html__( 'Подсказка: в DevTools смотри id и data-*; для кастомного CSS удобнее цепляться к #mp-scc-sticky-root[data-mp-scc-shell-state="…"].', 'mp-sticky-custom-cart' ) . '</p>';
		echo '</div>';
		echo '</details>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_textarea( $opt, 'sticky_cart', 'tristate_custom_css_global', __( 'Global CSS (для всех состояний)', 'mp-sticky-custom-cart' ), isset( $c['tristate_custom_css_global'] ) ? (string) $c['tristate_custom_css_global'] : '', __( 'Применяется как есть, без авто-обёртки. Пример селектора: .mp-scc-sticky-bar.mp-scc-sticky--tristate {...}', 'mp-sticky-custom-cart' ), array( 'rows' => 6, 'maxlength' => 20000 ) );
		self::field_textarea( $opt, 'sticky_cart', 'tristate_custom_css_state_a', __( 'State A CSS', 'mp-sticky-custom-cart' ), isset( $c['tristate_custom_css_state_a'] ) ? (string) $c['tristate_custom_css_state_a'] : '', __( 'Содержимое вставляется в селектор состояния A.', 'mp-sticky-custom-cart' ), array( 'rows' => 5, 'maxlength' => 20000 ) );
		self::field_textarea( $opt, 'sticky_cart', 'tristate_custom_css_state_b', __( 'State B CSS', 'mp-sticky-custom-cart' ), isset( $c['tristate_custom_css_state_b'] ) ? (string) $c['tristate_custom_css_state_b'] : '', __( 'Содержимое вставляется в селектор состояния B.', 'mp-sticky-custom-cart' ), array( 'rows' => 5, 'maxlength' => 20000 ) );
		self::field_textarea( $opt, 'sticky_cart', 'tristate_custom_css_state_c', __( 'State C CSS', 'mp-sticky-custom-cart' ), isset( $c['tristate_custom_css_state_c'] ) ? (string) $c['tristate_custom_css_state_c'] : '', __( 'Содержимое вставляется в селектор состояния C.', 'mp-sticky-custom-cart' ), array( 'rows' => 5, 'maxlength' => 20000 ) );
		echo '</tbody></table>';
	}

	/**
	 * @param array<string, mixed> $s
	 * @param array<string, bool>  $f
	 * @param string               $opt
	 * @param string               $fg
	 */
	private static function render_diagnostics_tab( array $s, array $f, $opt, $fg ) {
		$d = isset( $s['diagnostics'] ) && is_array( $s['diagnostics'] ) ? $s['diagnostics'] : array();
		echo '<h2>' . esc_html__( 'Диагностика', 'mp-sticky-custom-cart' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_hidden_then_checkbox(
			$opt,
			'diagnostics',
			'client_error_logging',
			__( 'Логировать ошибки JS/AJAX на сервер', 'mp-sticky-custom-cart' ),
			! empty( $d['client_error_logging'] )
		);
		self::field_number(
			$opt,
			'diagnostics',
			'log_retention_days',
			__( 'Хранить логи (дней)', 'mp-sticky-custom-cart' ),
			isset( $d['log_retention_days'] ) ? (int) $d['log_retention_days'] : 14,
			__( 'Записи старше этого срока удаляются при добавлении новых (и при ротации по объёму).', 'mp-sticky-custom-cart' )
		);
		self::field_number(
			$opt,
			'diagnostics',
			'log_max_entries',
			__( 'Макс. число записей в журнале', 'mp-sticky-custom-cart' ),
			isset( $d['log_max_entries'] ) ? (int) $d['log_max_entries'] : 300,
			__( 'После превышения удаляются самые старые записи.', 'mp-sticky-custom-cart' )
		);
		self::field_number(
			$opt,
			'diagnostics',
			'log_max_bytes',
			__( 'Макс. размер журнала (байт)', 'mp-sticky-custom-cart' ),
			isset( $d['log_max_bytes'] ) ? (int) $d['log_max_bytes'] : 262144,
			__( 'Ограничение размера опции в wp_options; при превышении старые записи отбрасываются.', 'mp-sticky-custom-cart' )
		);
		echo '</tbody></table>';

		self::render_error_log_panel();

		echo '<h3>' . esc_html__( 'Feature flags', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';

		$labels = FeatureFlagDefinitions::labels();
		$hints  = FeatureFlagDefinitions::descriptions();
		foreach ( FeatureFlagDefinitions::ordered_keys() as $key ) {
			$label = isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
			$hint  = isset( $hints[ $key ] ) ? $hints[ $key ] : '';
			self::field_flag_checkbox( $fg, $key, $label, ! empty( $f[ $key ] ), $hint );
		}

		echo '</tbody></table>';
	}

	/**
	 * Interactive log table (filters, export, detail drawer) + purge for {@see Constants::OPTION_ERROR_LOG}.
	 */
	private static function render_error_log_panel() {
		echo '<h3>' . esc_html__( 'Журнал ошибок (сервер)', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'События с витрины, сбои AJAX корзины и снимков. Полные пароли и nonce в записи не сохраняются; IP — только короткий хеш.', 'mp-sticky-custom-cart' ) . '</p>';

		if ( ! DiagnosticsAccess::can_manage() ) {
			echo '<div class="notice notice-warning inline"><p>';
			echo esc_html__( 'Просмотр журнала, экспорт и очистка доступны только пользователям с правом управления диагностикой плагина.', 'mp-sticky-custom-cart' );
			echo '</p></div>';
			return;
		}

		echo '<div id="mp-scc-error-log-root" class="mp-scc-error-log" hidden>';
		echo '<div class="mp-scc-error-log-toolbar">';

		echo '<label class="screen-reader-text" for="mp-scc-log-level">' . esc_html__( 'Уровень', 'mp-sticky-custom-cart' ) . '</label>';
		echo '<select id="mp-scc-log-level" class="mp-scc-error-log-filter">';
		echo '<option value="">' . esc_html__( 'Все уровни', 'mp-sticky-custom-cart' ) . '</option>';
		echo '<option value="debug">debug</option>';
		echo '<option value="info">info</option>';
		echo '<option value="warn">warn</option>';
		echo '<option value="error">error</option>';
		echo '</select> ';

		echo '<label class="screen-reader-text" for="mp-scc-log-date-from">' . esc_html__( 'С даты', 'mp-sticky-custom-cart' ) . '</label>';
		echo '<input type="date" id="mp-scc-log-date-from" class="mp-scc-error-log-filter" /> ';
		echo '<label class="screen-reader-text" for="mp-scc-log-date-to">' . esc_html__( 'По дату', 'mp-sticky-custom-cart' ) . '</label>';
		echo '<input type="date" id="mp-scc-log-date-to" class="mp-scc-error-log-filter" /> ';

		echo '<label class="screen-reader-text" for="mp-scc-log-source">' . esc_html__( 'Источник / endpoint', 'mp-sticky-custom-cart' ) . '</label>';
		echo '<input type="search" id="mp-scc-log-source" class="regular-text mp-scc-error-log-filter" placeholder="' . esc_attr__( 'Источник или endpoint', 'mp-sticky-custom-cart' ) . '" /> ';

		echo '<label class="screen-reader-text" for="mp-scc-log-search">' . esc_html__( 'Поиск в записи', 'mp-sticky-custom-cart' ) . '</label>';
		echo '<input type="search" id="mp-scc-log-search" class="regular-text mp-scc-error-log-filter" placeholder="' . esc_attr__( 'Текст в сообщении / payload', 'mp-sticky-custom-cart' ) . '" /> ';

		echo '<button type="button" class="button button-primary" id="mp-scc-log-apply">' . esc_html__( 'Применить', 'mp-sticky-custom-cart' ) . '</button> ';
		echo '<button type="button" class="button" id="mp-scc-log-reset">' . esc_html__( 'Сбросить', 'mp-sticky-custom-cart' ) . '</button>';

		echo '</div>';

		echo '<p class="mp-scc-error-log-meta"><span id="mp-scc-log-status"></span></p>';

		echo '<div class="mp-scc-error-log-table-wrap">';
		echo '<table class="widefat striped mp-scc-error-log-table">';
		echo '<thead><tr>';
		echo '<th scope="col">' . esc_html__( 'Время (UTC)', 'mp-sticky-custom-cart' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Уровень', 'mp-sticky-custom-cart' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Источник', 'mp-sticky-custom-cart' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Endpoint', 'mp-sticky-custom-cart' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Код', 'mp-sticky-custom-cart' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Сообщение', 'mp-sticky-custom-cart' ) . '</th>';
		echo '</tr></thead>';
		echo '<tbody id="mp-scc-log-tbody"><tr class="mp-scc-log-placeholder"><td colspan="6">' . esc_html__( 'Загрузка…', 'mp-sticky-custom-cart' ) . '</td></tr></tbody>';
		echo '</table>';
		echo '</div>';

		echo '<p class="mp-scc-error-log-pagination">';
		echo '<label for="mp-scc-log-per-page" class="screen-reader-text">' . esc_html__( 'На странице', 'mp-sticky-custom-cart' ) . '</label>';
		echo '<select id="mp-scc-log-per-page">';
		echo '<option value="25">25</option>';
		echo '<option value="50" selected>50</option>';
		echo '<option value="100">100</option>';
		echo '</select> ';
		echo '<button type="button" class="button" id="mp-scc-log-prev">' . esc_html__( 'Назад', 'mp-sticky-custom-cart' ) . '</button> ';
		echo '<button type="button" class="button" id="mp-scc-log-next">' . esc_html__( 'Вперёд', 'mp-sticky-custom-cart' ) . '</button>';
		echo '<span id="mp-scc-log-page-info" class="mp-scc-error-log-page-info"></span>';
		echo '</p>';

		echo '<p class="mp-scc-error-log-actions">';
		echo '<button type="button" class="button" id="mp-scc-log-export-csv">' . esc_html__( 'Экспорт CSV', 'mp-sticky-custom-cart' ) . '</button> ';
		echo '<button type="button" class="button" id="mp-scc-log-export-json">' . esc_html__( 'Экспорт JSON', 'mp-sticky-custom-cart' ) . '</button> ';
		submit_button(
			__( 'Очистить журнал', 'mp-sticky-custom-cart' ),
			'delete small',
			'submit',
			false,
			array(
				'form'    => self::ERROR_LOG_PURGE_FORM_ID,
				'style'   => 'margin-left:8px;',
				'onclick' => 'return confirm(' . wp_json_encode( __( 'Удалить все записи журнала?', 'mp-sticky-custom-cart' ) ) . ');',
			)
		);
		echo '</p>';

		echo '<div id="mp-scc-error-log-drawer" class="mp-scc-error-log-drawer" aria-hidden="true">';
		echo '<div class="mp-scc-error-log-drawer__inner">';
		echo '<div class="mp-scc-error-log-drawer__head">';
		echo '<h4 id="mp-scc-log-drawer-title">' . esc_html__( 'Запись', 'mp-sticky-custom-cart' ) . '</h4>';
		echo '<button type="button" class="button-link mp-scc-error-log-drawer__close" id="mp-scc-log-drawer-close" aria-label="' . esc_attr__( 'Закрыть', 'mp-sticky-custom-cart' ) . '"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>';
		echo '</div>';
		echo '<pre id="mp-scc-log-drawer-body" class="mp-scc-error-log-drawer__body"></pre>';
		echo '</div></div>';
		echo '<div id="mp-scc-error-log-backdrop" class="mp-scc-error-log-backdrop" aria-hidden="true"></div>';

		echo '</div>';
		echo '<script>document.getElementById("mp-scc-error-log-root").hidden=false;</script>';
	}

	/**
	 * @param string $opt Option array name.
	 * @param string $section Section key.
	 * @param string $field Field key.
	 */
	private static function field_checkbox( $opt, $section, $field, $label, $checked, $help = '' ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		echo '<tr><th scope="row">' . esc_html( $label ) . self::help_tip_button( $help ) . '</th><td>';
		printf(
			'<input type="hidden" name="%s" value="0" />',
			esc_attr( $name )
		);
		printf(
			'<label><input type="checkbox" name="%s" value="1" %s /> %s</label>',
			esc_attr( $name ),
			checked( $checked, true, false ),
			esc_html__( 'Включено', 'mp-sticky-custom-cart' )
		);
		echo '</td></tr>';
	}

	/**
	 * Hidden 0 + checkbox for booleans that must post when unchecked.
	 *
	 * @param string $opt Option array name.
	 * @param string $section Section key.
	 * @param string $field Field key.
	 */
	private static function field_hidden_then_checkbox( $opt, $section, $field, $label, $checked ) {
		self::field_checkbox( $opt, $section, $field, $label, $checked );
	}

	/**
	 * @param string $fg          Feature flags option name.
	 * @param string $description Help text under the control.
	 */
	private static function field_flag_checkbox( $fg, $key, $label, $checked, $description = '' ) {
		$name = sprintf( '%s[%s]', $fg, $key );
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
		printf( '<input type="hidden" name="%s" value="0" />', esc_attr( $name ) );
		printf(
			'<label><input type="checkbox" name="%s" value="1" %s /> %s</label>',
			esc_attr( $name ),
			checked( $checked, true, false ),
			esc_html__( 'Включено', 'mp-sticky-custom-cart' )
		);
		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * @param string $opt Option array name.
	 * @param string $section Section key.
	 * @param string $field Field key.
	 */
	private static function field_number( $opt, $section, $field, $label, $value, $help = '', $preview_meta = null ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>' . self::help_tip_button( $help ) . '</th><td>';
		$pv = is_array( $preview_meta ) ? self::preview_data_attr( $preview_meta ) : '';
		$num_attrs = '';
		if ( is_array( $preview_meta ) ) {
			if ( isset( $preview_meta['min'] ) && is_numeric( $preview_meta['min'] ) ) {
				$num_attrs .= ' min="' . esc_attr( (string) $preview_meta['min'] ) . '"';
			}
			if ( isset( $preview_meta['max'] ) && is_numeric( $preview_meta['max'] ) ) {
				$num_attrs .= ' max="' . esc_attr( (string) $preview_meta['max'] ) . '"';
			}
			if ( array_key_exists( 'step', $preview_meta ) && ( is_numeric( $preview_meta['step'] ) || ( is_string( $preview_meta['step'] ) && '' !== $preview_meta['step'] ) ) ) {
				$num_attrs .= ' step="' . esc_attr( (string) $preview_meta['step'] ) . '"';
			}
		}
		printf(
			'<input type="number" class="small-text" id="%1$s" name="%1$s" value="%2$s"%3$s%4$s />',
			esc_attr( $name ),
			esc_attr( (string) $value ),
			$pv, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in preview_data_attr
			$num_attrs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr
		);
		echo '</td></tr>';
	}

	/**
	 * @param string $opt Option array name.
	 * @param string $section Section key.
	 * @param string $field Field key.
	 */
	private static function field_text( $opt, $section, $field, $label, $value, $help = '', array $extra = array() ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>' . self::help_tip_button( $help ) . '</th><td>';
		$attrs     = '';
		$input_cls = isset( $extra['input_class'] ) && is_string( $extra['input_class'] ) ? trim( $extra['input_class'] ) : 'regular-text';
		if ( isset( $extra['maxlength'] ) ) {
			$attrs .= ' maxlength="' . (int) $extra['maxlength'] . '"';
		}
		if ( isset( $extra['preview'] ) && is_array( $extra['preview'] ) ) {
			$attrs .= self::preview_data_attr( $extra['preview'] );
		}
		printf(
			'<input type="text" class="%4$s" id="%1$s" name="%1$s" value="%2$s"%3$s />',
			esc_attr( $name ),
			esc_attr( $value ),
			$attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- maxlength is int, attribute name fixed
			esc_attr( $input_cls )
		);
		echo '</td></tr>';
	}

	/**
	 * @param string $opt Option array name.
	 * @param string $section Section key.
	 * @param string $field Field key.
	 * @param array<string, mixed> $extra Extra attrs: rows, maxlength.
	 */
	private static function field_textarea( $opt, $section, $field, $label, $value, $help = '', array $extra = array() ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		$rows = isset( $extra['rows'] ) && is_numeric( $extra['rows'] ) ? max( 2, (int) $extra['rows'] ) : 5;
		$attrs = '';
		if ( isset( $extra['maxlength'] ) && is_numeric( $extra['maxlength'] ) ) {
			$attrs .= ' maxlength="' . (int) $extra['maxlength'] . '"';
		}
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>' . self::help_tip_button( $help ) . '</th><td>';
		printf(
			'<textarea class="large-text code" id="%1$s" name="%1$s" rows="%2$d"%3$s>%4$s</textarea>',
			esc_attr( $name ),
			(int) $rows,
			$attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- maxlength is numeric
			esc_textarea( (string) $value )
		);
		echo '</td></tr>';
	}

	/**
	 * Radio grid of built-in SVG cart icon presets (10 variants).
	 *
	 * @param string $opt         Settings option name.
	 * @param string $current_id  Selected preset slug.
	 */
	private static function field_catalog_cart_icon_preset_grid( $opt, $current_id ) {
		$current = CatalogCartIconPresets::normalize( $current_id );
		$name    = sprintf( '%s[catalog][catalog_cart_icon_preset]', $opt );
		$labels  = CatalogCartIconPresets::labels();

		echo '<tr class="mp-scc-cart-icon-preset-row"><th scope="row">' . esc_html__( 'Вид значка корзины', 'mp-sticky-custom-cart' ) . '</th><td>';
		echo '<fieldset class="mp-scc-cart-icon-preset-grid"><legend class="screen-reader-text">' . esc_html__( 'Вид значка корзины', 'mp-sticky-custom-cart' ) . '</legend>';

		foreach ( CatalogCartIconPresets::IDS as $id ) {
			$label_text = isset( $labels[ $id ] ) ? $labels[ $id ] : $id;
			$rid        = 'mp-scc-cart-preset-' . $id;
			echo '<div class="mp-scc-cart-icon-preset-item">';
			printf(
				'<input type="radio" class="mp-scc-cart-icon-preset-input" id="%1$s" name="%2$s" value="%3$s"%4$s />',
				esc_attr( $rid ),
				esc_attr( $name ),
				esc_attr( $id ),
				checked( $current, $id, false )
			);
			echo '<label class="mp-scc-cart-icon-preset-card" for="' . esc_attr( $rid ) . '">';
			echo '<span class="mp-scc-cart-icon-preset-preview" aria-hidden="true">';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted SVG from plugin presets.
			echo CatalogCartIconPresets::svg_markup( $id, 40, 40, 1.75 );
			echo '</span>';
			echo '<span class="mp-scc-cart-icon-preset-name">' . esc_html( $label_text ) . '</span>';
			echo '</label>';
			echo '</div>';
		}

		echo '</fieldset>';
		echo '<p class="description">' . esc_html__( 'Десять векторных (SVG) значков в стиле currentColor на витрине. Ниже задаётся толщина линии для контуров.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '</td></tr>';
	}

	/**
	 * @param string $opt Option array name.
	 * @param string $section Section key.
	 * @param string $field Field key.
	 */
	private static function field_color( $opt, $section, $field, $label, $value, $help = '', $preview_meta = null ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>' . self::help_tip_button( $help ) . '</th><td>';
		$pv = is_array( $preview_meta ) ? self::preview_data_attr( $preview_meta ) : '';
		printf(
			'<input type="text" class="mp-scc-color" id="%1$s" name="%1$s" value="%2$s" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"%3$s />',
			esc_attr( $name ),
			esc_attr( $value ),
			$pv // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		echo '</td></tr>';
	}

	/**
	 * Unitless line-height (float) with live preview binding.
	 *
	 * @param array<string, mixed> $preview_meta Preview JSON for JS.
	 */
	private static function field_line_height( $opt, $section, $field, $label, $value, $help = '', array $preview_meta = array() ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>' . self::help_tip_button( $help ) . '</th><td>';
		$pv = isset( $preview_meta['var'] ) ? self::preview_data_attr( $preview_meta ) : '';
		printf(
			'<input type="number" class="small-text" step="0.05" min="1" max="2.5" id="%1$s" name="%1$s" value="%2$s"%3$s />',
			esc_attr( $name ),
			esc_attr( is_numeric( $value ) ? (string) (float) $value : '1.2' ),
			$pv // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		echo '</td></tr>';
	}

	/**
	 * @param string               $opt Option array name.
	 * @param string               $section Section key.
	 * @param string               $field Field key.
	 * @param array<string, string> $options Value => label.
	 */
	private static function field_select( $opt, $section, $field, $label, $current, array $options, $help = '', $preview_meta = null ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>' . self::help_tip_button( $help ) . '</th><td>';
		$pv = is_array( $preview_meta ) ? self::preview_data_attr( $preview_meta ) : '';
		echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . $pv . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- preview_data_attr is escaped
		foreach ( $options as $val => $opt_label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $val ),
				selected( $current, $val, false ),
				esc_html( $opt_label )
			);
		}
		echo '</select></td></tr>';
	}

	/**
	 * Effective UI strings (fallbacks + translation filters), same as storefront / {@see mpSccData.labels}.
	 */
	private static function render_labels_preview_panel() {
		echo '<h3>' . esc_html__( 'Предпросмотр текстов', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Как сейчас отдаётся на сайте (после сохранения настроек обновите страницу).', 'mp-sticky-custom-cart' ) . '</p>';

		$labels = OptionResolver::get_labels();
		$rows   = array(
			UiLabelsDefaults::KEY_CATALOG_CART_ICON  => __( 'Иконка «в корзину» в лупе (aria)', 'mp-sticky-custom-cart' ),
			UiLabelsDefaults::KEY_MORE_INFO          => __( 'Текст кнопки «Подробнее о товаре»', 'mp-sticky-custom-cart' ),
			UiLabelsDefaults::KEY_OUT_OF_STOCK       => __( 'Сообщение «Товара нет в наличии»', 'mp-sticky-custom-cart' ),
			UiLabelsDefaults::KEY_CLEAR_CART         => __( 'Текст кнопки «Очистить корзину»', 'mp-sticky-custom-cart' ),
			UiLabelsDefaults::KEY_CLEAR_CART_IN_PROGRESS => __( 'Текст при очистке (иконки, aria-busy)', 'mp-sticky-custom-cart' ),
			UiLabelsDefaults::KEY_CART_CLEARED       => __( 'Сообщение после очистки корзины', 'mp-sticky-custom-cart' ),
			UiLabelsDefaults::KEY_CHECKOUT            => __( 'Текст кнопки «Оформить заказ»', 'mp-sticky-custom-cart' ),
			UiLabelsDefaults::KEY_VARIATION_REQUIRED => __( 'Сообщение «Выберите вариацию товара»', 'mp-sticky-custom-cart' ),
			UiLabelsDefaults::KEY_SINGLE_ADD_SUCCESS   => __( 'Сообщение после добавления со страницы товара', 'mp-sticky-custom-cart' ),
			UiLabelsDefaults::KEY_DRAWER_EMPTY       => __( 'Пустая корзина (drawer)', 'mp-sticky-custom-cart' ),
			UiLabelsDefaults::KEY_DRAWER_EMPTY_HINT  => __( 'Подсказка под пустой корзиной (drawer)', 'mp-sticky-custom-cart' ),
			UiLabelsDefaults::KEY_DRAWER_REMOVE_LINE => __( 'Кнопка удаления позиции (aria)', 'mp-sticky-custom-cart' ),
			UiLabelsDefaults::KEY_LINE_REMOVED       => __( 'Сообщение после удаления позиции', 'mp-sticky-custom-cart' ),
		);

		echo '<table class="widefat striped mp-scc-label-preview"><thead><tr>';
		echo '<th scope="col">' . esc_html__( 'Назначение', 'mp-sticky-custom-cart' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Итоговый текст', 'mp-sticky-custom-cart' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $rows as $key => $title ) {
			$text = isset( $labels[ $key ] ) ? $labels[ $key ] : '';
			echo '<tr><td>' . esc_html( $title ) . '</td><td><code>' . esc_html( $text ) . '</code></td></tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Visual preview of the drawer empty state (labels from {@see OptionResolver::get_labels()}).
	 */
	private static function render_drawer_empty_preview() {
		$labels = OptionResolver::get_labels();
		$title  = isset( $labels[ UiLabelsDefaults::KEY_DRAWER_EMPTY ] ) ? (string) $labels[ UiLabelsDefaults::KEY_DRAWER_EMPTY ] : '';
		$hint   = isset( $labels[ UiLabelsDefaults::KEY_DRAWER_EMPTY_HINT ] ) ? (string) $labels[ UiLabelsDefaults::KEY_DRAWER_EMPTY_HINT ] : '';
		$hint   = trim( $hint );

		echo '<h3>' . esc_html__( 'Предпросмотр пустого drawer', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Блок, который показывается в раскрытой панели, когда в корзине нет позиций.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<div class="mp-scc-admin-drawer-empty-preview">';
		echo '<div class="mp-scc-drawer-empty mp-scc-drawer-empty--admin-preview" role="presentation">';
		echo '<div class="mp-scc-drawer-empty-visual" aria-hidden="true"><span class="mp-scc-drawer-empty-icon"></span></div>';
		echo '<p class="mp-scc-drawer-empty-title">' . esc_html( $title ) . '</p>';
		if ( '' !== $hint ) {
			echo '<p class="mp-scc-drawer-empty-hint">' . esc_html( $hint ) . '</p>';
		}
		echo '</div></div>';
	}

	/**
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
