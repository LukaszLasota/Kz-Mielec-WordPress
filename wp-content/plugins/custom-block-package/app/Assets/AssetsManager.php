<?php
/**
 * Assets Manager for Custom Block Package
 *
 * Manages external libraries and assets registration
 *
 * @package CustomBlockPackage
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CustomBlockPackage\Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class AssetsManager
 *
 * Handles registration of external JavaScript and CSS libraries
 * used by custom Gutenberg blocks
 */
class AssetsManager {

	/**
	 * Leaflet library version
	 *
	 * @var string
	 */
	private const LEAFLET_VERSION = '1.9.4';

	/**
	 * Glide.js library version
	 *
	 * @var string
	 */
	private const GLIDE_VERSION = '3.5.2';

	/**
	 * Constructor
	 *
	 * Hooks into WordPress init action to register all assets
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_all_assets' ) );
	}

	/**
	 * Register all plugin assets
	 *
	 * Main method that registers all external libraries
	 * used by the custom blocks
	 *
	 * @return void
	 */
	public function register_all_assets(): void {
		$this->register_leaflet_assets();
		$this->register_glide_assets();
	}

	/**
	 * Register Leaflet map library assets
	 *
	 * Registers Leaflet CSS from CDN for interactive maps
	 * Used by: map-block
	 *
	 * @return void
	 */
	private function register_leaflet_assets(): void {
		$leaflet_css_path = UP_PLUGIN_DIR . 'build/leaflet/leaflet.css';

		if ( file_exists( $leaflet_css_path ) ) {
			wp_register_style(
				'my-block-leaflet-style',
				UP_PLUGIN_URL . 'build/leaflet/leaflet.css',
				array(),
				self::LEAFLET_VERSION
			);
		}
	}

	/**
	 * Register Glide.js carousel library assets
	 *
	 * Registers Glide.js files from local build directory
	 * Includes: core CSS, minified JS, and initialization script
	 * Used by: slider blocks
	 *
	 * @return void
	 */
	private function register_glide_assets(): void {
		$glide_css_path  = UP_PLUGIN_DIR . 'build/glide-package/glide.core.css';
		$glide_js_path   = UP_PLUGIN_DIR . 'build/glide-package/glide.min.js';
		$glide_init_path = UP_PLUGIN_DIR . 'build/glide-package/index.js';

		// Register Glide core CSS.
		if ( file_exists( $glide_css_path ) ) {
			wp_register_style(
				'glide-core-style',
				UP_PLUGIN_URL . 'build/glide-package/glide.core.css',
				array(),
				self::GLIDE_VERSION
			);
		}

		// Register Glide core JavaScript.
		if ( file_exists( $glide_js_path ) ) {
			wp_register_script(
				'glide-core-script',
				UP_PLUGIN_URL . 'build/glide-package/glide.min.js',
				array(),
				self::GLIDE_VERSION,
				true
			);
		}

		// Register Glide initialization script.
		if ( file_exists( $glide_init_path ) ) {
			wp_register_script(
				'glide-init-script',
				UP_PLUGIN_URL . 'build/glide-package/index.js',
				array( 'glide-core-script' ),
				'1.0.0',
				true
			);
		}
	}
}
