<?php
/**
 * Block Registration Manager for Custom Block Package
 *
 * Handles automatic registration of custom Gutenberg blocks
 *
 * @package CustomBlockPackage
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CustomBlockPackage\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class RegisterBlocks
 *
 * Automatically scans and registers all custom Gutenberg blocks
 * from the build directory
 */
class RegisterBlocks {

	/**
	 * Constructor
	 *
	 * Hooks into WordPress init and block categories filter
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ) );
	}

	/**
	 * Register custom block category
	 *
	 * Adds a custom category for blocks at the beginning of the categories list
	 *
	 * @param array $categories Existing block categories.
	 * @return array Modified block categories
	 */
	public function register_block_category( array $categories ): array {
		// Add custom blocks category at the beginning.
		array_unshift(
			$categories,
			array(
				'slug'  => 'custom-blocks-from-scratch',
				'title' => __( 'Custom blocks for sites', 'custom-block-package' ),
				'icon'  => 'heart',
			)
		);

		return $categories;
	}

	/**
	 * Register all custom blocks
	 *
	 * Automatically scans the build/blocks directory and registers
	 * all blocks that have a valid block.json file
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		// Automatically scan block folders.
		$blocks_dir    = UP_PLUGIN_DIR . 'build/blocks/';
		$block_folders = glob( $blocks_dir . '*', GLOB_ONLYDIR );

		if ( ! is_array( $block_folders ) ) {
			return;
		}

		foreach ( $block_folders as $block_folder ) {
			$block_json_path = $block_folder . '/block.json';

			// Check if block.json file exists.
			if ( ! file_exists( $block_json_path ) ) {
				continue;
			}

			// Read block.json content.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read.
			$file_contents = file_get_contents( $block_json_path );
			if ( false === $file_contents ) {
				continue;
			}

			$block_metadata = json_decode( $file_contents, true );
			$block_name     = $block_metadata['name'] ?? null;

			if ( ! $block_metadata || ! isset( $block_name ) ) {
				continue;
			}

			// Check if block is already registered.
			if ( \WP_Block_Type_Registry::get_instance()->is_registered( $block_name ) ) {
				continue;
			}

			// Register block.
			register_block_type_from_metadata( $block_json_path );
		}
	}
}
