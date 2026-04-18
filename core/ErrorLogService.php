<?php
/**
 * Persistent error/diagnostics log in {@see Constants::OPTION_ERROR_LOG} (option array).
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

use MpStickyCustomCart\Core\Contracts\LoggingServiceInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Implements {@see LoggingServiceInterface}: severity, retention, size cap, sanitized payloads.
 */
final class ErrorLogService implements LoggingServiceInterface {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Keys removed or redacted from payloads/context (case-insensitive substring match).
	 *
	 * @var list<string>
	 */
	private static $sensitive_key_fragments = array(
		'password',
		'passwd',
		'secret',
		'token',
		'credit',
		'card',
		'cvv',
		'cookie',
		'authorization',
		'nonce',
		'_ajax_nonce',
	);

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @param string               $level   One of LEVEL_*.
	 * @param string               $message Human-readable message.
	 * @param array<string, mixed> $context Keys: source, endpoint, code, payload (array), extra nested context.
	 */
	public function log( $level, $message, array $context = array() ) {
		if ( ! OptionResolver::get_setting( 'diagnostics.client_error_logging', true ) ) {
			return;
		}

		$level = sanitize_key( (string) $level );
		if ( ! in_array( $level, array( self::LEVEL_DEBUG, self::LEVEL_INFO, self::LEVEL_WARN, self::LEVEL_ERROR ), true ) ) {
			$level = self::LEVEL_ERROR;
		}

		$entry = array(
			'v'       => 2,
			'ts'      => time(),
			'level'   => $level,
			'message' => substr( wp_strip_all_tags( (string) $message ), 0, 2000 ),
		);

		if ( isset( $context['source'] ) ) {
			$entry['source'] = sanitize_key( (string) $context['source'] );
		}
		if ( isset( $context['endpoint'] ) ) {
			$entry['endpoint'] = preg_replace( '/[^a-z0-9_\-]/', '', (string) $context['endpoint'] );
		}
		if ( isset( $context['code'] ) ) {
			$entry['code'] = sanitize_key( (string) $context['code'] );
		}

		$user = wp_get_current_user();
		if ( $user && $user->ID > 0 ) {
			$entry['user_id'] = (int) $user->ID;
			$roles            = $user->roles;
			$entry['role']    = is_array( $roles ) && isset( $roles[0] ) ? sanitize_key( (string) $roles[0] ) : '';
		} else {
			$entry['user_id'] = 0;
			$entry['guest']   = true;
		}

		if ( ! empty( $context['payload'] ) && is_array( $context['payload'] ) ) {
			$entry['payload'] = $this->sanitize_tree( $context['payload'], 4 );
		}

		$extra = isset( $context['context'] ) && is_array( $context['context'] ) ? $context['context'] : array();
		if ( isset( $context['extra'] ) && is_array( $context['extra'] ) ) {
			$extra = array_merge( $extra, $context['extra'] );
		}
		if ( array() !== $extra ) {
			$entry['context'] = $this->sanitize_tree( $extra, 4 );
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
		if ( '' !== $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$entry['ip_hash'] = substr( hash( 'sha256', $ip . wp_salt( 'auth' ) ), 0, 12 );
		}

		/**
		 * Filters a structured log entry before append (server-side diagnostics).
		 *
		 * @param array<string, mixed> $entry Full entry.
		 */
		$entry = apply_filters( 'mp_sticky_custom_cart_error_log_entry', $entry );

		$this->append_entry( $entry );
	}

	/**
	 * @param array<string, mixed> $args Keys: limit, offset, level, date_from (Y-m-d), date_to (Y-m-d), source (substring), search (substring).
	 * @return array<int, array<string, mixed>>
	 */
	public function query( array $args = array() ) {
		$r = $this->query_with_meta( $args );
		return $r['entries'];
	}

	/**
	 * Filtered list with total count for pagination.
	 *
	 * @param array<string, mixed> $args Same as {@see query()} plus limit/offset.
	 * @return array{ entries: array<int, array<string, mixed>>, total: int }
	 */
	public function query_with_meta( array $args = array() ) {
		$limit  = isset( $args['limit'] ) ? max( 1, min( 500, (int) $args['limit'] ) ) : 100;
		$offset = isset( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;

		$filtered = $this->filter_entries( $this->list_entries_newest_first(), $args );
		$total    = count( $filtered );
		$page     = array_slice( $filtered, $offset, $limit );

		return array(
			'entries' => $page,
			'total'   => $total,
		);
	}

	/**
	 * All matching entries for export (capped).
	 *
	 * @param array<string, mixed> $args Filter args; optional export_limit (max 5000).
	 * @return array<int, array<string, mixed>>
	 */
	public function query_for_export( array $args = array() ) {
		$max = isset( $args['export_limit'] ) ? max( 1, min( 5000, (int) $args['export_limit'] ) ) : 5000;
		unset( $args['offset'], $args['limit'] );
		$args['limit'] = $max;

		$filtered = $this->filter_entries( $this->list_entries_newest_first(), $args );
		return array_slice( $filtered, 0, $max );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function list_entries_newest_first() {
		$log = $this->load_raw_entries();
		return array_reverse( array_values( $log ) );
	}

	/**
	 * @param array<int, array<string, mixed>> $entries
	 * @param array<string, mixed>             $args
	 * @return array<int, array<string, mixed>>
	 */
	private function filter_entries( array $entries, array $args ) {
		$out = array();
		foreach ( $entries as $e ) {
			if ( ! is_array( $e ) ) {
				continue;
			}
			if ( ! $this->entry_matches_filters( $e, $args ) ) {
				continue;
			}
			$out[] = $e;
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $e
	 * @param array<string, mixed> $args
	 */
	private function entry_matches_filters( array $e, array $args ) {
		$level = isset( $args['level'] ) ? sanitize_key( (string) $args['level'] ) : '';
		if ( '' !== $level ) {
			if ( $this->infer_level( $e ) !== $level ) {
				return false;
			}
		}

		$ts = isset( $e['ts'] ) ? (int) $e['ts'] : ( isset( $e['t'] ) ? (int) $e['t'] : 0 );

		if ( ! empty( $args['date_from'] ) ) {
			$from = self::parse_ymd_boundary( (string) $args['date_from'], true );
			if ( null !== $from && $ts < $from ) {
				return false;
			}
		}
		if ( ! empty( $args['date_to'] ) ) {
			$to = self::parse_ymd_boundary( (string) $args['date_to'], false );
			if ( null !== $to && $ts > $to ) {
				return false;
			}
		}

		if ( ! empty( $args['source'] ) ) {
			$needle = $this->strtolower_utf8( trim( (string) $args['source'] ) );
			if ( '' !== $needle ) {
				$src = isset( $e['source'] ) ? $this->strtolower_utf8( (string) $e['source'] ) : '';
				$end = isset( $e['endpoint'] ) ? $this->strtolower_utf8( (string) $e['endpoint'] ) : '';
				if ( false === strpos( $src, $needle ) && false === strpos( $end, $needle ) ) {
					return false;
				}
			}
		}

		if ( ! empty( $args['search'] ) ) {
			$needle = $this->strtolower_utf8( trim( (string) $args['search'] ) );
			if ( '' !== $needle && false === strpos( $this->entry_search_blob( $e ), $needle ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $ymd Y-m-d.
	 * @param bool   $start_of_day True = 00:00:00, false = 23:59:59 (site timezone).
	 * @return int|null
	 */
	private static function parse_ymd_boundary( $ymd, $start_of_day ) {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
			return null;
		}
		try {
			$tz = wp_timezone();
			$d  = \DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, $tz );
			if ( ! $d ) {
				return null;
			}
			if ( $start_of_day ) {
				return $d->setTime( 0, 0, 0 )->getTimestamp();
			}
			return $d->setTime( 23, 59, 59 )->getTimestamp();
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * @param array<string, mixed> $e
	 */
	private function entry_search_blob( array $e ) {
		$parts = array();
		foreach ( array( 'message', 'code', 'source', 'endpoint' ) as $k ) {
			if ( isset( $e[ $k ] ) ) {
				$parts[] = (string) $e[ $k ];
			}
		}
		if ( isset( $e['payload'] ) && is_array( $e['payload'] ) ) {
			$enc = wp_json_encode( $e['payload'] );
			if ( is_string( $enc ) ) {
				$parts[] = $enc;
			}
		}
		if ( isset( $e['context'] ) && is_array( $e['context'] ) ) {
			$enc = wp_json_encode( $e['context'] );
			if ( is_string( $enc ) ) {
				$parts[] = $enc;
			}
		}
		return $this->strtolower_utf8( implode( ' ', $parts ) );
	}

	/**
	 * @param string $s
	 * @return string
	 */
	private function strtolower_utf8( $s ) {
		if ( function_exists( 'mb_strtolower' ) ) {
			return mb_strtolower( (string) $s, 'UTF-8' );
		}
		return strtolower( (string) $s );
	}

	public function purge() {
		update_option( Constants::OPTION_ERROR_LOG, array(), false );
	}

	/**
	 * @param array<string, mixed> $entry
	 */
	private function append_entry( array $entry ) {
		$log = $this->load_raw_entries();
		$log[] = $entry;
		$log = $this->prune( $log );
		update_option( Constants::OPTION_ERROR_LOG, array_values( $log ), false );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function load_raw_entries() {
		$log = get_option( Constants::OPTION_ERROR_LOG, array() );
		return is_array( $log ) ? $log : array();
	}

	/**
	 * @param array<int, array<string, mixed>> $log
	 * @return array<int, array<string, mixed>>
	 */
	private function prune( array $log ) {
		$days       = max( 1, (int) OptionResolver::get_setting( 'diagnostics.log_retention_days', 14 ) );
		$max_entries = max( 10, min( 2000, (int) OptionResolver::get_setting( 'diagnostics.log_max_entries', 300 ) ) );
		$max_bytes   = max( 4096, min( 1048576, (int) OptionResolver::get_setting( 'diagnostics.log_max_bytes', 262144 ) ) );

		$cutoff = time() - $days * DAY_IN_SECONDS;
		$log    = array_values(
			array_filter(
				$log,
				function ( $e ) use ( $cutoff ) {
					if ( ! is_array( $e ) ) {
						return false;
					}
					$ts = isset( $e['ts'] ) ? (int) $e['ts'] : ( isset( $e['t'] ) ? (int) $e['t'] : 0 );
					return $ts >= $cutoff;
				}
			)
		);

		if ( count( $log ) > $max_entries ) {
			$log = array_slice( $log, -$max_entries );
		}

		while ( strlen( maybe_serialize( $log ) ) > $max_bytes && count( $log ) > 1 ) { // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- size estimate only
			array_shift( $log );
			$log = array_values( $log );
		}

		return $log;
	}

	/**
	 * @param mixed $data
	 * @param int   $depth
	 * @return mixed
	 */
	private function sanitize_tree( $data, $depth ) {
		if ( $depth <= 0 ) {
			return '[max depth]';
		}
		if ( is_array( $data ) ) {
			$out = array();
			foreach ( $data as $k => $v ) {
				$key = is_string( $k ) ? $k : (string) $k;
				if ( $this->is_sensitive_key( $key ) ) {
					$out[ $key ] = '[redacted]';
					continue;
				}
				if ( is_array( $v ) ) {
					$out[ $key ] = $this->sanitize_tree( $v, $depth - 1 );
				} elseif ( is_scalar( $v ) || null === $v ) {
					$out[ $key ] = $this->sanitize_scalar( $v );
				} else {
					$out[ $key ] = '[omitted]';
				}
			}
			return $out;
		}
		return $this->sanitize_scalar( $data );
	}

	/**
	 * @param string $key
	 */
	private function is_sensitive_key( $key ) {
		$l = strtolower( (string) $key );
		foreach ( self::$sensitive_key_fragments as $frag ) {
			if ( false !== strpos( $l, $frag ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param mixed $v
	 * @return mixed
	 */
	private function sanitize_scalar( $v ) {
		if ( null === $v ) {
			return null;
		}
		if ( is_bool( $v ) || is_int( $v ) ) {
			return $v;
		}
		if ( is_float( $v ) ) {
			return round( $v, 4 );
		}
		$s = wp_strip_all_tags( (string) $v );
		if ( strlen( $s ) > 500 ) {
			return substr( $s, 0, 500 ) . '…';
		}
		return $s;
	}

	/**
	 * @param array<string, mixed> $e
	 */
	public function infer_level( array $e ) {
		if ( isset( $e['level'] ) && is_string( $e['level'] ) ) {
			return sanitize_key( $e['level'] );
		}
		$type = isset( $e['type'] ) ? (string) $e['type'] : '';
		if ( 0 === strpos( $type, 'client_' ) ) {
			return self::LEVEL_INFO;
		}
		return self::LEVEL_ERROR;
	}

	/**
	 * @param array<string, mixed> $e
	 */
	public function format_entry_label( array $e ) {
		if ( isset( $e['source'] ) ) {
			return (string) $e['source'];
		}
		if ( isset( $e['type'] ) ) {
			return (string) $e['type'];
		}
		return 'event';
	}

	private function __construct() {
	}

	/**
	 * Not cloneable.
	 */
	private function __clone() {
	}
}
