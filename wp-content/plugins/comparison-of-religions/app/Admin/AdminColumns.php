<?php
/**
 * Custom Admin Columns for the Comparison Topic CPT list table.
 *
 * Adds two extra columns to the admin post list (wp-admin > Porownania):
 *   1. "Koscioly" (Churches) — shows the number of churches assigned to the topic.
 *   2. "Kolejnosc" (Sort Order) — shows the sort_order meta value, and is sortable
 *      by clicking the column header.
 *
 * This makes it easy to see at a glance which topics have data and in what order
 * they'll appear in the accordion.
 *
 * @package ComparisonOfReligions
 */

declare(strict_types=1);

namespace ComparisonOfReligions\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ComparisonOfReligions\PostTypes\ComparisonTopic;

/**
 * Adds custom columns to the CPT list table and enables sorting by sort_order.
 */
class AdminColumns {

	/**
	 * Constructor — hooks into the four filters/actions needed for custom columns.
	 */
	public function __construct() {
		$post_type = ComparisonTopic::POST_TYPE;

		add_filter( "manage_{$post_type}_posts_columns", [ $this, 'columns' ] );
		add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'column_content' ], 10, 2 );
		add_filter( "manage_edit-{$post_type}_sortable_columns", [ $this, 'sortable_columns' ] );
		add_action( 'pre_get_posts', [ $this, 'sort_by_order' ] );
	}

	/**
	 * Define custom columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function columns( array $columns ): array {
		$new_columns = [];

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			// Insert custom columns after title.
			if ( 'title' === $key ) {
				$new_columns['churches_count'] = __( 'Koscioly', 'comparison-of-religions' );
				$new_columns['sort_order']     = __( 'Kolejnosc', 'comparison-of-religions' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column slug.
	 * @param int    $post_id Post ID.
	 */
	public function column_content( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'churches_count':
				$churches = get_post_meta( $post_id, 'churches', true );
				echo esc_html( is_array( $churches ) ? (string) count( $churches ) : '0' );
				break;

			case 'sort_order':
				echo esc_html( (string) (int) get_post_meta( $post_id, 'sort_order', true ) );
				break;
		}
	}

	/**
	 * Make sort_order column sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sortable_columns( array $columns ): array {
		$columns['sort_order'] = 'sort_order';
		return $columns;
	}

	/**
	 * Handle sorting by sort_order meta.
	 *
	 * @param \WP_Query $query Main query.
	 */
	public function sort_by_order( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ComparisonTopic::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( 'sort_order' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', 'sort_order' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}
}
