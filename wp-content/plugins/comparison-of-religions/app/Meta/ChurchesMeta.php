<?php
/**
 * Churches Meta Registration — defines the data schema for comparison content.
 *
 * Registers three meta fields:
 *   1. Post meta 'churches' (array) — the core data structure. Each element is:
 *      { church_name: string, description: string (HTML with <p> tags) }
 *      Example: [
 *        { church_name: "Roman Catholic Church", description: "<p>Baptism is...</p><p>Infant...</p>" },
 *        { church_name: "Pentecostal Church",    description: "<p>Baptism of believers...</p>" }
 *      ]
 *
 *   2. Post meta 'sort_order' (integer) — controls display order of topics
 *      within a category in the accordion. Lower number = displayed first.
 *
 *   3. Term meta 'sort_order' (integer) — controls display order of categories
 *      (accordion panels). Lower number = panel appears first.
 *
 * All meta fields are exposed via REST API (show_in_rest) for Gutenberg compatibility.
 * The 'churches' field has a full JSON Schema definition so WordPress can validate
 * the structure when saving via the REST API.
 *
 * @package ComparisonOfReligions
 */

declare(strict_types=1);

namespace ComparisonOfReligions\Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ComparisonOfReligions\PostTypes\ComparisonTopic;
use ComparisonOfReligions\Taxonomies\ComparisonCategory;

/**
 * Registers post meta and term meta on the 'init' action.
 */
class ChurchesMeta {

	/**
	 * Constructor — hooks registration to 'init'.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Register all meta fields for the comparison data model.
	 */
	public function register(): void {
		// Core data field: array of church positions on this topic.
		// Each element: { church_name: string, description: string (HTML) }.
		register_post_meta(
			ComparisonTopic::POST_TYPE,
			'churches',
			[
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'church_name' => [ 'type' => 'string' ],
								'description' => [ 'type' => 'string' ],
							],
						],
					],
				],
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'sanitize_callback' => [ $this, 'sanitize_churches' ],
			]
		);

		// Display order of this topic within its category (lower = first).
		register_post_meta(
			ComparisonTopic::POST_TYPE,
			'sort_order',
			[
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => true,
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'sanitize_callback' => 'absint',
			]
		);

		// Display order of categories in the accordion (lower = panel appears first).
		register_term_meta(
			ComparisonCategory::TAXONOMY,
			'sort_order',
			[
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => true,
				'auth_callback'     => function () {
					return current_user_can( 'manage_categories' );
				},
				'sanitize_callback' => 'absint',
			]
		);
	}

	/**
	 * Sanitize the churches meta value.
	 *
	 * Ensures each element has only church_name (plain text) and description (safe HTML).
	 * Uses sanitize_text_field() for names and wp_kses_post() for descriptions
	 * (allows standard HTML tags like <p>, <strong>, <a>, etc.).
	 *
	 * @param mixed $value Raw value from REST API or form submission.
	 * @return array Sanitized array of {church_name, description} objects.
	 */
	public function sanitize_churches( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values(
			array_map(
				function ( $church ) {
					return [
						'church_name' => sanitize_text_field( $church['church_name'] ?? '' ),
						'description' => wp_kses_post( $church['description'] ?? '' ),
					];
				},
				$value
			)
		);
	}
}
