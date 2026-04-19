<?php
/**
 * Sticky bar + drawer markup for the storefront.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the fixed bottom cart shell (summary + drawer region).
 */
interface StickyCartRendererInterface {

	/**
	 * Whether the sticky UI should be output on this request.
	 */
	public function should_render();

	/**
	 * Full HTML fragment for the sticky root (typically via {@see wp_body_open}, else `wp_footer` fallback).
	 *
	 * @return string Safe HTML.
	 */
	public function render();

	/**
	 * Optional: print wrapper hooks for themes; default may echo {@see render()}.
	 */
	public function register_hooks();
}
