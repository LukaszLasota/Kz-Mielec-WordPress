<?php
/**
 * Setup class
 *
 * Handles WordPress theme setup and feature registration.
 *
 * @package Kzmielec\BasicTheme
 */

namespace Kzmielec\BasicTheme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class Setup
 *
 * Handles WordPress theme setup and feature registration.
 */
class Setup implements ActionHookInterface {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
	}

	/**
	 * Meeting meta keys that should be searchable alongside title/content
	 * (e.g. the nominative day "Niedziela 10:30" and the address, which live
	 * in meta and are otherwise invisible to WordPress' default search).
	 *
	 * @var string[]
	 */
	private const SEARCHABLE_META_KEYS = array( '_meeting_day_hour', '_meeting_place' );

	/**
	 * Register WordPress action hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'after_setup_theme', array( $this, 'kzmielec_setup' ) );
		add_action( 'pre_get_posts', array( $this, 'restrict_search_post_types' ) );
		add_filter( 'posts_join', array( $this, 'search_meta_join' ), 10, 2 );
		add_filter( 'posts_search', array( $this, 'search_meta_where' ), 10, 2 );
		add_filter( 'posts_distinct', array( $this, 'search_meta_distinct' ), 10, 2 );
	}

	/**
	 * Keep media attachments out of front-end search results.
	 *
	 * Attachments are public and searchable by default in WordPress, which
	 * lets raw media files surface on the search page. Restrict the main
	 * front-end search query to real content types.
	 *
	 * @param \WP_Query $query The query being prepared.
	 * @return void
	 */
	public function restrict_search_post_types( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}

		$query->set( 'post_type', array( 'post', 'page', 'meetings' ) );
	}

	/**
	 * Whether the given query is the front-end main search query.
	 *
	 * @param \WP_Query $query The query.
	 * @return bool
	 */
	private function is_frontend_search( \WP_Query $query ): bool {
		return ! is_admin() && $query->is_main_query() && $query->is_search();
	}

	/**
	 * Join the postmeta table (whitelisted keys) onto the search query so
	 * meeting meta becomes searchable.
	 *
	 * @param string    $join  The JOIN clause.
	 * @param \WP_Query $query The query.
	 * @return string
	 */
	public function search_meta_join( string $join, \WP_Query $query ): string {
		global $wpdb;

		if ( ! $this->is_frontend_search( $query ) ) {
			return $join;
		}

		// esc_sql() is declared as accepting and returning array|string, so
		// passing it to array_map() leaves implode() with an array<array|string>
		// as far as static analysis is concerned. Escaping each key through a
		// typed closure keeps the value list provably array<string>.
		$escaped_keys = array_map(
			static function ( string $meta_key ): string {
				return (string) esc_sql( $meta_key );
			},
			self::SEARCHABLE_META_KEYS
		);

		$keys  = "'" . implode( "','", $escaped_keys ) . "'";
		$join .= " LEFT JOIN {$wpdb->postmeta} AS kz_sm ON ( {$wpdb->posts}.ID = kz_sm.post_id AND kz_sm.meta_key IN ( {$keys} ) ) ";

		return $join;
	}

	/**
	 * OR the search terms against the joined meeting meta values, so a card
	 * matches when a term appears in title, content OR the whitelisted meta.
	 *
	 * @param string    $search The search SQL fragment (leading " AND (...)").
	 * @param \WP_Query $query  The query.
	 * @return string
	 */
	public function search_meta_where( string $search, \WP_Query $query ): string {
		global $wpdb;

		if ( '' === $search || ! $this->is_frontend_search( $query ) ) {
			return $search;
		}

		$terms = $query->query_vars['search_terms'] ?? array();
		if ( empty( $terms ) ) {
			$typed = trim( (string) $query->get( 's' ) );
			if ( '' === $typed ) {
				return $search;
			}
			$terms = array( $typed );
		}

		$meta_ors = array();
		foreach ( $terms as $term ) {
			$meta_ors[] = $wpdb->prepare( 'kz_sm.meta_value LIKE %s', '%' . $wpdb->esc_like( $term ) . '%' );
		}
		$meta_condition = '(' . implode( ' OR ', $meta_ors ) . ')';

		// $search is " AND ( <title/content matching> )"; widen it to also
		// accept a meta match: " AND ( ( <original> ) OR <meta> )".
		$inner = preg_replace( '/^\s*AND\s*/', '', $search );

		return " AND ( {$inner} OR {$meta_condition} )";
	}

	/**
	 * Force DISTINCT on the search query — the meta join can otherwise
	 * duplicate a post that matches on more than one meta row.
	 *
	 * @param string    $distinct The DISTINCT clause.
	 * @param \WP_Query $query    The query.
	 * @return string
	 */
	public function search_meta_distinct( string $distinct, \WP_Query $query ): string {
		return $this->is_frontend_search( $query ) ? 'DISTINCT' : $distinct;
	}

	/**
	 * Setup theme features and supports.
	 *
	 * @return void
	 */
	public function kzmielec_setup(): void {
		add_theme_support( 'menus' );

		add_theme_support( 'post-thumbnails' );

		add_theme_support( 'title-tag' );

		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
		);

		add_theme_support( 'post-formats', array( 'image', 'video', 'quote', 'gallery', 'aside' ) );

		add_theme_support( 'responsive-embeds' );

		add_theme_support( 'custom-background' );

		add_theme_support( 'automatic-feed-links' );

		add_theme_support( 'align-wide' );

		add_theme_support( 'block-templates' );

		add_theme_support( 'block-template-parts' );

		add_theme_support( 'footer-widgets', 3 );

		add_theme_support( 'customize-selective-refresh-widgets' );

		add_theme_support( 'editor-styles' );

		add_editor_style( 'assets/css/editor.css' );

		add_theme_support( 'wp-block-styles' );

		add_image_size( 'blog-card', 600, 400, false );
	}
}
