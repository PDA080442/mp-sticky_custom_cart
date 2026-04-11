<?php
/**
 * Structured logging for AJAX/JS diagnostics (admin-readable).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Persists or streams log entries with severity and context.
 */
interface LoggingServiceInterface {

	public const LEVEL_DEBUG = 'debug';
	public const LEVEL_INFO  = 'info';
	public const LEVEL_WARN  = 'warn';
	public const LEVEL_ERROR = 'error';

	/**
	 * @param string               $level   One of LEVEL_* constants.
	 * @param string               $message Human-readable message.
	 * @param array<string, mixed> $context Sanitized metadata (URL, endpoint, payload refs).
	 */
	public function log( $level, $message, array $context = array() );

	/**
	 * Read recent entries for admin UI (paged or capped).
	 *
	 * @param array<string, mixed> $args Query args (limit, offset, level).
	 * @return array<int, array<string, mixed>>
	 */
	public function query( array $args = array() );

	/**
	 * Remove stored logs (admin tool).
	 */
	public function purge();
}
