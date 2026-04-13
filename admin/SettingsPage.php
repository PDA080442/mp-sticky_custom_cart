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
use MpStickyCustomCart\Core\Config\UiLabelsDefaults;
use MpStickyCustomCart\Core\Constants;
use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the menu page and renders option fields bound to Settings API.
 */
final class SettingsPage {

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
			'cart'        => __( 'Нижняя панель и drawer: внешний вид, редирект страницы корзины, уведомление после добавления в корзину.', 'mp-sticky-custom-cart' ),
			'wishlist'    => __( 'Отступы и слой иконки избранного относительно overlay каталога (см. docs/wishlist-integration.md).', 'mp-sticky-custom-cart' ),
			'styles'      => __( 'Базовые цвета панели; попадают в CSS-переменные темы плагина на витрине.', 'mp-sticky-custom-cart' ),
			'diagnostics' => __( 'Логи с клиента, срок хранения и переключатели функций (feature flags).', 'mp-sticky-custom-cart' ),
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
	}

	/**
	 * @param string $text Tooltip / aria-label (plain text).
	 * @return string HTML button (empty if no text).
	 */
	private static function help_tip_button( $text ) {
		$text = is_string( $text ) ? trim( $text ) : '';
		if ( '' === $text ) {
			return '';
		}
		return ' <button type="button" class="button-link mp-scc-help-tip" aria-label="' . esc_attr( $text ) . '" title="' . esc_attr( $text ) . '"><span class="dashicons dashicons-editor-help" aria-hidden="true"></span></button>';
	}

	/**
	 * How catalog options map to the storefront (for admins).
	 */
	private static function render_catalog_impact_notes() {
		echo '<div class="mp-scc-catalog-impact-notes">';
		echo '<p><strong>' . esc_html__( 'Как это влияет на витрину', 'mp-sticky-custom-cart' ) . '</strong></p>';
		echo '<ul class="ul-disc">';
		echo '<li>' . esc_html__( 'Поведение клика по миниатюре и селекторы задают, будет ли изображение добавлять simple-товар в корзину без перехода на страницу товара.', 'mp-sticky-custom-cart' ) . '</li>';
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
		echo '<p class="description">' . esc_html__( 'Упрощённый макет: на сайте вид зависит от темы и ширины колонки. Ниже — подпись и стили с учётом текущих чисел и CSS-переменных каталога.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<div class="mp-scc-admin-catalog-preview" style="' . esc_attr( $style ) . '">';
		echo '<div class="mp-scc-admin-catalog-preview__card" role="presentation">';
		echo '<div class="mp-scc-admin-catalog-preview__thumb">';
		echo '<div class="mp-scc-admin-catalog-preview__fake-img" aria-hidden="true"></div>';
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
		if ( 'add_to_cart' === $behavior && ! $img_atc_on ) {
			echo '<div class="notice notice-warning inline"><p>';
			echo esc_html__( 'Выбрано добавление в корзину по клику на изображение, но на вкладке «Служебное» выключен feature flag «Клик по изображению добавляет в корзину» — на сайте перехват не сработает.', 'mp-sticky-custom-cart' );
			echo '</p></div>';
		}

		echo '<h3>' . esc_html__( 'Клик по изображению в лупе', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
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
	private static function render_cart_tab( array $s, $opt ) {
		$c  = isset( $s['sticky_cart'] ) && is_array( $s['sticky_cart'] ) ? $s['sticky_cart'] : array();
		$cr = isset( $s['cart_route'] ) && is_array( $s['cart_route'] ) ? $s['cart_route'] : array();
		$n  = isset( $s['notices'] ) && is_array( $s['notices'] ) ? $s['notices'] : array();
		echo '<h2>' . esc_html__( 'Нижняя корзина (sticky)', 'mp-sticky-custom-cart' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Значения ниже попадают в CSS-переменные (--mp-scc-*) на сайте.', 'mp-sticky-custom-cart' ) . '</p>';

		echo '<h3>' . esc_html__( 'Панель: прозрачность, blur, скругление', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number(
			$opt,
			'sticky_cart',
			'z_index',
			__( 'z-index панели', 'mp-sticky-custom-cart' ),
			isset( $c['z_index'] ) ? (int) $c['z_index'] : 100050,
			__( 'Панель должна быть поверх контента, но не перекрывать важные модальные окна темы; при конфликте уменьшите или увеличьте значение.', 'mp-sticky-custom-cart' )
		);
		self::field_number( $opt, 'sticky_cart', 'surface_backdrop_blur_px', __( 'Blur подложки (px)', 'mp-sticky-custom-cart' ), isset( $c['surface_backdrop_blur_px'] ) ? (int) $c['surface_backdrop_blur_px'] : 14 );
		self::field_text(
			$opt,
			'sticky_cart',
			'surface_background_alpha',
			__( 'Прозрачность фона (0–1)', 'mp-sticky-custom-cart' ),
			isset( $c['surface_background_alpha'] ) ? (string) $c['surface_background_alpha'] : '0.78',
			__( 'Дробь от 0 до 1; вместе с blur задаёт «стекло» под панелью. Неверный формат при сохранении будет приведён к допустимому.', 'mp-sticky-custom-cart' )
		);
		self::field_number( $opt, 'sticky_cart', 'border_radius_px', __( 'Радиус скругления углов (px)', 'mp-sticky-custom-cart' ), isset( $c['border_radius_px'] ) ? (int) $c['border_radius_px'] : 14 );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Отступы панели: desktop', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'padding_x_desktop_px', __( 'Горизонтальный отступ (px)', 'mp-sticky-custom-cart' ), isset( $c['padding_x_desktop_px'] ) ? (int) $c['padding_x_desktop_px'] : 20 );
		self::field_number( $opt, 'sticky_cart', 'padding_y_desktop_px', __( 'Вертикальный отступ (px)', 'mp-sticky-custom-cart' ), isset( $c['padding_y_desktop_px'] ) ? (int) $c['padding_y_desktop_px'] : 14 );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Отступы панели: mobile', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'padding_x_mobile_px', __( 'Горизонтальный отступ (px)', 'mp-sticky-custom-cart' ), isset( $c['padding_x_mobile_px'] ) ? (int) $c['padding_x_mobile_px'] : 14 );
		self::field_number( $opt, 'sticky_cart', 'padding_y_mobile_px', __( 'Вертикальный отступ (px)', 'mp-sticky-custom-cart' ), isset( $c['padding_y_mobile_px'] ) ? (int) $c['padding_y_mobile_px'] : 12 );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Drawer и анимация', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'drawer_max_height_vh', __( 'Макс. высота drawer (vh)', 'mp-sticky-custom-cart' ), isset( $c['drawer_max_height_vh'] ) ? (int) $c['drawer_max_height_vh'] : 55 );
		self::field_number( $opt, 'sticky_cart', 'drawer_toggle_duration_ms', __( 'Длительность анимации drawer (мс)', 'mp-sticky-custom-cart' ), isset( $c['drawer_toggle_duration_ms'] ) ? (int) $c['drawer_toggle_duration_ms'] : 260 );
		self::field_text(
			$opt,
			'sticky_cart',
			'drawer_toggle_easing',
			__( 'Кривая easing (CSS, например cubic-bezier)', 'mp-sticky-custom-cart' ),
			isset( $c['drawer_toggle_easing'] ) ? (string) $c['drawer_toggle_easing'] : 'cubic-bezier(0.4, 0, 0.2, 1)',
			__( 'Стандартное значение Material-like; можно заменить на linear или свою cubic-bezier().', 'mp-sticky-custom-cart' )
		);
		self::field_number( $opt, 'sticky_cart', 'quantity_debounce_ms', __( 'Debounce изменения количества (мс)', 'mp-sticky-custom-cart' ), isset( $c['quantity_debounce_ms'] ) ? (int) $c['quantity_debounce_ms'] : 320 );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Типографика (счётчики и кнопки)', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'summary_font_size_px', __( 'Размер шрифта summary (px)', 'mp-sticky-custom-cart' ), isset( $c['summary_font_size_px'] ) ? (int) $c['summary_font_size_px'] : 15 );
		self::field_number( $opt, 'sticky_cart', 'summary_font_weight', __( 'Начертание summary (100–900)', 'mp-sticky-custom-cart' ), isset( $c['summary_font_weight'] ) ? (int) $c['summary_font_weight'] : 600 );
		self::field_number( $opt, 'sticky_cart', 'button_font_size_px', __( 'Размер шрифта кнопок (px)', 'mp-sticky-custom-cart' ), isset( $c['button_font_size_px'] ) ? (int) $c['button_font_size_px'] : 14 );
		self::field_number( $opt, 'sticky_cart', 'button_font_weight', __( 'Начертание кнопок (100–900)', 'mp-sticky-custom-cart' ), isset( $c['button_font_weight'] ) ? (int) $c['button_font_weight'] : 600 );
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
		$c = isset( $s['styles'] ) && is_array( $s['styles'] ) ? $s['styles'] : array();
		echo '<h2>' . esc_html__( 'Цвета (CSS-переменные позже)', 'mp-sticky-custom-cart' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_color( $opt, 'styles', 'color_text_primary', __( 'Текст основной', 'mp-sticky-custom-cart' ), isset( $c['color_text_primary'] ) ? (string) $c['color_text_primary'] : '#1a1a1a' );
		self::field_color( $opt, 'styles', 'color_surface_tint', __( 'Подложка панели', 'mp-sticky-custom-cart' ), isset( $c['color_surface_tint'] ) ? (string) $c['color_surface_tint'] : '#ffffff' );
		self::field_color( $opt, 'styles', 'color_button_primary', __( 'Кнопка основная', 'mp-sticky-custom-cart' ), isset( $c['color_button_primary'] ) ? (string) $c['color_button_primary'] : '#111111' );
		self::field_color( $opt, 'styles', 'color_button_primary_text', __( 'Текст на кнопке', 'mp-sticky-custom-cart' ), isset( $c['color_button_primary_text'] ) ? (string) $c['color_button_primary_text'] : '#ffffff' );
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
			__( 'Срок хранения записей клиентских ошибок в опции плагина; старые записи подрезаются при новых событиях.', 'mp-sticky-custom-cart' )
		);
		echo '</tbody></table>';

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
	private static function field_number( $opt, $section, $field, $label, $value, $help = '' ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>' . self::help_tip_button( $help ) . '</th><td>';
		printf(
			'<input type="number" class="small-text" id="%1$s" name="%1$s" value="%2$s" />',
			esc_attr( $name ),
			esc_attr( (string) $value )
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
		$attrs = '';
		if ( isset( $extra['maxlength'] ) ) {
			$attrs .= ' maxlength="' . (int) $extra['maxlength'] . '"';
		}
		printf(
			'<input type="text" class="regular-text" id="%1$s" name="%1$s" value="%2$s"%3$s />',
			esc_attr( $name ),
			esc_attr( $value ),
			$attrs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- maxlength is int, attribute name fixed
		);
		echo '</td></tr>';
	}

	/**
	 * @param string $opt Option array name.
	 * @param string $section Section key.
	 * @param string $field Field key.
	 */
	private static function field_color( $opt, $section, $field, $label, $value, $help = '' ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>' . self::help_tip_button( $help ) . '</th><td>';
		printf(
			'<input type="text" class="mp-scc-color" id="%1$s" name="%1$s" value="%2$s" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" />',
			esc_attr( $name ),
			esc_attr( $value )
		);
		echo '</td></tr>';
	}

	/**
	 * @param string               $opt Option array name.
	 * @param string               $section Section key.
	 * @param string               $field Field key.
	 * @param array<string, string> $options Value => label.
	 */
	private static function field_select( $opt, $section, $field, $label, $current, array $options, $help = '' ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>' . self::help_tip_button( $help ) . '</th><td>';
		echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '">';
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
			UiLabelsDefaults::KEY_MORE_INFO          => __( 'Текст кнопки «Подробнее о товаре»', 'mp-sticky-custom-cart' ),
			UiLabelsDefaults::KEY_OUT_OF_STOCK       => __( 'Сообщение «Товара нет в наличии»', 'mp-sticky-custom-cart' ),
			UiLabelsDefaults::KEY_CLEAR_CART         => __( 'Текст кнопки «Очистить корзину»', 'mp-sticky-custom-cart' ),
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
