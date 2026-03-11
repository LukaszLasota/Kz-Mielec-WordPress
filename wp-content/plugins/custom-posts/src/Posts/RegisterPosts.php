<?php
/**
 * Register custom post types
 *
 * @package CustomPostsPlugin
 */

namespace CustomPostsPlugin\Posts;

use CustomPostsPlugin\Core\CptBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RegisterPosts
 *
 * Registers all custom post types used by the plugin.
 */
class RegisterPosts {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_meetings();
	}

	/**
	 * Register the Meetings (Spotkania) custom post type.
	 *
	 * @return void
	 */
	private function register_meetings(): void {
		$labels = static function (): array {
			return [
				'name'               => __( 'Spotkania', 'custom-posts' ),
				'singular_name'      => __( 'Spotkanie', 'custom-posts' ),
				'add_new'            => __( 'Dodaj Nowe', 'custom-posts' ),
				'add_new_item'       => __( 'Dodaj Nowe Spotkanie', 'custom-posts' ),
				'edit_item'          => __( 'Edytuj Spotkanie', 'custom-posts' ),
				'new_item'           => __( 'Nowe Spotkanie', 'custom-posts' ),
				'all_items'          => __( 'Wszystkie Spotkania', 'custom-posts' ),
				'view_item'          => __( 'Zobacz Spotkania', 'custom-posts' ),
				'search_items'       => __( 'Szukaj Spotkań', 'custom-posts' ),
				'not_found'          => __( 'Nie znaleziono spotkań', 'custom-posts' ),
				'not_found_in_trash' => __( 'Nie znaleziono spotkań w Koszu', 'custom-posts' ),
				'menu_name'          => __( 'Spotkania', 'custom-posts' ),
			];
		};

		new CptBuilder( 'meetings', $labels, 5, false );
	}
}
