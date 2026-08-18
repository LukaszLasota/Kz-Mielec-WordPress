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
		add_action( 'init', array( $this, 'clean_head_links' ) );
		add_filter( 'eio_lazy_exclusions', array( $this, 'skip_lazy_for_priority_images' ) );
		add_action( 'wp_default_scripts', array( $this, 'remove_jquery_migrate' ) );
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

	/**
	 * Drop jQuery Migrate from jQuery's dependencies on the front end.
	 *
	 * The library itself has to stay: the Instagram feed plugin registers its
	 * script with `jquery` as a dependency, so dequeuing it would take the feed
	 * down.
	 * Migrate is a different thing - a shim that restores APIs removed in
	 * jQuery 3.0 for code written against 1.x. Nothing here needs it, and it is
	 * a separate 13 KB file on every page.
	 *
	 * Left in place in admin, where third-party panels are outside our control.
	 *
	 * @param \WP_Scripts $scripts The scripts registry, passed by reference by core.
	 * @return void
	 */
	public function remove_jquery_migrate( \WP_Scripts $scripts ): void {
		if ( is_admin() ) {
			return;
		}

		if ( ! isset( $scripts->registered['jquery'] ) ) {
			return;
		}

		$scripts->registered['jquery']->deps = array_values(
			array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) )
		);
	}

	/**
	 * Remove three unused tags that core prints into every `<head>`.
	 *
	 * `rsd_link` advertises the XML-RPC endpoint to remote editing clients.
	 * There are none here, and the endpoint answers 403 anyway, so the tag
	 * points at nothing.
	 *
	 * `wp_shortlink_wp_head` publishes a `?p=123` alias of the current URL.
	 * Nothing consumes it, and on a multilingual site it is one more address
	 * for the same page. The `template_redirect` twin sends the same value as
	 * an HTTP `Link:` header and goes with it.
	 *
	 * `wp_generator` prints the WordPress version. This is housekeeping, not a
	 * security measure - a version is derivable from core file fingerprints
	 * whatever the markup says. The `the_generator` filter covers the copy that
	 * goes into the RSS feeds, which stay enabled.
	 *
	 * @return void
	 */
	public function clean_head_links(): void {
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
	}
}
