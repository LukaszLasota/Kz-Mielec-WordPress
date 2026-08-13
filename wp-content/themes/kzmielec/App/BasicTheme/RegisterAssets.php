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
 * Manages theme asset registration, cache-busting each handle with the built
 * file's filemtime. The build produces a single optimised file per entry, so
 * there is no development/production variant to choose between.
 */
class RegisterAssets implements ActionHookInterface {


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

		$this->register_add_action();
	}

	/**
	 * Register WordPress action hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_kzmielec_assets' ) );
		add_action( 'wp_head', array( $this, 'print_accessibility_preferences' ), 1 );
	}

	/**
	 * Print the inline script that restores the visitor's accessibility settings.
	 *
	 * This has to be inline and in the head, ahead of the stylesheet: the bundled
	 * script runs in the footer, so restoring the setting there would show the
	 * page at the default size in the default palette first and only then redraw
	 * it — a flash that is worst for exactly the people who need the setting.
	 *
	 * Nothing read from storage reaches the DOM verbatim. Each value is compared
	 * against a fixed allowlist and only the literals below are ever written, so
	 * a tampered `localStorage` entry cannot inject an attribute value. The whole
	 * body is wrapped in try/catch because reading `localStorage` throws outright
	 * when a browser blocks storage (Safari's private mode), and an exception
	 * here would stop the rest of the head from running.
	 *
	 * `data-a11y-js` is what reveals the bar: the controls do nothing without
	 * JavaScript, so a visitor without it should not be offered them.
	 *
	 * @return void
	 */
	public function print_accessibility_preferences(): void {
		$script = <<<'JS'
(function(){try{var r=document.documentElement,s=localStorage.getItem("kzmielec-a11y-size");r.setAttribute("data-a11y-js","");if("large"===s||"xlarge"===s){r.setAttribute("data-a11y-size",s);}if("on"===localStorage.getItem("kzmielec-a11y-contrast")){r.setAttribute("data-a11y-contrast","on");}}catch(e){}})();
JS;

		/*
		 * `data-no-optimize` is not decoration. Without it, LiteSpeed's "Minify
		 * JS" moves this very script out of the document into an external
		 * `data:text/javascript;base64,…` URL, and "Load JS Deferred" then
		 * stamps `defer` on it — so the one script whose whole job is to run
		 * before the first paint ran after it. Measured consequence: the strip
		 * appeared ~0.9 s in and pushed the page down 49px (CLS 0.03 on desktop,
		 * far worse on a phone), and a visitor returning with high contrast saw
		 * the default palette first. The plugin checks this attribute in
		 * `optimize.cls.php:889`, ahead of every transform, and uses it for its
		 * own inline scripts; the value has to be a non-empty string because the
		 * gate is `! empty( $attrs['data-no-optimize'] )`.
		 */
		wp_print_inline_script_tag( $script, array( 'data-no-optimize' => '1' ) );
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

		/*
		 * Only while debugging. This runs once per asset per request, so on a
		 * production server a single missing file wrote a line to the error log
		 * on every page view - noise that says the same thing a million times
		 * and buries whatever else lands there.
		 */
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug-only logging.
			error_log( sprintf( 'Asset file not found: %s', $file_path ) );
		}

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
	 * Enqueue frontend assets.
	 *
	 * Loads frontend CSS and JavaScript for public-facing pages.
	 *
	 * @return void
	 */
	public function register_kzmielec_assets(): void {
		// Frontend JavaScript.
		$this->enqueue_asset( 'script', 'kzmielec-script', '/assets/js/frontend.js' );

		// Frontend styles.
		$this->enqueue_asset( 'style', 'kzmielec-styles', '/assets/css/frontend.css' );

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
