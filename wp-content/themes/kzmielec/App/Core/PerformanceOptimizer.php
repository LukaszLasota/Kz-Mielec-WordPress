<?php
/**
 * PerformanceOptimizer class
 *
 * Handles third-party plugin asset optimization.
 *
 * @package Kzmielec\Core
 */

namespace Kzmielec\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class PerformanceOptimizer
 *
 * Moves render-blocking third-party scripts to footer, disables unnecessary
 * WordPress features, and defers non-critical assets.
 */
class PerformanceOptimizer implements ActionHookInterface {

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
		add_action( 'init', array( $this, 'disable_emoji' ) );
		add_filter( 'eio_lazy_exclusions', array( $this, 'skip_lazy_for_priority_images' ) );
	}

	/**
	 * Add fetchpriority to EWWW lazy loading exclusions.
	 *
	 * Above-fold images with fetchpriority="high" should never be lazy-loaded
	 * as it replaces them with a placeholder, causing CLS.
	 *
	 * @param array<string> $exclusions List of strings that skip lazy loading.
	 * @return array<string>
	 */
	public function skip_lazy_for_priority_images( array $exclusions ): array {
		$exclusions[] = 'fetchpriority';

		return $exclusions;
	}

	/**
	 * Disable WordPress emoji scripts and styles.
	 *
	 * WordPress loads emoji detection JS and CSS on every page by default.
	 * Modern browsers support emoji natively, making this unnecessary.
	 *
	 * @return void
	 */
	public function disable_emoji(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	}
}
