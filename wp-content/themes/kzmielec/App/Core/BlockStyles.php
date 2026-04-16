<?php
/**
 * Block Style Variations
 *
 * Registers theme-specific block style variations and enqueues their CSS.
 *
 * @package Kzmielec
 */

namespace Kzmielec\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class BlockStyles
 *
 * Registers custom block style variations from the theme and loads
 * their compiled CSS only when the block is present on the page.
 */
class BlockStyles implements ActionHookInterface {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
	}

	/**
	 * Register WordPress action hooks.
	 *
	 * Uses priority 20 so plugin blocks are already registered at init:10.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'init', array( $this, 'register_block_styles' ), 20 );
	}

	/**
	 * Register block style variations and enqueue their CSS.
	 *
	 * @return void
	 */
	public function register_block_styles(): void {
		$this->register_dynamic_images_styles();
		$this->register_heading_styles();
	}

	/**
	 * Register style variations for the dynamic-images block.
	 *
	 * @return void
	 */
	private function register_dynamic_images_styles(): void {
		register_block_style(
			'custom-block-package/dynamic-images',
			array(
				'name'  => 'banner-hero',
				'label' => __( 'Banner Hero', 'kzmielec' ),
			)
		);

		$asset_suffix = $this->get_asset_suffix();
		$css_path     = '/assets/css/block-styles/dynamic-images-banner-hero' . $asset_suffix . '.css';
		$full_path    = get_template_directory() . $css_path;

		if ( file_exists( $full_path ) ) {
			wp_enqueue_block_style(
				'custom-block-package/dynamic-images',
				array(
					'handle' => 'dynamic-images-banner-hero',
					'src'    => get_template_directory_uri() . $css_path,
					'path'   => $full_path,
					'ver'    => (string) filemtime( $full_path ),
				)
			);
		}
	}

	/**
	 * Register style variations for the core/heading block.
	 *
	 * @return void
	 */
	private function register_heading_styles(): void {
		register_block_style(
			'core/heading',
			array(
				'name'  => 'section-line',
				'label' => __( 'Z linią', 'kzmielec' ),
			)
		);

		$asset_suffix = $this->get_asset_suffix();
		$css_path     = '/assets/css/block-styles/heading-section-line' . $asset_suffix . '.css';
		$full_path    = get_template_directory() . $css_path;

		if ( file_exists( $full_path ) ) {
			wp_enqueue_block_style(
				'core/heading',
				array(
					'handle' => 'heading-section-line',
					'src'    => get_template_directory_uri() . $css_path,
					'path'   => $full_path,
					'ver'    => (string) filemtime( $full_path ),
				)
			);
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
			: ( getenv( 'ENV_TYPE' ) ?: 'development' );

		return ( 'production' === $environment ) ? '.min' : '';
	}
}