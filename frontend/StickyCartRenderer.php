<?php
/**
 * Markup for the sticky cart shell (summary, actions, drawer scaffold).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

use MpStickyCustomCart\Core\CheckoutQueryPreserve;
use MpStickyCustomCart\Core\Config\FeatureFlagsDefaults;
use MpStickyCustomCart\Core\Config\UiLabelsDefaults;
use MpStickyCustomCart\Core\Contracts\StickyCartRendererInterface;
use MpStickyCustomCart\Core\OptionResolver;

defined( 'ABSPATH' ) || exit;

/**
 * @implements StickyCartRendererInterface
 */
final class StickyCartRenderer implements StickyCartRendererInterface {

	public function should_render() {
		return StickyCartVisibility::should_render_sticky();
	}

	public function register_hooks() {
		add_action( 'mp_sticky_custom_cart_render_sticky_cart', array( $this, 'maybe_render' ), 10 );

		/**
		 * Fires after sticky cart renderer hooks are registered.
		 */
		do_action( 'mp_sticky_custom_cart_sticky_cart_renderer_registered', $this );
	}

	/**
	 * Echoes markup when {@see should_render()} passes.
	 */
	public function maybe_render() {
		if ( ! $this->should_render() ) {
			return;
		}

		echo $this->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in template below
	}

	public function render() {
		$cart        = WC()->cart;
		$empty       = $cart->is_empty();
		$qty_total   = (int) $cart->get_cart_contents_count();
		$line_count  = $empty ? 0 : count( $cart->get_cart() );
		$total       = $empty ? wc_price( 0 ) : $cart->get_cart_subtotal();
		$total       = is_string( $total ) ? $total : wc_price( 0 );
		$drawer    = OptionResolver::get_flag( FeatureFlagsDefaults::KEY_STICKY_DRAWER_ENABLED, true );
		$tristate  = OptionResolver::get_flag( FeatureFlagsDefaults::KEY_STICKY_TRISTATE_ENABLED, false );
		$root_mods = trim( ( $empty ? ' mp-scc-sticky--empty' : '' ) . ( $tristate ? ' mp-scc-sticky--tristate' : '' ) );
		$show_sticky_bar_summary = ! $tristate || ! $drawer;

		$checkout_base = function_exists( 'wc_get_checkout_url' ) ? (string) wc_get_checkout_url() : '';
		$checkout_url  = CheckoutQueryPreserve::merge_request_into_url( $checkout_base );

		$checkout_label            = OptionResolver::get_label( UiLabelsDefaults::KEY_CHECKOUT );
		$checkout_aria_unavailable = sprintf(
			/* translators: %s: visible checkout button label */
			__( '%s — недоступно: корзина пуста', 'mp-sticky-custom-cart' ),
			$checkout_label
		);
		$clear_label               = OptionResolver::get_label( UiLabelsDefaults::KEY_CLEAR_CART );
		$clear_aria_unavailable    = sprintf(
			/* translators: %s: visible clear-cart button label */
			__( '%s — недоступно: корзина пуста', 'mp-sticky-custom-cart' ),
			$clear_label
		);
		$drawer_empty_hint         = OptionResolver::get_label( UiLabelsDefaults::KEY_DRAWER_EMPTY_HINT );
		$drawer_empty_hint_trim    = trim( $drawer_empty_hint );

		$aria_controls = 'mp-scc-drawer';
		if ( $tristate && $drawer ) {
			$aria_controls = 'mp-scc-shell-panel-b mp-scc-drawer';
		}

		$z = (int) OptionResolver::get_setting( 'sticky_cart.z_index', 100050 );
		$z = max( 1, min( 9999999, $z ) );
		// Critical layout when root prints from wp_body_open before themed wrappers: without fixed+bottom,
		// the shell sits in normal flow (top-left). CSS still owns glass, padding, tristate metrics.
		if ( $tristate ) {
			$root_style = sprintf(
				'position:fixed;z-index:%d;top:auto;left:auto;right:0;bottom:0;width:auto;max-width:none;margin:0;background:transparent;backdrop-filter:none;-webkit-backdrop-filter:none;box-shadow:none;border:none;pointer-events:auto;',
				$z
			);
		} else {
			$root_style = sprintf(
				'position:fixed;z-index:%d;inset:auto 0 0 0;width:100%%;max-width:100%%;margin:0;pointer-events:auto;',
				$z
			);
		}

		ob_start();
		?>
<div id="mp-scc-sticky-root" class="mp-scc-root mp-scc-sticky-bar<?php echo $root_mods ? ' ' . esc_attr( $root_mods ) : ''; ?>" style="<?php echo esc_attr( $root_style ); ?>" role="region" aria-label="<?php esc_attr_e( 'Shopping cart', 'mp-sticky-custom-cart' ); ?>" data-mp-scc-sticky-root data-mp-scc-cart-empty="<?php echo $empty ? '1' : '0'; ?>" data-mp-scc-sticky-tristate="<?php echo $tristate ? '1' : '0'; ?>">
	<div class="mp-scc-sticky-stack">
		<?php if ( $tristate && $drawer ) : ?>
		<div id="mp-scc-shell-panel-b" class="mp-scc-shell-panel-b" data-mp-scc-shell-panel-b role="region" aria-label="<?php esc_attr_e( 'Cart summary', 'mp-sticky-custom-cart' ); ?>" hidden aria-hidden="true">
			<div class="mp-scc-shell-panel-b__body">
				<?php echo self::tristate_panel_dismiss_button_markup( 'b' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div class="mp-scc-shell-panel-b__metrics" aria-live="polite" aria-atomic="true">
					<div class="mp-scc-shell-panel-b__row mp-scc-shell-panel-b__row--lines">
						<span class="mp-scc-shell-panel-b__label"><?php echo esc_html( OptionResolver::get_label( UiLabelsDefaults::KEY_TRISTATE_METRIC_LINES, __( 'Позиций', 'mp-sticky-custom-cart' ) ) ); ?></span>
						<span class="mp-scc-cart-count mp-scc-shell-panel-b__value" data-mp-scc-cart-line-count><?php echo esc_html( (string) $line_count ); ?></span>
					</div>
					<hr class="mp-scc-tristate-metrics-hr" aria-hidden="true" />
					<div class="mp-scc-shell-panel-b__row mp-scc-shell-panel-b__row--qty">
						<span class="mp-scc-shell-panel-b__label"><?php echo esc_html( OptionResolver::get_label( UiLabelsDefaults::KEY_TRISTATE_METRIC_QTY, __( 'Товаров', 'mp-sticky-custom-cart' ) ) ); ?></span>
						<span class="mp-scc-cart-qty-count mp-scc-shell-panel-b__value" data-mp-scc-cart-qty-count><?php echo esc_html( (string) (int) $qty_total ); ?></span>
					</div>
					<hr class="mp-scc-tristate-metrics-hr" aria-hidden="true" />
					<div class="mp-scc-shell-panel-b__row mp-scc-shell-panel-b__row--total">
						<span class="mp-scc-shell-panel-b__label"><?php echo esc_html( OptionResolver::get_label( UiLabelsDefaults::KEY_TRISTATE_METRIC_TOTAL, __( 'Сумма', 'mp-sticky-custom-cart' ) ) ); ?></span>
						<span class="mp-scc-cart-total mp-scc-shell-panel-b__subtotal" data-mp-scc-cart-total><?php echo wp_kses_post( $total ); ?></span>
					</div>
				</div>
				<?php
				echo self::tristate_icon_actions_toolbar_markup( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$empty,
					$checkout_url,
					$checkout_base,
					$clear_label,
					$clear_aria_unavailable,
					$checkout_label,
					$checkout_aria_unavailable,
					'open'
				);
				?>
			</div>
		</div>
		<?php endif; ?>
		<?php if ( $drawer ) : ?>
		<div id="mp-scc-drawer" class="mp-scc-drawer<?php echo $empty ? ' mp-scc-drawer--empty' : ''; ?><?php echo $tristate ? ' mp-scc-drawer--tristate-c' : ''; ?>" role="region" aria-labelledby="mp-scc-drawer-toggle" hidden data-mp-scc-drawer>
			<div class="mp-scc-drawer-inner<?php echo $empty ? ' mp-scc-drawer-inner--empty' : ''; ?>">
				<?php if ( $tristate ) : ?>
				<div class="mp-scc-drawer-c" data-mp-scc-drawer-tristate-c>
					<div class="mp-scc-drawer-c__top">
						<button type="button" class="mp-scc-btn mp-scc-btn--ghost mp-scc-shell-panel-b__icon-btn mp-scc-drawer-c__collapse mp-scc-shell-panel-b__toggle-c"
							data-mp-scc-toggle-c
							data-mp-scc-toggle-c-mode="close"
							title="<?php echo esc_attr__( 'Свернуть список товаров', 'mp-sticky-custom-cart' ); ?>"
							aria-label="<?php echo esc_attr__( 'Свернуть список товаров', 'mp-sticky-custom-cart' ); ?>"
						>
							<span class="mp-scc-shell-panel-b__icon-svg" aria-hidden="true"><?php echo self::inline_svg_chevron_down_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</button>
						<?php echo self::tristate_drawer_c_metrics_markup( $line_count, $qty_total, $total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo self::tristate_panel_dismiss_button_markup( 'c' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="mp-scc-drawer-c__scroll" data-mp-scc-drawer-lines-scroll>
						<ul id="mp-scc-drawer-items" class="mp-scc-drawer-items" aria-label="<?php esc_attr_e( 'Products in cart', 'mp-sticky-custom-cart' ); ?>" data-mp-scc-drawer-items<?php echo $empty ? ' hidden aria-hidden="true"' : ''; ?>></ul>
						<div id="mp-scc-drawer-empty" class="mp-scc-drawer-empty" role="status"<?php echo $empty ? '' : ' hidden'; ?> data-mp-scc-drawer-empty>
							<div class="mp-scc-drawer-empty-visual" aria-hidden="true">
								<span class="mp-scc-drawer-empty-icon"></span>
							</div>
							<p class="mp-scc-drawer-empty-title"><?php echo esc_html( OptionResolver::get_label( UiLabelsDefaults::KEY_DRAWER_EMPTY ) ); ?></p>
							<p class="mp-scc-drawer-empty-hint"<?php echo '' === $drawer_empty_hint_trim ? ' hidden' : ''; ?>><?php echo esc_html( $drawer_empty_hint ); ?></p>
						</div>
					</div>
					<?php
					echo self::tristate_icon_actions_toolbar_markup( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$empty,
						$checkout_url,
						$checkout_base,
						$clear_label,
						$clear_aria_unavailable,
						$checkout_label,
						$checkout_aria_unavailable,
						'none'
					);
					?>
				</div>
				<?php else : ?>
				<ul id="mp-scc-drawer-items" class="mp-scc-drawer-items" aria-label="<?php esc_attr_e( 'Products in cart', 'mp-sticky-custom-cart' ); ?>" data-mp-scc-drawer-items<?php echo $empty ? ' hidden aria-hidden="true"' : ''; ?>></ul>
				<div id="mp-scc-drawer-empty" class="mp-scc-drawer-empty" role="status"<?php echo $empty ? '' : ' hidden'; ?> data-mp-scc-drawer-empty>
					<div class="mp-scc-drawer-empty-visual" aria-hidden="true">
						<span class="mp-scc-drawer-empty-icon"></span>
					</div>
					<p class="mp-scc-drawer-empty-title"><?php echo esc_html( OptionResolver::get_label( UiLabelsDefaults::KEY_DRAWER_EMPTY ) ); ?></p>
					<p class="mp-scc-drawer-empty-hint"<?php echo '' === $drawer_empty_hint_trim ? ' hidden' : ''; ?>><?php echo esc_html( $drawer_empty_hint ); ?></p>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>
		<div class="mp-scc-sticky-inner">
			<section class="mp-scc-sticky-summary" aria-label="<?php esc_attr_e( 'Cart summary', 'mp-sticky-custom-cart' ); ?>" aria-live="<?php echo $tristate ? 'off' : 'polite'; ?>" aria-atomic="true">
				<?php if ( $drawer ) : ?>
				<button type="button" id="mp-scc-drawer-toggle" class="mp-scc-drawer-toggle<?php echo $tristate ? ' mp-scc-drawer-toggle--fab' : ''; ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $aria_controls ); ?>" data-mp-scc-drawer-toggle<?php echo $tristate ? ' aria-haspopup="dialog"' : ''; ?>>
					<span class="mp-scc-sr-only"><?php echo esc_html__( 'Show or hide cart details', 'mp-sticky-custom-cart' ); ?></span>
					<span class="mp-scc-drawer-toggle-icon" aria-hidden="true"></span>
					<?php if ( $tristate ) : ?>
					<span class="mp-scc-drawer-toggle-badge" data-mp-scc-cart-line-count aria-hidden="true"><?php echo esc_html( (string) $line_count ); ?></span>
					<?php endif; ?>
				</button>
				<?php endif; ?>
				<?php if ( $show_sticky_bar_summary ) : ?>
				<div class="mp-scc-sticky-summary-text">
					<span class="mp-scc-cart-count" data-mp-scc-cart-line-count><?php echo esc_html( (string) $line_count ); ?></span>
					<span class="mp-scc-sr-only" data-mp-scc-cart-qty-total><?php echo esc_html( sprintf( /* translators: %d: total quantity of all line items */ __( 'Total quantity in cart: %d', 'mp-sticky-custom-cart' ), $qty_total ) ); ?></span>
					<span class="mp-scc-cart-total" data-mp-scc-cart-total><?php echo wp_kses_post( $total ); ?></span>
				</div>
				<?php endif; ?>
			</section>
			<?php if ( $show_sticky_bar_summary ) : ?>
			<div class="mp-scc-sticky-actions" role="toolbar" aria-orientation="horizontal" aria-label="<?php esc_attr_e( 'Cart actions', 'mp-sticky-custom-cart' ); ?>" data-mp-scc-actions>
				<button type="button" class="mp-scc-btn mp-scc-btn--ghost mp-scc-clear-cart<?php echo $empty ? ' mp-scc-clear-cart--disabled' : ''; ?>"
					data-mp-scc-clear-cart
					data-mp-scc-clear-aria-disabled="<?php echo esc_attr( $clear_aria_unavailable ); ?>"
					<?php if ( $empty ) : ?>
					disabled
					aria-disabled="true"
					aria-label="<?php echo esc_attr( $clear_aria_unavailable ); ?>"
					<?php endif; ?>
				><?php echo esc_html( $clear_label ); ?></button>
				<a class="mp-scc-btn mp-scc-btn--primary mp-scc-checkout<?php echo $empty ? ' mp-scc-checkout--disabled' : ''; ?>"
					href="<?php echo $empty ? '#' : esc_url( $checkout_url ); ?>"
					data-mp-scc-checkout
					data-mp-scc-checkout-base="<?php echo esc_url( $checkout_base ); ?>"
					data-mp-scc-checkout-aria-disabled="<?php echo esc_attr( $checkout_aria_unavailable ); ?>"
					<?php if ( $empty ) : ?>
					aria-disabled="true"
					tabindex="-1"
					aria-label="<?php echo esc_attr( $checkout_aria_unavailable ); ?>"
					<?php endif; ?>
				><?php echo esc_html( $checkout_label ); ?></a>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>
		<?php
		$html = (string) ob_get_clean();

		/**
		 * Filters the full sticky cart HTML fragment before output.
		 *
		 * @param string               $html    Markup.
		 * @param array<string, mixed> $context Cart snapshot (count, empty, drawer flag).
		 */
		$context = array(
			'cart_empty'        => $empty,
			'cart_count'        => $qty_total,
			'line_count'        => $line_count,
			'drawer_enabled'    => $drawer,
			'tristate_enabled'  => $tristate,
		);

		return (string) apply_filters( 'mp_sticky_custom_cart_sticky_cart_html', $html, $context );
	}

	/**
	 * Close control for tri-state panel B and drawer C (dispatches shell CLOSE_B / CLOSE_C).
	 *
	 * @param string $which Host id for data-mp-scc-shell-dismiss: b|c.
	 */
	private static function tristate_panel_dismiss_button_markup( $which ) {
		$which = 'c' === $which ? 'c' : 'b';
		$label   = __( 'Закрыть', 'mp-sticky-custom-cart' );
		ob_start();
		?>
		<button type="button" class="mp-scc-tristate-dismiss"
			data-mp-scc-shell-dismiss="<?php echo esc_attr( $which ); ?>"
			title="<?php echo esc_attr( $label ); ?>"
			aria-label="<?php echo esc_attr( $label ); ?>"
		>
			<span class="mp-scc-tristate-dismiss__glyph" aria-hidden="true"><?php echo self::inline_svg_dismiss_x_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</button>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Metrics header for tri-state drawer C (same figures as panel B; dp §17.4).
	 *
	 * @param int    $line_count Number of cart lines (distinct rows).
	 * @param int    $qty_total  Sum of line quantities (pieces).
	 * @param string $total      Subtotal HTML.
	 */
	private static function tristate_drawer_c_metrics_markup( $line_count, $qty_total, $total ) {
		ob_start();
		?>
		<div class="mp-scc-drawer-c__metrics" aria-live="polite" aria-atomic="true">
			<div class="mp-scc-drawer-c__row mp-scc-drawer-c__row--lines">
				<span class="mp-scc-drawer-c__label"><?php echo esc_html( OptionResolver::get_label( UiLabelsDefaults::KEY_TRISTATE_METRIC_LINES, __( 'Позиций', 'mp-sticky-custom-cart' ) ) ); ?></span>
				<span class="mp-scc-cart-count mp-scc-drawer-c__value" data-mp-scc-cart-line-count><?php echo esc_html( (string) $line_count ); ?></span>
			</div>
			<hr class="mp-scc-tristate-metrics-hr" aria-hidden="true" />
			<div class="mp-scc-drawer-c__row mp-scc-drawer-c__row--qty">
				<span class="mp-scc-drawer-c__label"><?php echo esc_html( OptionResolver::get_label( UiLabelsDefaults::KEY_TRISTATE_METRIC_QTY, __( 'Товаров', 'mp-sticky-custom-cart' ) ) ); ?></span>
				<span class="mp-scc-cart-qty-count mp-scc-drawer-c__value" data-mp-scc-cart-qty-count><?php echo esc_html( (string) (int) $qty_total ); ?></span>
			</div>
			<hr class="mp-scc-tristate-metrics-hr" aria-hidden="true" />
			<div class="mp-scc-drawer-c__row mp-scc-drawer-c__row--total">
				<span class="mp-scc-drawer-c__label"><?php echo esc_html( OptionResolver::get_label( UiLabelsDefaults::KEY_TRISTATE_METRIC_TOTAL, __( 'Сумма', 'mp-sticky-custom-cart' ) ) ); ?></span>
				<span class="mp-scc-cart-total mp-scc-drawer-c__subtotal" data-mp-scc-cart-total><?php echo wp_kses_post( $total ); ?></span>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Icon-only clear + checkout toolbar (shared by panel B and drawer C; same title/aria-label, dp §17.4).
	 *
	 * @param bool   $empty                   Whether cart is empty.
	 * @param string $checkout_url            Checkout URL or #.
	 * @param string $checkout_base           Base checkout URL.
	 * @param string $clear_label             Clear button label.
	 * @param string $clear_aria_unavailable  Aria when clear disabled.
	 * @param string $checkout_label          Checkout label.
	 * @param string $checkout_aria_unavailable Aria when checkout disabled.
	 * @param string $c_toggle_mode            open|close|none explicit B<->C control mode.
	 */
	private static function tristate_icon_actions_toolbar_markup(
		$empty,
		$checkout_url,
		$checkout_base,
		$clear_label,
		$clear_aria_unavailable,
		$checkout_label,
		$checkout_aria_unavailable,
		$c_toggle_mode
	) {
		$c_toggle_mode = is_string( $c_toggle_mode ) ? $c_toggle_mode : 'none';
		if ( ! in_array( $c_toggle_mode, array( 'open', 'close', 'none' ), true ) ) {
			$c_toggle_mode = 'none';
		}
		ob_start();
		?>
		<div class="mp-scc-shell-panel-b__actions" role="toolbar" aria-orientation="horizontal" aria-label="<?php esc_attr_e( 'Cart actions', 'mp-sticky-custom-cart' ); ?>">
			<button type="button" class="mp-scc-btn mp-scc-btn--ghost mp-scc-shell-panel-b__icon-btn mp-scc-clear-cart<?php echo $empty ? ' mp-scc-clear-cart--disabled' : ''; ?>"
				data-mp-scc-clear-cart
				data-mp-scc-clear-label="<?php echo esc_attr( $clear_label ); ?>"
				data-mp-scc-clear-aria-disabled="<?php echo esc_attr( $clear_aria_unavailable ); ?>"
				title="<?php echo esc_attr( $empty ? $clear_aria_unavailable : $clear_label ); ?>"
				<?php if ( $empty ) : ?>
				disabled
				aria-disabled="true"
				aria-label="<?php echo esc_attr( $clear_aria_unavailable ); ?>"
				<?php else : ?>
				aria-label="<?php echo esc_attr( $clear_label ); ?>"
				<?php endif; ?>
			>
				<span class="mp-scc-shell-panel-b__icon-svg" aria-hidden="true"><?php echo self::inline_svg_trash_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</button>
			<a class="mp-scc-btn mp-scc-btn--primary mp-scc-shell-panel-b__icon-btn mp-scc-checkout<?php echo $empty ? ' mp-scc-checkout--disabled' : ''; ?>"
				href="<?php echo $empty ? '#' : esc_url( $checkout_url ); ?>"
				data-mp-scc-checkout
				data-mp-scc-checkout-label="<?php echo esc_attr( $checkout_label ); ?>"
				data-mp-scc-checkout-base="<?php echo esc_url( $checkout_base ); ?>"
				data-mp-scc-checkout-aria-disabled="<?php echo esc_attr( $checkout_aria_unavailable ); ?>"
				title="<?php echo esc_attr( $empty ? $checkout_aria_unavailable : $checkout_label ); ?>"
				<?php if ( $empty ) : ?>
				aria-disabled="true"
				tabindex="-1"
				aria-label="<?php echo esc_attr( $checkout_aria_unavailable ); ?>"
				<?php else : ?>
				aria-label="<?php echo esc_attr( $checkout_label ); ?>"
				<?php endif; ?>
			>
				<span class="mp-scc-shell-panel-b__icon-svg" aria-hidden="true"><?php echo self::inline_svg_cart_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</a>
			<?php if ( 'none' !== $c_toggle_mode ) : ?>
			<button type="button" class="mp-scc-btn mp-scc-btn--ghost mp-scc-shell-panel-b__icon-btn mp-scc-shell-panel-b__toggle-c"
				data-mp-scc-toggle-c
				data-mp-scc-toggle-c-mode="<?php echo esc_attr( $c_toggle_mode ); ?>"
				title="<?php echo esc_attr( 'open' === $c_toggle_mode ? __( 'Открыть список товаров', 'mp-sticky-custom-cart' ) : __( 'Свернуть список товаров', 'mp-sticky-custom-cart' ) ); ?>"
				aria-label="<?php echo esc_attr( 'open' === $c_toggle_mode ? __( 'Открыть список товаров', 'mp-sticky-custom-cart' ) : __( 'Свернуть список товаров', 'mp-sticky-custom-cart' ) ); ?>"
			>
				<span class="mp-scc-shell-panel-b__icon-svg" aria-hidden="true">
					<?php echo 'open' === $c_toggle_mode ? self::inline_svg_chevron_up_icon() : self::inline_svg_chevron_down_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
			</button>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Inline SVG for panel B clear action (currentColor).
	 */
	private static function inline_svg_trash_icon() {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="mp-scc-tristate-action-svg mp-scc-tristate-action-svg--trash"><path d="M9 3h6a1 1 0 011 1v1h5a1 1 0 010 2H4a1 1 0 010-2h5V4a1 1 0 011-1zm-3 6h12l-1.05 12.15A2 2 0 0115.96 23H8.04a2 2 0 01-1.99-1.85L6 9z"/></svg>';
	}

	/**
	 * Inline SVG for panel B checkout action (fill currentColor).
	 */
	private static function inline_svg_cart_icon() {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="mp-scc-tristate-action-svg mp-scc-tristate-action-svg--cart"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.15.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>';
	}

	/**
	 * Inline SVG for explicit B->C control (chevron up).
	 */
	private static function inline_svg_chevron_up_icon() {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="mp-scc-tristate-action-svg mp-scc-tristate-action-svg--chevron-up"><path d="M12 8.41l4.29 4.3a1 1 0 001.42-1.42l-5-5a1 1 0 00-1.42 0l-5 5a1 1 0 001.42 1.42L12 8.4z"/></svg>';
	}

	/**
	 * Inline SVG for explicit C->B control (chevron down).
	 */
	private static function inline_svg_chevron_down_icon() {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="mp-scc-tristate-action-svg mp-scc-tristate-action-svg--chevron-down"><path d="M12 15.59l-4.29-4.3a1 1 0 10-1.42 1.42l5 5a1 1 0 001.42 0l5-5a1 1 0 10-1.42-1.42L12 15.6z"/></svg>';
	}

	/**
	 * Inline SVG for dismiss (×) using currentColor stroke.
	 */
	private static function inline_svg_dismiss_x_icon() {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" class="mp-scc-tristate-dismiss__svg"><path d="M18 6L6 18M6 6l12 12"/></svg>';
	}
}
