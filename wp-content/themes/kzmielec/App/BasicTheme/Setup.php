<?php

namespace Kzmielec\BasicTheme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class Setup
 *
 * Handles WordPress theme setup and feature registration.
 */
class Setup implements ActionHookInterface {

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
		add_action( 'after_setup_theme', array( $this, 'kzmielec_setup' ) );
	}

	/**
	 * Setup theme features and supports.
	 *
	 * @return void
	 */
	public function kzmielec_setup(): void {
		add_theme_support( 'menus' );

		add_theme_support( 'post-thumbnails' );

		add_theme_support( 'title-tag' );

		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
		);

		add_theme_support( 'post-formats', array( 'image', 'video', 'quote', 'gallery', 'aside' ) );

		add_theme_support( 'responsive-embeds' );

		add_theme_support( 'custom-background' );

		add_theme_support( 'automatic-feed-links' );

		add_theme_support( 'align-wide' );

		add_theme_support( 'block-templates' );

		add_theme_support( 'block-template-parts' );

		add_theme_support( 'footer-widgets', 3 );

		add_theme_support( 'customize-selective-refresh-widgets' );

		add_theme_support( 'editor-styles' );

		add_editor_style( 'assets/css/editor.css' );

		add_theme_support( 'wp-block-styles' );

		add_image_size( 'blog-card', 600, 400, false );
	}
}
