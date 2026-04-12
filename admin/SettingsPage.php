<?php
/**
 * Admin settings screen: tabs Каталог, Корзина, Избранное, Стили, Служебное.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Admin;

use MpStickyCustomCart\Core\Config\FeatureFlagDefinitions;
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
	}

	/**
	 * Submenu under WooCommerce when available, else top-level.
	 */
	public static function add_menu_page() {
		$cap = 'manage_options';
		$slug = Constants::SLUG;

		$hook = add_submenu_page(
			'woocommerce',
			__( 'Sticky Cart', 'mp-sticky-custom-cart' ),
			__( 'Sticky Cart', 'mp-sticky-custom-cart' ),
			$cap,
			$slug,
			array( self::class, 'render' )
		);
		self::$hook_suffix = false === $hook ? '' : (string) $hook;
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

			<form action="options.php" method="post">
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
	 * @param array<string, mixed> $s   Settings tree.
	 * @param string               $opt Option key (name prefix).
	 */
	private static function render_catalog_tab( array $s, $opt ) {
		$c = isset( $s['catalog'] ) && is_array( $s['catalog'] ) ? $s['catalog'] : array();
		$l = isset( $s['labels'] ) && is_array( $s['labels'] ) ? $s['labels'] : array();
		echo '<h2>' . esc_html__( 'Каталог', 'mp-sticky-custom-cart' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';

		self::field_checkbox( $opt, 'catalog', 'hover_overlay_mobile_always', __( 'Показывать кнопку «Подробнее» на мобильных всегда', 'mp-sticky-custom-cart' ), ! empty( $c['hover_overlay_mobile_always'] ) );
		self::field_number( $opt, 'catalog', 'hover_animation_duration_ms', __( 'Длительность анимации hover (мс)', 'mp-sticky-custom-cart' ), isset( $c['hover_animation_duration_ms'] ) ? (int) $c['hover_animation_duration_ms'] : 220 );
		self::field_text( $opt, 'catalog', 'hover_animation_easing', __( 'Easing (CSS)', 'mp-sticky-custom-cart' ), isset( $c['hover_animation_easing'] ) ? (string) $c['hover_animation_easing'] : '' );
		self::field_select(
			$opt,
			'catalog',
			'hover_motion_preset',
			__( 'Пресет анимации', 'mp-sticky-custom-cart' ),
			isset( $c['hover_motion_preset'] ) ? (string) $c['hover_motion_preset'] : 'fade_slide',
			array(
				'fade_slide' => 'fade + slide',
				'fade'       => 'fade',
				'slide'      => 'slide',
			)
		);
		self::field_number( $opt, 'catalog', 'hover_slide_offset_px', __( 'Смещение slide (px)', 'mp-sticky-custom-cart' ), isset( $c['hover_slide_offset_px'] ) ? (int) $c['hover_slide_offset_px'] : 8 );
		self::field_number( $opt, 'catalog', 'hover_hide_delay_ms', __( 'Задержка перед скрытием overlay (мс)', 'mp-sticky-custom-cart' ), isset( $c['hover_hide_delay_ms'] ) ? (int) $c['hover_hide_delay_ms'] : 50 );
		self::field_checkbox( $opt, 'catalog', 'more_info_new_tab', __( 'Открывать «Подробнее» в новой вкладке', 'mp-sticky-custom-cart' ), ! empty( $c['more_info_new_tab'] ) );
		self::field_number( $opt, 'catalog', 'catalog_overlay_z_index', __( 'Z-index слоя «Подробнее»', 'mp-sticky-custom-cart' ), isset( $c['catalog_overlay_z_index'] ) ? (int) $c['catalog_overlay_z_index'] : 4 );
		echo '<tr><td colspan="2"><p class="description">' . esc_html__( 'Клик по изображению в каталоге: если пусто, используется стандартный селектор WooCommerce (см. документацию плагина).', 'mp-sticky-custom-cart' ) . '</p></td></tr>';
		self::field_text( $opt, 'catalog', 'image_click_selector', __( 'Селектор изображения карточки (CSS)', 'mp-sticky-custom-cart' ), isset( $c['image_click_selector'] ) ? (string) $c['image_click_selector'] : '' );
		self::field_text( $opt, 'catalog', 'card_root_selector', __( 'Корень карточки для состояний (closest, CSS)', 'mp-sticky-custom-cart' ), isset( $c['card_root_selector'] ) ? (string) $c['card_root_selector'] : 'li.product' );

		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Тексты интерфейса', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Пустое поле на сайте заменяется стандартной фразой из плагина.', 'mp-sticky-custom-cart' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_text( $opt, 'labels', 'more_info', __( 'Текст кнопки «Подробнее о товаре»', 'mp-sticky-custom-cart' ), isset( $l['more_info'] ) ? (string) $l['more_info'] : '' );
		self::field_text( $opt, 'labels', 'out_of_stock', __( 'Сообщение «Товара нет в наличии»', 'mp-sticky-custom-cart' ), isset( $l['out_of_stock'] ) ? (string) $l['out_of_stock'] : '' );
		self::field_text( $opt, 'labels', 'clear_cart', __( 'Текст кнопки «Очистить корзину»', 'mp-sticky-custom-cart' ), isset( $l['clear_cart'] ) ? (string) $l['clear_cart'] : '' );
		self::field_text( $opt, 'labels', 'cart_cleared', __( 'Сообщение после очистки корзины', 'mp-sticky-custom-cart' ), isset( $l['cart_cleared'] ) ? (string) $l['cart_cleared'] : '' );
		self::field_text( $opt, 'labels', 'checkout', __( 'Текст кнопки «Оформить заказ»', 'mp-sticky-custom-cart' ), isset( $l['checkout'] ) ? (string) $l['checkout'] : '' );
		self::field_text( $opt, 'labels', 'variation_required', __( 'Сообщение «Выберите вариацию товара»', 'mp-sticky-custom-cart' ), isset( $l['variation_required'] ) ? (string) $l['variation_required'] : '' );
		self::field_text( $opt, 'labels', 'drawer_empty', __( 'Пустая корзина (drawer)', 'mp-sticky-custom-cart' ), isset( $l['drawer_empty'] ) ? (string) $l['drawer_empty'] : '' );
		echo '</tbody></table>';

		self::render_labels_preview_panel();
	}

	/**
	 * @param array<string, mixed> $s
	 * @param string               $opt
	 */
	private static function render_cart_tab( array $s, $opt ) {
		$c = isset( $s['sticky_cart'] ) && is_array( $s['sticky_cart'] ) ? $s['sticky_cart'] : array();
		echo '<h2>' . esc_html__( 'Нижняя корзина (sticky)', 'mp-sticky-custom-cart' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Значения ниже попадают в CSS-переменные (--mp-scc-*) на сайте.', 'mp-sticky-custom-cart' ) . '</p>';

		echo '<h3>' . esc_html__( 'Панель: прозрачность, blur, скругление', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'z_index', __( 'z-index панели', 'mp-sticky-custom-cart' ), isset( $c['z_index'] ) ? (int) $c['z_index'] : 100050 );
		self::field_number( $opt, 'sticky_cart', 'surface_backdrop_blur_px', __( 'Blur подложки (px)', 'mp-sticky-custom-cart' ), isset( $c['surface_backdrop_blur_px'] ) ? (int) $c['surface_backdrop_blur_px'] : 14 );
		self::field_text( $opt, 'sticky_cart', 'surface_background_alpha', __( 'Прозрачность фона (0–1)', 'mp-sticky-custom-cart' ), isset( $c['surface_background_alpha'] ) ? (string) $c['surface_background_alpha'] : '0.78' );
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
		self::field_text( $opt, 'sticky_cart', 'drawer_toggle_easing', __( 'Кривая easing (CSS, например cubic-bezier)', 'mp-sticky-custom-cart' ), isset( $c['drawer_toggle_easing'] ) ? (string) $c['drawer_toggle_easing'] : 'cubic-bezier(0.4, 0, 0.2, 1)' );
		self::field_number( $opt, 'sticky_cart', 'quantity_debounce_ms', __( 'Debounce изменения количества (мс)', 'mp-sticky-custom-cart' ), isset( $c['quantity_debounce_ms'] ) ? (int) $c['quantity_debounce_ms'] : 320 );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Типографика (счётчики и кнопки)', 'mp-sticky-custom-cart' ) . '</h3>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::field_number( $opt, 'sticky_cart', 'summary_font_size_px', __( 'Размер шрифта summary (px)', 'mp-sticky-custom-cart' ), isset( $c['summary_font_size_px'] ) ? (int) $c['summary_font_size_px'] : 15 );
		self::field_number( $opt, 'sticky_cart', 'summary_font_weight', __( 'Начертание summary (100–900)', 'mp-sticky-custom-cart' ), isset( $c['summary_font_weight'] ) ? (int) $c['summary_font_weight'] : 600 );
		self::field_number( $opt, 'sticky_cart', 'button_font_size_px', __( 'Размер шрифта кнопок (px)', 'mp-sticky-custom-cart' ), isset( $c['button_font_size_px'] ) ? (int) $c['button_font_size_px'] : 14 );
		self::field_number( $opt, 'sticky_cart', 'button_font_weight', __( 'Начертание кнопок (100–900)', 'mp-sticky-custom-cart' ), isset( $c['button_font_weight'] ) ? (int) $c['button_font_weight'] : 600 );
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
		self::field_number( $opt, 'wishlist_ui', 'heart_icon_z_index', __( 'Z-index иконки избранного (выше overlay)', 'mp-sticky-custom-cart' ), isset( $c['heart_icon_z_index'] ) ? (int) $c['heart_icon_z_index'] : 6 );
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
		self::field_number( $opt, 'diagnostics', 'log_retention_days', __( 'Хранить логи (дней)', 'mp-sticky-custom-cart' ), isset( $d['log_retention_days'] ) ? (int) $d['log_retention_days'] : 14 );
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
	private static function field_checkbox( $opt, $section, $field, $label, $checked ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
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
	private static function field_number( $opt, $section, $field, $label, $value ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>';
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
	private static function field_text( $opt, $section, $field, $label, $value ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>';
		printf(
			'<input type="text" class="regular-text" id="%1$s" name="%1$s" value="%2$s" />',
			esc_attr( $name ),
			esc_attr( $value )
		);
		echo '</td></tr>';
	}

	/**
	 * @param string $opt Option array name.
	 * @param string $section Section key.
	 * @param string $field Field key.
	 */
	private static function field_color( $opt, $section, $field, $label, $value ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>';
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
	private static function field_select( $opt, $section, $field, $label, $current, array $options ) {
		$name = sprintf( '%s[%s][%s]', $opt, $section, $field );
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>';
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
			UiLabelsDefaults::KEY_DRAWER_EMPTY       => __( 'Пустая корзина (drawer)', 'mp-sticky-custom-cart' ),
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
	 * Not instantiable.
	 */
	private function __construct() {
	}
}
