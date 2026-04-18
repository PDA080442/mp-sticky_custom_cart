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

		ob_start();
		?>
<div id="mp-scc-sticky-root" class="mp-scc-root mp-scc-sticky-bar<?php echo esc_attr( $root_mods ); ?>" role="region" aria-label="<?php esc_attr_e( 'Shopping cart', 'mp-sticky-custom-cart' ); ?>" data-mp-scc-sticky-root data-mp-scc-cart-empty="<?php echo $empty ? '1' : '0'; ?>" data-mp-scc-sticky-tristate="<?php echo $tristate ? '1' : '0'; ?>">
	<div class="mp-scc-sticky-stack">
		<?php if ( $tristate && $drawer ) : ?>
		<div id="mp-scc-shell-panel-b" class="mp-scc-shell-panel-b" data-mp-scc-shell-panel-b role="region" aria-label="<?php esc_attr_e( 'Cart summary', 'mp-sticky-custom-cart' ); ?>" hidden aria-hidden="true">
			<div class="mp-scc-shell-panel-b__body">
				<div class="mp-scc-shell-panel-b__metrics" aria-live="polite" aria-atomic="true">
					<div class="mp-scc-shell-panel-b__row mp-scc-shell-panel-b__row--lines">
						<span class="mp-scc-shell-panel-b__label"><?php esc_html_e( 'Позиций', 'mp-sticky-custom-cart' ); ?></span>
						<span class="mp-scc-cart-count mp-scc-shell-panel-b__value" data-mp-scc-cart-count><?php echo esc_html( (string) $line_count ); ?></span>
					</div>
					<div class="mp-scc-shell-panel-b__row mp-scc-shell-panel-b__row--total">
						<span class="mp-scc-shell-panel-b__label"><?php esc_html_e( 'Сумма', 'mp-sticky-custom-cart' ); ?></span>
						<span class="mp-scc-cart-total mp-scc-shell-panel-b__subtotal" data-mp-scc-cart-total><?php echo wp_kses_post( $total ); ?></span>
					</div>
				</div>
				<div class="mp-scc-shell-panel-b__actions" role="toolbar" aria-orientation="horizontal" aria-label="<?php esc_attr_e( 'Cart actions', 'mp-sticky-custom-cart' ); ?>">
					<button type="button" class="mp-scc-btn mp-scc-btn--ghost mp-scc-shell-panel-b__icon-btn mp-scc-clear-cart<?php echo $empty ? ' mp-scc-clear-cart--disabled' : ''; ?>"
						data-mp-scc-clear-cart
						data-mp-scc-clear-aria-disabled="<?php echo esc_attr( $clear_aria_unavailable ); ?>"
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
						data-mp-scc-checkout-base="<?php echo esc_url( $checkout_base ); ?>"
						data-mp-scc-checkout-aria-disabled="<?php echo esc_attr( $checkout_aria_unavailable ); ?>"
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
				</div>
			</div>
		</div>
		<?php endif; ?>
		<?php if ( $drawer ) : ?>
		<div id="mp-scc-drawer" class="mp-scc-drawer<?php echo $empty ? ' mp-scc-drawer--empty' : ''; ?>" role="region" aria-labelledby="mp-scc-drawer-toggle" hidden data-mp-scc-drawer>
			<div class="mp-scc-drawer-inner<?php echo $empty ? ' mp-scc-drawer-inner--empty' : ''; ?>">
				<ul id="mp-scc-drawer-items" class="mp-scc-drawer-items" aria-label="<?php esc_attr_e( 'Products in cart', 'mp-sticky-custom-cart' ); ?>" data-mp-scc-drawer-items<?php echo $empty ? ' hidden aria-hidden="true"' : ''; ?>></ul>
				<div id="mp-scc-drawer-empty" class="mp-scc-drawer-empty" role="status"<?php echo $empty ? '' : ' hidden'; ?> data-mp-scc-drawer-empty>
					<div class="mp-scc-drawer-empty-visual" aria-hidden="true">
						<span class="mp-scc-drawer-empty-icon"></span>
					</div>
					<p class="mp-scc-drawer-empty-title"><?php echo esc_html( OptionResolver::get_label( UiLabelsDefaults::KEY_DRAWER_EMPTY ) ); ?></p>
					<p class="mp-scc-drawer-empty-hint"<?php echo '' === $drawer_empty_hint_trim ? ' hidden' : ''; ?>><?php echo esc_html( $drawer_empty_hint ); ?></p>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<div class="mp-scc-sticky-inner">
			<section class="mp-scc-sticky-summary" aria-label="<?php esc_attr_e( 'Cart summary', 'mp-sticky-custom-cart' ); ?>" aria-live="<?php echo $tristate ? 'off' : 'polite'; ?>" aria-atomic="true">
				<?php if ( $drawer ) : ?>
				<button type="button" id="mp-scc-drawer-toggle" class="mp-scc-drawer-toggle<?php echo $tristate ? ' mp-scc-drawer-toggle--fab' : ''; ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $aria_controls ); ?>" data-mp-scc-drawer-toggle<?php echo $tristate ? ' aria-haspopup="dialog"' : ''; ?>>
					<span class="mp-scc-sr-only"><?php echo esc_html__( 'Show or hide cart details', 'mp-sticky-custom-cart' ); ?></span>
					<span class="mp-scc-drawer-toggle-icon" aria-hidden="true"></span>
				</button>
				<?php endif; ?>
				<div class="mp-scc-sticky-summary-text">
					<span class="mp-scc-cart-count" data-mp-scc-cart-count><?php echo esc_html( (string) $line_count ); ?></span>
					<span class="mp-scc-sr-only" data-mp-scc-cart-qty-total><?php echo esc_html( sprintf( /* translators: %d: total quantity of all line items */ __( 'Total quantity in cart: %d', 'mp-sticky-custom-cart' ), $qty_total ) ); ?></span>
					<span class="mp-scc-cart-total" data-mp-scc-cart-total><?php echo wp_kses_post( $total ); ?></span>
				</div>
			</section>
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
	 * Inline SVG for panel B clear action (currentColor).
	 */
	private static function inline_svg_trash_icon() {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M9 3h6a1 1 0 011 1v1h5a1 1 0 010 2H4a1 1 0 010-2h5V4a1 1 0 011-1zm-3 6h12l-1.05 12.15A2 2 0 0115.96 23H8.04a2 2 0 01-1.99-1.85L6 9z"/></svg>';
	}

	/**
	 * Inline SVG for panel B checkout action (fill currentColor).
	 */
	private static function inline_svg_cart_icon() {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.15.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>';
	}
}
