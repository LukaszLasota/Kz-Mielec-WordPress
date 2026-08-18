<?php
/**
 * Writing the current database contents out as seed files.
 *
 * This is the direction that makes the data portable: whatever the site holds
 * now becomes the starting point another site can be built from. It reads only,
 * and it never calls a translation API - the translations are already in the
 * database, put there once.
 *
 * The identity of a topic across languages is the slug of its source-language
 * counterpart, taken from Polylang's own links at export time. Post IDs are
 * never written to a file: they are local to one database and would be wrong
 * everywhere else.
 *
 * @package ComparisonOfReligions
 */

declare(strict_types=1);

namespace ComparisonOfReligions\Seed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ComparisonOfReligions\PostTypes\ComparisonTopic;
use ComparisonOfReligions\Taxonomies\ComparisonCategory;
use WP_Post;
use WP_Term;

/**
 * Turns the database into seed files, one per language.
 */
class SeedExporter {

	/**
	 * Export every language the site has.
	 *
	 * @param array<int, string> $languages Slugs to export; empty means all the site has.
	 * @return array<string, array{topics:int,categories:int,written:bool}> Report per language.
	 * @throws \RuntimeException When translated content exists that nothing can tell apart.
	 */
	public function export( array $languages = [] ): array {
		if ( Languages::stale_translations() ) {
			throw new \RuntimeException(
				'This database holds more than one language but Polylang is not active, '
				. 'so the languages cannot be told apart. Activate Polylang before exporting.'
			);
		}

		$targets = [] === $languages
			? Languages::site()
			: array_values( array_intersect( $languages, Languages::site() ) );

		$report = [];

		foreach ( $targets as $lang ) {
			$categories = $this->categories( $lang );
			$topics     = $this->topics( $lang );

			$report[ $lang ] = [
				'categories' => count( $categories ),
				'topics'     => count( $topics ),
				'written'    => SeedFile::write(
					$lang,
					[
						'categories' => $categories,
						'topics'     => $topics,
					]
				),
			];
		}

		return $report;
	}

	/**
	 * Categories of one language, sorted by key.
	 *
	 * Sorted on purpose: an export whose order depends on term ids produces a
	 * different file every time the database is rebuilt, and a file that always
	 * differs hides the change that matters.
	 *
	 * @param string $lang Language slug.
	 * @return array<int, array<string, mixed>>
	 */
	private function categories( string $lang ): array {
		$rows = [];

		foreach ( $this->terms( $lang ) as $term ) {
			$parent = 0 !== $term->parent ? get_term( $term->parent, ComparisonCategory::TAXONOMY ) : null;

			$rows[] = [
				'key'    => $this->term_key( $term ),
				'name'   => $term->name,
				'slug'   => $term->slug,
				'order'  => (int) get_term_meta( $term->term_id, 'sort_order', true ),
				'parent' => $parent instanceof WP_Term ? $this->term_key( $parent ) : null,
			];
		}

		usort( $rows, static fn( array $a, array $b ): int => strcmp( (string) $a['key'], (string) $b['key'] ) );

		return $rows;
	}

	/**
	 * Topics of one language, sorted by key.
	 *
	 * @param string $lang Language slug.
	 * @return array<int, array<string, mixed>>
	 */
	private function topics( string $lang ): array {
		$rows = [];

		foreach ( $this->posts( $lang ) as $post ) {
			$assigned = wp_get_object_terms( $post->ID, ComparisonCategory::TAXONOMY );
			$keys     = [];

			if ( is_array( $assigned ) ) {
				foreach ( $assigned as $term ) {
					$keys[] = $this->term_key( $term );
				}
			}

			$rows[] = [
				'key'        => $this->post_key( $post ),
				'title'      => $post->post_title,
				'slug'       => $post->post_name,
				'sort_order' => (int) get_post_meta( $post->ID, 'sort_order', true ),
				'categories' => $keys,
				'churches'   => $this->churches( $post->ID ),
			];
		}

		usort( $rows, static fn( array $a, array $b ): int => strcmp( (string) $a['key'], (string) $b['key'] ) );

		return $rows;
	}

	/**
	 * The churches meta of one topic, reduced to the two fields that carry data.
	 *
	 * @param int $post_id Topic id.
	 * @return array<int, array{church_name:string,description:string}>
	 */
	private function churches( int $post_id ): array {
		$stored = get_post_meta( $post_id, 'churches', true );
		$rows   = [];

		if ( ! is_array( $stored ) ) {
			return $rows;
		}

		foreach ( $stored as $church ) {
			if ( ! is_array( $church ) ) {
				continue;
			}

			$rows[] = [
				'church_name' => isset( $church['church_name'] ) ? (string) $church['church_name'] : '',
				'description' => isset( $church['description'] ) ? (string) $church['description'] : '',
			];
		}

		return $rows;
	}

	/**
	 * Topics of one language.
	 *
	 * Every topic is fetched and then filtered by its own language, rather than
	 * asking the query to narrow. See `Languages::post_speaks()` for why the
	 * query argument cannot be trusted here.
	 *
	 * @param string $lang Language slug.
	 * @return array<int, WP_Post>
	 */
	private function posts( string $lang ): array {
		$posts = get_posts(
			[
				'post_type'        => ComparisonTopic::POST_TYPE,
				'post_status'      => [ 'publish', 'draft', 'pending', 'private' ],
				'numberposts'      => -1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'suppress_filters' => false,
			]
		);

		$mine = [];

		foreach ( $posts as $post ) {
			if ( Languages::post_speaks( $post->ID, $lang ) ) {
				$mine[] = $post;
			}
		}

		return $mine;
	}

	/**
	 * Categories of one language.
	 *
	 * @param string $lang Language slug.
	 * @return array<int, WP_Term>
	 */
	private function terms( string $lang ): array {
		$terms = get_terms(
			[
				'taxonomy'   => ComparisonCategory::TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			]
		);

		if ( ! is_array( $terms ) ) {
			return [];
		}

		$mine = [];

		foreach ( $terms as $term ) {
			if ( Languages::term_speaks( $term->term_id, $lang ) ) {
				$mine[] = $term;
			}
		}

		return $mine;
	}

	/**
	 * Cross-language identity of a topic: the slug it has in the source language.
	 *
	 * @param WP_Post $post Topic in any language.
	 */
	private function post_key( WP_Post $post ): string {
		if ( ! function_exists( 'pll_get_post_translations' ) ) {
			return $post->post_name;
		}

		$translations = pll_get_post_translations( $post->ID );

		if ( ! is_array( $translations ) || ! isset( $translations[ Languages::SOURCE ] ) ) {
			return $post->post_name;
		}

		$source = get_post( (int) $translations[ Languages::SOURCE ] );

		return $source instanceof WP_Post ? $source->post_name : $post->post_name;
	}

	/**
	 * Cross-language identity of a category: its slug in the source language.
	 *
	 * @param WP_Term $term Category in any language.
	 */
	private function term_key( WP_Term $term ): string {
		if ( ! function_exists( 'pll_get_term_translations' ) ) {
			return $term->slug;
		}

		$translations = pll_get_term_translations( $term->term_id );

		if ( ! is_array( $translations ) || ! isset( $translations[ Languages::SOURCE ] ) ) {
			return $term->slug;
		}

		$source = get_term( (int) $translations[ Languages::SOURCE ], ComparisonCategory::TAXONOMY );

		return $source instanceof WP_Term ? $source->slug : $term->slug;
	}
}
