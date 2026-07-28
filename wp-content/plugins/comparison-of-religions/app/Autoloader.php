<?php
/**
 * PSR-4 Autoloader for the Comparison of Religions plugin.
 *
 * Maps the ComparisonOfReligions namespace to the app/ directory:
 *   ComparisonOfReligions\PostTypes\ComparisonTopic → app/PostTypes/ComparisonTopic.php
 *   ComparisonOfReligions\Meta\ChurchesMeta         → app/Meta/ChurchesMeta.php
 *
 * This is a lightweight autoloader without Composer dependency.
 * Pattern reused from the custom-block-package plugin.
 *
 * @package ComparisonOfReligions
 */

declare(strict_types=1);

namespace ComparisonOfReligions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PSR-4 Autoloader — instantiated at the bottom of this file.
 */
class Autoloader {

	/**
	 * Constructor — registers spl_autoload_register callback.
	 */
	public function __construct() {
		spl_autoload_register( [ $this, 'autoload' ] );
	}

	/**
	 * Autoload callback.
	 *
	 * Strips the base namespace prefix and converts the remaining class path
	 * to a file path relative to the app/ directory.
	 *
	 * @param string $class_name Fully qualified class name (e.g. ComparisonOfReligions\Meta\ChurchesMeta).
	 */
	public function autoload( string $class_name ): void {
		if ( 0 !== strpos( $class_name, 'ComparisonOfReligions\\' ) ) {
			return;
		}

		$class_path = str_replace( 'ComparisonOfReligions\\', '', $class_name );
		$class_path = str_replace( '\\', DIRECTORY_SEPARATOR, $class_path );
		$file_path  = COR_PLUGIN_DIR . 'app' . DIRECTORY_SEPARATOR . $class_path . '.php';

		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}
}

new Autoloader();
