<?php
/**
 * RegisterAssets class
 *
 * Handles registration and enqueueing of theme assets (CSS and JavaScript).
 *
 * @package Kzmielec\BasicTheme
 */

namespace Kzmielec\BasicTheme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class RegisterAssets
 *
 * Manages theme asset registration with automatic versioning and environment-based file loading.
 */
class RegisterAssets implements ActionHookInterface {

	/**
	 * File suffix for production assets (.min for production, empty for development).
	 *
	 * @var string
	 */
	private string $suffix;

	/**
	 * Theme directory URI.
	 *
	 * @var string
	 */
	private string $theme_uri;

	/**
	 * Theme directory path.
	 *
	 * @var string
	 */
	private string $theme_path;

	/**
	 * Constructor.
	 *
	 * Initializes theme paths and registers WordPress hooks.
	 */
	public function __construct() {
		$this->theme_uri  = get_stylesheet_directory_uri();
		$this->theme_path = get_stylesheet_directory();
		$this->suffix     = $this->get_asset_suffix();

		$this->register_add_action();
	}

	/**
	 * Register WordPress action hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_kzmielec_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_kzmielec_admin_assets' ) );
	}

	/**
	 * Get asset suffix based on environment.
	 *
	 * Returns '.min' for production environment, empty string for development.
	 * Supports WordPress native wp_get_environment_type() and fallback to getenv().
	 *
	 * @return string Asset suffix (.min or empty).
	 */
	private function get_asset_suffix(): string {
		$env = 'development';

		// Try WordPress native function (WP 5.5+).
		if ( function_exists( 'wp_get_environment_type' ) ) {
			$env = wp_get_environment_type();
		} elseif ( false !== getenv( 'ENV_TYPE' ) ) {
			// Fallback for Docker/deployment environments.
			$env = getenv( 'ENV_TYPE' );
		}

		return ( 'production' === $env ) ? '.min' : '';
	}

	/**
	 * Get file version for cache busting.
	 *
	 * Returns file modification time if file exists, false otherwise.
	 *
	 * @param string $file_path Absolute path to file.
	 * @return string|false File modification timestamp as string or false.
	 */
	private function get_file_version( string $file_path ): string|false {
		if ( file_exists( $file_path ) ) {
			return (string) filemtime( $file_path );
		}

		// Log missing file for debugging.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional logging for missing asset files.
		error_log( sprintf( 'Asset file not found: %s', $file_path ) );
		return false;
	}

	/**
	 * Enqueue script or style with automatic versioning.
	 *
	 * @param string             $type      Asset type: 'script' or 'style'.
	 * @param string             $handle    WordPress asset handle.
	 * @param string             $path      Relative path from theme directory.
	 * @param array<int, string> $deps      Array of dependencies (default: empty).
	 * @param bool               $in_footer Whether to enqueue script in footer (default: true).
	 * @param string             $media     Media type for styles (default: 'all').
	 * @return void
	 */
	private function enqueue_asset(
		string $type,
		string $handle,
		string $path,
		array $deps = array(),
		bool $in_footer = true,
		string $media = 'all'
	): void {
		$url       = $this->theme_uri . $path;
		$file_path = $this->theme_path . $path;
		$version   = $this->get_file_version( $file_path );

		// Skip if file doesn't exist.
		if ( false === $version ) {
			return;
		}

		if ( 'script' === $type ) {
			wp_enqueue_script( $handle, $url, $deps, $version, $in_footer );
		} else {
			wp_enqueue_style( $handle, $url, $deps, $version, $media );
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * Loads backend CSS and JavaScript for WordPress admin panel.
	 *
	 * @return void
	 */
	public function register_kzmielec_admin_assets(): void {
		$this->enqueue_asset( 'style', 'kzmielec-admin-style', "/assets/css/backend{$this->suffix}.css" );
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * Loads frontend CSS, JavaScript and print styles for public-facing pages.
	 *
	 * @return void
	 */
	public function register_kzmielec_assets(): void {
		// Frontend JavaScript.
		$this->enqueue_asset( 'script', 'kzmielec-script', "/assets/js/frontend{$this->suffix}.js" );

		// Frontend styles.
		$this->enqueue_asset( 'style', 'kzmielec-styles', "/assets/css/frontend{$this->suffix}.css" );

		// Print styles.
		$this->enqueue_asset( 'style', 'kzmielec-print-styles', "/assets/css/print{$this->suffix}.css", array(), true, 'print' );

		// Localize script for AJAX.
		wp_localize_script(
			'kzmielec-script',
			'redlist',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

}
