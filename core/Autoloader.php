<?php
/**
 * PSR-4 class autoloader for plugin modules.
 *
 * @package MpStickyCustomCart
 */

namespace MpStickyCustomCart\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Maps namespace prefixes to directory names under the plugin root.
 */
final class Autoloader {

	/**
	 * Absolute path to the plugin root (trailing slash).
	 *
	 * @var string
	 */
	private static $base_path = '';

	/**
	 * Prefix => subdirectory (under plugin root).
	 *
	 * Longer prefixes must be registered first if they share a common stem.
	 *
	 * @var array<string, string>
	 */
	private static $prefixes = array(
		'MpStickyCustomCart\\Integrations\\' => 'integrations',
		'MpStickyCustomCart\\Frontend\\'     => 'frontend',
		'MpStickyCustomCart\\Admin\\'        => 'admin',
		'MpStickyCustomCart\\Core\\'         => 'core',
	);

	/**
	 * Register the autoloader.
	 *
	 * @param string $plugin_root Absolute path to the directory containing `core`, `admin`, etc.
	 */
	public static function register( $plugin_root ) {
		self::$base_path = rtrim( (string) $plugin_root, "/\\" ) . DIRECTORY_SEPARATOR;
		spl_autoload_register( array( self::class, 'autoload' ), true, true );
	}

	/**
	 * Load a class file if it matches a known PSR-4 prefix.
	 *
	 * @param string $class Fully qualified class name.
	 */
	public static function autoload( $class ) {
		foreach ( self::$prefixes as $prefix => $dir ) {
			if ( 0 !== strpos( (string) $class, $prefix ) ) {
				continue;
			}

			$relative = substr( (string) $class, strlen( $prefix ) );
			if ( '' === $relative ) {
				return;
			}

			$relative_path = str_replace( '\\', DIRECTORY_SEPARATOR, $relative ) . '.php';
			$file          = self::$base_path . $dir . DIRECTORY_SEPARATOR . $relative_path;

			if ( is_readable( $file ) ) {
				require $file;
			}

			return;
		}
	}
}
