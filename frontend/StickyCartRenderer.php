<?php
/**
 * Markup for the sticky cart shell (summary, actions, drawer scaffold).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Frontend;

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
		if ( ! OptionResolver::get_flag( FeatureFlagsDefaults::KEY_STICKY_CART_ENABLED, true ) ) {
			return false;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}

		if ( is_feed() || is_embed() ) {
			return false;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		/**
		 * Filters whether the sticky cart root is printed on this request.
		 *
		 * @param bool $show Default decision.
		 */
		return (bool) apply_filters( 'mp_sticky_custom_cart_should_render_sticky', true );
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
		$drawer = OptionResolver::get_flag( FeatureFlagsDefaults::KEY_STICKY_DRAWER_ENABLED, true );

		$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '';
		$checkout_url = is_string( $checkout_url ) ? $checkout_url : '';

		ob_start();
		?>
<div id="mp-scc-sticky-root" class="mp-scc-root mp-scc-sticky-bar" role="region" aria-label="<?php esc_attr_e( 'Shopping cart', 'mp-sticky-custom-cart' ); ?>" data-mp-scc-sticky-root>
	<div class="mp-scc-sticky-stack">
		<?php if ( $drawer ) : ?>
		<div id="mp-scc-drawer" class="mp-scc-drawer" role="region" aria-labelledby="mp-scc-drawer-toggle" hidden data-mp-scc-drawer>
			<div class="mp-scc-drawer-inner">
				<ul id="mp-scc-drawer-items" class="mp-scc-drawer-items" aria-label="<?php esc_attr_e( 'Products in cart', 'mp-sticky-custom-cart' ); ?>" data-mp-scc-drawer-items></ul>
				<div id="mp-scc-drawer-empty" class="mp-scc-drawer-empty" role="status"<?php echo $empty ? '' : ' hidden'; ?> data-mp-scc-drawer-empty>
					<p class="mp-scc-drawer-empty-text"><?php echo esc_html( OptionResolver::get_label( UiLabelsDefaults::KEY_DRAWER_EMPTY ) ); ?></p>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<div class="mp-scc-sticky-inner">
			<section class="mp-scc-sticky-summary" aria-label="<?php esc_attr_e( 'Cart summary', 'mp-sticky-custom-cart' ); ?>" aria-live="polite" aria-atomic="true">
				<?php if ( $drawer ) : ?>
				<button type="button" id="mp-scc-drawer-toggle" class="mp-scc-drawer-toggle" aria-expanded="false" aria-controls="mp-scc-drawer" data-mp-scc-drawer-toggle>
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
				<button type="button" class="mp-scc-btn mp-scc-btn--ghost mp-scc-clear-cart" data-mp-scc-clear-cart><?php echo esc_html( OptionResolver::get_label( UiLabelsDefaults::KEY_CLEAR_CART ) ); ?></button>
				<a class="mp-scc-btn mp-scc-btn--primary mp-scc-checkout" href="<?php echo esc_url( $checkout_url ); ?>" data-mp-scc-checkout><?php echo esc_html( OptionResolver::get_label( UiLabelsDefaults::KEY_CHECKOUT ) ); ?></a>
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
			'cart_empty'     => $empty,
			'cart_count'     => $qty_total,
			'line_count'     => $line_count,
			'drawer_enabled' => $drawer,
		);

		return (string) apply_filters( 'mp_sticky_custom_cart_sticky_cart_html', $html, $context );
	}
}
