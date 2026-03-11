<?php
/**
 * PSR-4 Autoloader for Custom Block Package
 *
 * @package CustomBlockPackage
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CustomBlockPackage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Autoloader
 *
 * Automatically loads classes based on PSR-4 standard
 */
class Autoloader {

	/**
	 * Constructor
	 *
	 * Registers the autoload function
	 */
	public function __construct() {
		spl_autoload_register( [ $this, 'autoload' ] );
	}

	/**
	 * Autoload classes
	 *
	 * Maps CustomBlockPackage namespace to app/ directory
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return void
	 */
	public function autoload( string $class_name ): void {

		// Check if class belongs to our namespace.
		if ( 0 !== strpos( $class_name, 'CustomBlockPackage\\' ) ) {
			return;
		}

		// Remove base namespace.
		$class_path = str_replace( 'CustomBlockPackage\\', '', $class_name );

		// Convert namespace separators to directory separators.
		$class_path = str_replace( '\\', DIRECTORY_SEPARATOR, $class_path );

		// Build full file path.
		$file_path = UP_PLUGIN_DIR . 'app' . DIRECTORY_SEPARATOR . $class_path . '.php';

		// Load class file if exists.
		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		} else {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				wp_die( esc_html( "Autoloader: file not found at {$file_path}" ) );
			}
			return;
		}
	}
}

new Autoloader();
