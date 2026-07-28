<?php
/**
 * Comparison Topic — Custom Post Type registration.
 *
 * Each post represents one comparison sub-topic (e.g. "Baptism", "Holy Scripture",
 * "Mother of Christ"). The actual comparison content is stored in the 'churches'
 * post meta field (see ChurchesMeta.php), not in post_content.
 *
 * Key settings:
 *   - public = false — topics are not individually viewable; they're rendered
 *     collectively by the comparison-accordion Gutenberg block.
 *   - show_in_rest = true — required for Gutenberg editor support and REST API access.
 *   - supports: title + custom-fields + revisions (no editor — content lives in meta).
 *
 * @package ComparisonOfReligions
 */

declare(strict_types=1);

namespace ComparisonOfReligions\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the comparison_topic CPT on the 'init' action.
 * The static register_post_type() method is also called directly during plugin activation.
 */
class ComparisonTopic {

	public const POST_TYPE = 'comparison_topic';

	/**
	 * Constructor — hooks into WordPress 'init' to register the CPT.
	 */
	public function __construct() {
		add_action( 'init', [ self::class, 'register_post_type' ] );
	}

	/**
	 * Register the comparison_topic post type.
	 *
	 * Static so it can be called both from the 'init' hook and
	 * from the activation hook in index.php (before 'init' fires).
	 */
	public static function register_post_type(): void {
		$labels = [
			'name'               => __( 'Tematy porownania', 'comparison-of-religions' ),
			'singular_name'      => __( 'Temat porownania', 'comparison-of-religions' ),
			'menu_name'          => __( 'Porownania', 'comparison-of-religions' ),
			'all_items'          => __( 'Wszystkie tematy', 'comparison-of-religions' ),
			'add_new'            => __( 'Dodaj temat', 'comparison-of-religions' ),
			'add_new_item'       => __( 'Dodaj nowy temat', 'comparison-of-religions' ),
			'edit_item'          => __( 'Edytuj temat', 'comparison-of-religions' ),
			'new_item'           => __( 'Nowy temat', 'comparison-of-religions' ),
			'view_item'          => __( 'Zobacz temat', 'comparison-of-religions' ),
			'search_items'       => __( 'Szukaj tematow', 'comparison-of-religions' ),
			'not_found'          => __( 'Nie znaleziono tematow', 'comparison-of-religions' ),
			'not_found_in_trash' => __( 'Nie znaleziono w koszu', 'comparison-of-religions' ),
		];

		register_post_type(
			self::POST_TYPE,
			[
				'labels'             => $labels,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'menu_icon'          => 'dashicons-editor-table',
				'menu_position'      => 25,
				'supports'           => [ 'title', 'custom-fields', 'revisions' ],
				'has_archive'        => false,
				'rewrite'            => false,
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'hierarchical'       => false,
			]
		);
	}
}
