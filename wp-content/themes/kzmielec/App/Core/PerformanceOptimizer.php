<?php
/**
 * PerformanceOptimizer class
 *
 * Handles third-party plugin asset optimization.
 *
 * @package Church\Core
 */

namespace Church\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Church\Interfaces\ActionHookInterface;

/**
 * Class PerformanceOptimizer
 *
 * Moves render-blocking third-party scripts to footer, disables unnecessary
 * WordPress features, and defers non-critical assets.
 */
class PerformanceOptimizer implements ActionHookInterface {

	/**
	 * Forminator script handles to move to footer.
	 *
	 * @var array<string>
	 */
	private const FORMINATOR_SCRIPTS = array(
		'forminator-front-scripts',
		'forminator-ui',
		'forminator-jquery-validate',
		'forminator-chartjs',
		'chartjs-plugin-datalabels',
		'select2-forminator',
		'forminator-intlTelInput',
		'forminator-dompurify',
	);

	/**
	 * Forminator CSS handle prefixes to defer.
	 *
	 * These stylesheets are below-the-fold (form section) and safe to
	 * load asynchronously using the media="print" swap technique.
	 *
	 * @var array<string>
	 */
	private const FORMINATOR_STYLES = array(
		'forminator-forms-',
		'forminator-grid-',
		'forminator-icons',
		'forminator-utilities',
		'buttons',
	);

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
		add_action( 'wp_enqueue_scripts', array( $this, 'optimize_forminator_assets' ), 999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'defer_recaptcha' ), 999 );
		add_action( 'init', array( $this, 'disable_emoji' ) );
		add_filter( 'eio_lazy_exclusions', array( $this, 'skip_lazy_for_priority_images' ) );
		add_filter( 'style_loader_tag', array( $this, 'defer_forminator_styles' ), 10, 2 );
	}

	/**
	 * Move Forminator scripts to footer to prevent render-blocking.
	 *
	 * @return void
	 */
	public function optimize_forminator_assets(): void {
		if ( is_admin() ) {
			return;
		}

		foreach ( self::FORMINATOR_SCRIPTS as $handle ) {
			wp_script_add_data( $handle, 'group', 1 );
		}
	}

	/**
	 * Defer reCAPTCHA loading until user scrolls near the form.
	 *
	 * Forminator loads Google reCAPTCHA (~800KB) immediately on pages with forms.
	 * This dequeues the script and adds an IntersectionObserver that loads it
	 * only when the form section becomes visible in the viewport.
	 *
	 * @return void
	 */
	public function defer_recaptcha(): void {
		if ( is_admin() ) {
			return;
		}

		// Check if Forminator enqueued the reCAPTCHA script.
		if ( ! wp_script_is( 'forminator-google-recaptcha', 'enqueued' ) ) {
			return;
		}

		// Get the original script source before dequeuing.
		$scripts = wp_scripts();
		$src     = '';
		if ( isset( $scripts->registered['forminator-google-recaptcha'] ) ) {
			$src = $scripts->registered['forminator-google-recaptcha']->src;
		}

		if ( empty( $src ) ) {
			return;
		}

		// Remove the immediate reCAPTCHA load.
		wp_dequeue_script( 'forminator-google-recaptcha' );

		// Add inline script that lazy-loads reCAPTCHA via IntersectionObserver.
		$inline_js = sprintf(
			'(function(){' .
				'var loaded=false;' .
				'function loadRecaptcha(){' .
					'if(loaded)return;' .
					'loaded=true;' .
					'var s=document.createElement("script");' .
					's.src=%s;' .
					'document.body.appendChild(s);' .
				'}' .
				'var form=document.querySelector(".forminator-custom-form");' .
				'if(!form)return;' .
				'if("IntersectionObserver" in window){' .
					'var o=new IntersectionObserver(function(e){' .
						'e.forEach(function(i){' .
							'if(i.isIntersecting){loadRecaptcha();o.disconnect();}' .
						'});' .
					'},{rootMargin:"600px"});' .
					'o.observe(form);' .
				'}else{loadRecaptcha();}' .
			'})();',
			wp_json_encode( $src )
		);

		wp_add_inline_script( 'forminator-front-scripts', $inline_js );
	}

	/**
	 * Defer Forminator CSS to prevent render-blocking.
	 *
	 * Forminator forms are below the fold, so their CSS doesn't need to block
	 * initial rendering. Uses the media="print" swap technique: the browser
	 * downloads the stylesheet without blocking, then switches to media="all"
	 * once loaded.
	 *
	 * @param string $html   The link tag HTML.
	 * @param string $handle The stylesheet handle.
	 * @return string
	 */
	public function defer_forminator_styles( string $html, string $handle ): string {
		foreach ( self::FORMINATOR_STYLES as $prefix ) {
			if ( str_starts_with( $handle, $prefix ) ) {
				$html = str_replace(
					"media='all'",
					"media='print' onload=\"this.media='all'\"",
					$html
				);
				break;
			}
		}

		return $html;
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
