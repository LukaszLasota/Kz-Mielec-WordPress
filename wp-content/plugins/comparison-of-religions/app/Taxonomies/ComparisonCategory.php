<?php
/**
 * Comparison Category — Custom Taxonomy registration.
 *
 * Categories group comparison topics into accordion panels. Each term becomes
 * one collapsible section in the frontend accordion (e.g. "Sacraments", "God",
 * "Eschatology"). The display order is controlled by 'sort_order' term meta.
 *
 * Key settings:
 *   - hierarchical = true — behaves like WordPress categories (parent/child).
 *   - public = false — no archive pages; terms are only used for grouping.
 *   - show_admin_column = true — shows category in the CPT list table.
 *
 * @package ComparisonOfReligions
 */

declare(strict_types=1);

namespace ComparisonOfReligions\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ComparisonOfReligions\PostTypes\ComparisonTopic;

/**
 * Registers the comparison_category taxonomy on the 'init' action.
 * Also called directly during plugin activation (see index.php).
 */
class ComparisonCategory {

	public const TAXONOMY = 'comparison_category';

	/**
	 * Constructor — hooks into WordPress 'init'.
	 */
	public function __construct() {
		add_action( 'init', [ self::class, 'register_taxonomy' ] );
	}

	/**
	 * Register the comparison_category taxonomy attached to comparison_topic CPT.
	 */
	public static function register_taxonomy(): void {
		$labels = [
			'name'              => __( 'Kategorie porownania', 'comparison-of-religions' ),
			'singular_name'     => __( 'Kategoria porownania', 'comparison-of-religions' ),
			'add_new_item'      => __( 'Dodaj kategorie', 'comparison-of-religions' ),
			'edit_item'         => __( 'Edytuj kategorie', 'comparison-of-religions' ),
			'search_items'      => __( 'Szukaj kategorii', 'comparison-of-religions' ),
			'all_items'         => __( 'Wszystkie kategorie', 'comparison-of-religions' ),
			'parent_item'       => __( 'Kategoria nadrzedna', 'comparison-of-religions' ),
			'parent_item_colon' => __( 'Kategoria nadrzedna:', 'comparison-of-religions' ),
			'new_item_name'     => __( 'Nowa kategoria', 'comparison-of-religions' ),
			'menu_name'         => __( 'Kategorie porównania', 'comparison-of-religions' ),
		];

		register_taxonomy(
			self::TAXONOMY,
			ComparisonTopic::POST_TYPE,
			[
				'labels'            => $labels,
				'hierarchical'      => true,
				'public'            => false,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => false,
			]
		);
	}
}
