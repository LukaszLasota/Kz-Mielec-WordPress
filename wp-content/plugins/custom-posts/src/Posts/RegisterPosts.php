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
		add_action( 'template_redirect', [ $this, 'redirect_single_meeting' ] );
	}

	/**
	 * Meetings are only ever shown as a group — on the homepage tiles and the
	 * "zaplanuj-wizyte" archive. Individual meeting pages are not wanted, so
	 * redirect any single-meeting request to the archive, jumping to that
	 * meeting's anchor (matches the homepage tile links: /zaplanuj-wizyte/#slug).
	 * Logged-in editors keep working post previews.
	 *
	 * @return void
	 */
	public function redirect_single_meeting(): void {
		if ( ! is_singular( 'meetings' ) ) {
			return;
		}
		if ( is_preview() && current_user_can( 'edit_posts' ) ) {
			return;
		}
		$meeting = get_queried_object();
		$anchor  = ( $meeting instanceof \WP_Post && '' !== $meeting->post_name ) ? '#' . $meeting->post_name : '';
		wp_safe_redirect( home_url( '/zaplanuj-wizyte/' . $anchor ), 301 );
		exit;
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

		new CptBuilder( 'meetings', $labels, 5, 'zaplanuj-wizyte' );
	}
}
