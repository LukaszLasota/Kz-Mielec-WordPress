<?php
/**
 * Pattern Assets Management
 *
 * Automatically detects and enqueues pattern-specific CSS/JS files
 * only when patterns are used on the page.
 *
 * @package Kzmielec
 */

namespace Kzmielec\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class PatternAssets
 *
 * Detects patterns in post content and enqueues their compiled assets.
 */
class PatternAssets implements ActionHookInterface {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
	}

	/**
	 * Register WordPress action hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_pattern_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_pattern_assets' ) );
	}

	/**
	 * Enqueue pattern assets based on post content.
	 *
	 * @return void
	 */
	public function enqueue_pattern_assets(): void {
		global $post;

		if ( ! $post || empty( $post->post_content ) ) {
			return;
		}

		$patterns = $this->detect_patterns_in_content( $post->post_content );

		// Debug: log detected patterns.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug-only logging.
			error_log( 'PatternAssets detected patterns: ' . wp_json_encode( $patterns ) );
		}

		foreach ( $patterns as $pattern_slug ) {
			$this->enqueue_pattern( $pattern_slug );
		}
	}

	/**
	 * Detect patterns used in content.
	 *
	 * Uses hybrid approach:
	 * 1. WordPress parse_blocks() for Gutenberg blocks (primary)
	 * 2. HTML regex parsing as fallback for edge cases
	 *
	 * @param string $content Post content to scan.
	 * @return array<string> Array of pattern slugs.
	 */
	private function detect_patterns_in_content( string $content ): array {
		$patterns = array();

		// Method 1: WordPress Block Parser (primary method).
		if ( function_exists( 'parse_blocks' ) ) {
			$blocks = parse_blocks( $content );
			$this->find_pattern_classes_recursive( $blocks, $patterns );
		}

		// Method 2: HTML regex parsing (fallback for edge cases).
		if ( empty( $patterns ) ) {
			preg_match_all(
				'/class="[^"]*pattern-([a-z0-9-]+)[^"]*"/',
				$content,
				$matches
			);

			if ( ! empty( $matches[1] ) ) {
				$patterns = $matches[1];
			}
		}

		return array_unique( $patterns );
	}

	/**
	 * Recursively find pattern class names in blocks.
	 *
	 * @param array<mixed>  $blocks  Array of parsed blocks.
	 * @param array<string> &$patterns Array to store found pattern slugs (passed by reference).
	 * @return void
	 */
	private function find_pattern_classes_recursive( array $blocks, array &$patterns ): void {
		foreach ( $blocks as $block ) {
			// Check className attribute.
			if ( isset( $block['attrs']['className'] ) ) {
				$class_name = $block['attrs']['className'];

				if ( false !== strpos( $class_name, 'pattern-' ) ) {
					preg_match_all( '/pattern-([a-z0-9-]+)/', $class_name, $matches );

					if ( ! empty( $matches[1] ) ) {
						$patterns = array_merge( $patterns, $matches[1] );
					}
				}
			}

			// Recursively check inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$this->find_pattern_classes_recursive( $block['innerBlocks'], $patterns );
			}
		}
	}

	/**
	 * Enqueue CSS and JS for a specific pattern.
	 *
	 * @param string $slug Pattern slug (e.g., 'three-columns-of-services').
	 * @return void
	 */
	private function enqueue_pattern( string $slug ): void {
		$theme_dir    = get_template_directory();
		$theme_uri    = get_template_directory_uri();
		$asset_suffix = $this->get_asset_suffix();

		$css_file = "{$theme_dir}/assets/css/patterns/{$slug}-style{$asset_suffix}.css";
		$js_file  = "{$theme_dir}/assets/js/patterns/{$slug}-script{$asset_suffix}.js";

		// Enqueue CSS if exists.
		if ( file_exists( $css_file ) ) {
			$css_version = filemtime( $css_file );
			wp_enqueue_style(
				"pattern-{$slug}",
				"{$theme_uri}/assets/css/patterns/{$slug}-style{$asset_suffix}.css",
				array(),
				false !== $css_version ? (string) $css_version : null
			);
		}

		// Enqueue JS if exists.
		if ( file_exists( $js_file ) ) {
			$js_version = filemtime( $js_file );
			wp_enqueue_script(
				"pattern-{$slug}",
				"{$theme_uri}/assets/js/patterns/{$slug}-script{$asset_suffix}.js",
				array(),
				false !== $js_version ? (string) $js_version : null,
				true
			);
		}
	}

	/**
	 * Enqueue all pattern styles in block editor.
	 *
	 * Loads all available pattern CSS files in the Gutenberg editor
	 * so patterns display correctly in the editor preview.
	 *
	 * @return void
	 */
	public function enqueue_editor_pattern_assets(): void {
		$theme_dir    = get_template_directory();
		$theme_uri    = get_template_directory_uri();
		$asset_suffix = $this->get_asset_suffix();

		$pattern_dirs = glob( "{$theme_dir}/webpack/src/patterns/*", GLOB_ONLYDIR );

		if ( false === $pattern_dirs || empty( $pattern_dirs ) ) {
			return;
		}

		foreach ( $pattern_dirs as $pattern_dir ) {
			$slug     = basename( $pattern_dir );
			$css_file = "{$theme_dir}/assets/css/patterns/{$slug}-style{$asset_suffix}.css";

			if ( file_exists( $css_file ) ) {
				$css_version = filemtime( $css_file );
				wp_enqueue_style(
					"pattern-editor-{$slug}",
					"{$theme_uri}/assets/css/patterns/{$slug}-style{$asset_suffix}.css",
					array(),
					false !== $css_version ? (string) $css_version : null
				);
			}
		}
	}

	/**
	 * Get asset file suffix based on environment.
	 *
	 * @return string '.min' for production, empty string for development.
	 */
	private function get_asset_suffix(): string {
		$environment = function_exists( 'wp_get_environment_type' )
			? wp_get_environment_type()
			: getenv( 'ENV_TYPE' );

		return ( 'production' === $environment ) ? '.min' : '';
	}
}
