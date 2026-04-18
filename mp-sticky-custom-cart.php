<?php
/**
 * Plugin Name:       MP Sticky Custom Cart
 * Description:       Sticky cart, catalog integration, and WooCommerce cart UI.
 * Version:           0.1.41
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Popravkin Danil
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mp-sticky-custom-cart
 * Domain Path:       /languages
 *
 * @package MpStickyCustomCart
 */

defined( 'ABSPATH' ) || exit;

// Keep in sync with the Version header above.
define( 'MP_STICKY_CUSTOM_CART_VERSION', '0.1.41' );
define( 'MP_STICKY_CUSTOM_CART_ASSET_VERSION', MP_STICKY_CUSTOM_CART_VERSION );

define( 'MP_STICKY_CUSTOM_CART_FILE', __FILE__ );
define( 'MP_STICKY_CUSTOM_CART_PATH', __DIR__ );
define( 'MP_STICKY_CUSTOM_CART_URL', plugin_dir_url( __FILE__ ) );
define( 'MP_STICKY_CUSTOM_CART_BASENAME', plugin_basename( __FILE__ ) );

require_once __DIR__ . '/core/Autoloader.php';

\MpStickyCustomCart\Core\Autoloader::register( __DIR__ );

\MpStickyCustomCart\Core\Plugin::register();
