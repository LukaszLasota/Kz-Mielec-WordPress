<?php
/**
 * Custom admin columns for Meetings post type
 *
 * @package CustomPostsPlugin
 */

namespace CustomPostsPlugin\Posts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CustomColumns
 *
 * Customizes admin list table columns for the Meetings post type.
 */
class CustomColumns {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'manage_meetings_posts_columns', [ $this, 'set_custom_columns' ] );
		add_action( 'manage_meetings_posts_custom_column', [ $this, 'render_custom_column_content' ], 10, 2 );
	}

	/**
	 * Define custom columns for the meetings list table.
	 *
	 * @param array<string, string> $columns Default columns.
	 * @return array<string, string> Modified columns.
	 */
	public function set_custom_columns( $columns ): array {
		$columns = [];

		$columns['title']             = __( 'Tytuł', 'custom-posts' );
		$columns['author']            = __( 'Autor', 'custom-posts' );
		$columns['taxonomy-category'] = __( 'Kategorie', 'custom-posts' );
		$columns['taxonomy-post_tag'] = __( 'Tagi', 'custom-posts' );
		$columns['date']              = __( 'Data', 'custom-posts' );
		$columns['modified_date']     = __( 'Ostatnia modyfikacja', 'custom-posts' );

		return $columns;
	}

	/**
	 * Render content for custom columns.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_custom_column_content( $column, $post_id ): void {
		switch ( $column ) {
			case 'title':
				echo '<strong>' . esc_html( get_the_title( $post_id ) ) . '</strong>';
				break;
			case 'author':
				$author = get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) );
				echo esc_html( $author );
				break;
			case 'taxonomy-category':
				$terms = get_the_term_list( $post_id, 'category', '', ', ' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					echo wp_kses_post( $terms );
				} else {
					echo esc_html__( 'Brak kategorii', 'custom-posts' );
				}
				break;
			case 'taxonomy-post_tag':
				$tags = get_the_term_list( $post_id, 'post_tag', '', ', ' );
				if ( $tags && ! is_wp_error( $tags ) ) {
					echo wp_kses_post( $tags );
				} else {
					echo esc_html__( 'Brak tagów', 'custom-posts' );
				}
				break;
			case 'date':
				echo esc_html( get_the_date( '', $post_id ) );
				break;
			case 'modified_date':
				$modified_date = get_post_modified_time( 'Y-m-d H:i', false, $post_id );
				echo esc_html( $modified_date );
				break;
		}
	}
}
