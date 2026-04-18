<?php
/**
 * Capability for diagnostics log (view, export, purge); filterable for custom roles.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Admin;

use MpStickyCustomCart\Core\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * {@see Constants::CAPABILITY_MANAGE_DIAGNOSTICS} + {@see apply_filters} `mp_sticky_custom_cart_diagnostics_capability`.
 */
final class DiagnosticsAccess {

	/**
	 * Effective capability name (defaults to plugin cap on the administrator role).
	 *
	 * @return string
	 */
	public static function capability() {
		return apply_filters( 'mp_sticky_custom_cart_diagnostics_capability', Constants::CAPABILITY_MANAGE_DIAGNOSTICS );
	}

	/**
	 * Whether the current user may view/export/purge the diagnostics log.
	 *
	 * @return bool
	 */
	public static function can_manage() {
		return current_user_can( self::capability() );
	}

	private function __construct() {
	}
}
