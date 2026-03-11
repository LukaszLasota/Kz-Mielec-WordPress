<?php
/**
 * Taxonomy Builder
 *
 * @package CustomPostsPlugin
 */

namespace CustomPostsPlugin\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TaxBuilder
 *
 * Reusable builder for registering custom taxonomies.
 */
class TaxBuilder {

	/**
	 * Taxonomy slug.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * Associated post type.
	 *
	 * @var string
	 */
	private string $post_type;

	/**
	 * Taxonomy labels array or callable that returns labels.
	 *
	 * @var array<string, string>|callable(): array<string, string>
	 */
	private $labels;

	/**
	 * Optional extra arguments.
	 *
	 * @var array<string, mixed>
	 */
	private array $args;

	/**
	 * Constructor.
	 *
	 * @param string                                                  $slug      Taxonomy slug.
	 * @param string                                                  $post_type Associated post type slug.
	 * @param array<string, string>|callable(): array<string, string> $labels    Labels array or callable returning labels.
	 * @param array<string, mixed>                                    $args      Optional extra arguments.
	 */
	public function __construct( string $slug, string $post_type, $labels, array $args = [] ) {
		$this->slug      = $slug;
		$this->post_type = $post_type;
		$this->labels    = $labels;
		$this->args      = $args;

		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Register the taxonomy.
	 *
	 * @return void
	 */
	public function register(): void {
		$resolved_labels = is_callable( $this->labels ) ? ( $this->labels )() : $this->labels;

		$tax_args = array_merge(
			[
				'hierarchical'      => true,
				'labels'            => $resolved_labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => [ 'slug' => $this->slug ],
			],
			$this->args
		);

		register_taxonomy( $this->slug, [ $this->post_type ], $tax_args );
	}
}
