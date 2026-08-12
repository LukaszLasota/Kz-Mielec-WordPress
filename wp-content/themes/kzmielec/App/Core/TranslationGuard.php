<?php
/**
 * Keeps translated content out of the site when Polylang is not managing it.
 *
 * @package Kzmielec
 */

declare(strict_types=1);

namespace Kzmielec\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A safety net for one specific accident: Polylang being switched off.
 *
 * Without Polylang the site does not break — every Polish URL still returns 200,
 * the accessibility strip works and the language switcher disappears cleanly,
 * because the template guards on an empty language list. What does happen is
 * subtler and worse for the site's standing: the 174 translated posts stop being
 * translations and become ordinary pages of the Polish site. Measured with
 * Polylang off, the page sitemap went from 18 entries to 72 and the comparison
 * topics from 37 to 148 — English, Ukrainian and Spanish content offered to
 * Google as part of a Polish site, with no hreflang to explain it.
 *
 * WHY THIS LIVES IN THE THEME, not in `kzmielec-translate` where it started.
 * That plugin is a migration tool: it translates the content once and then has
 * nothing left to do. While the guard lived inside it, the plugin could never be
 * switched off, which is a strange thing to require of a tool whose job is
 * finished — and a requirement whoever inherits this site in a year would not
 * know about. The theme is always active, so here the protection cannot be
 * removed by accident. `kzmielec-translate` is now genuinely optional.
 *
 * The two meta keys below are the contract with that plugin, deliberately
 * duplicated as literals rather than referenced through its classes: this code
 * has to keep working after the plugin is deleted, and a `SegmentStore::META_KEY`
 * would be a fatal error the moment it was.
 *
 * When Polylang is active this class does nothing at all — Polylang's own
 * language filtering is both correct and cheaper.
 */
class TranslationGuard {

	/**
	 * Post meta that marks a post as machine-translated by `kzmielec-translate`.
	 *
	 * Contract with that plugin's `SegmentStore::META_KEY`. A literal on purpose —
	 * see the class comment.
	 */
	private const POST_MARKER = '_kzt_segments';

	/**
	 * Term meta that marks a taxonomy term as a translation.
	 *
	 * Contract with that plugin's `TermTranslator::MARKER`.
	 */
	private const TERM_MARKER = '_kzt_translated';

	/**
	 * Language prefixes this project uses, other than the default.
	 *
	 * @var array<int, string>
	 */
	private const PREFIXES = array( 'en', 'uk', 'es' );

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'pre_get_posts', array( $this, 'exclude_translations' ) );
		add_filter( 'get_terms_args', array( $this, 'exclude_translated_terms' ), 10, 2 );
		add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', array( $this, 'exclude_from_sitemap' ) );

		/*
		 * Priority 5, ahead of core's `redirect_canonical` at 10 — see the method.
		 */
		add_action( 'template_redirect', array( $this, 'retire_language_prefixes' ), 5 );
	}

	/**
	 * Whether Polylang is present and therefore in charge.
	 *
	 * @return bool
	 */
	private function polylang_active(): bool {
		return function_exists( 'pll_get_post' );
	}

	/**
	 * Drop translated posts from front-end queries while Polylang is inactive.
	 *
	 * Every front-end query, not only the main one. Restricting this to
	 * `is_main_query()` looked tidier and was wrong: the tiles, the beliefs
	 * navigation and the comparison accordion all run their own WP_Query, so with
	 * Polylang off they pulled all four languages at once — the Polish home page
	 * ended up with meeting tiles anchored to `#головне-богослужіння`. Measured,
	 * not imagined.
	 *
	 * @param \WP_Query $query Query being prepared.
	 * @return void
	 */
	public function exclude_translations( \WP_Query $query ): void {
		if ( $this->polylang_active() || is_admin() ) {
			return;
		}

		$meta = (array) $query->get( 'meta_query' );

		$meta[] = array(
			'key'     => self::POST_MARKER,
			'compare' => 'NOT EXISTS',
		);

		$query->set( 'meta_query', $meta );
	}

	/**
	 * Keep translated taxonomy terms out of term queries while Polylang is off.
	 *
	 * `get_terms()` is not a WP_Query, so the pre_get_posts guard never sees it.
	 * Without this the comparison accordion listed all four languages' categories
	 * side by side on the Polish page.
	 *
	 * Implemented with `exclude` rather than a meta query on purpose: the block's
	 * own term query already sets `meta_key => sort_order`, and adding a second
	 * meta condition to that is how you get a query that silently returns nothing.
	 *
	 * @param array<string, mixed> $args       Term query arguments.
	 * @param array<int, string>   $taxonomies Taxonomies being queried.
	 * @return array<string, mixed>
	 */
	public function exclude_translated_terms( array $args, array $taxonomies ): array {
		if ( $this->polylang_active() || is_admin() ) {
			return $args;
		}

		/*
		 * Recursion stop: the lookup below is itself a get_terms() call and would
		 * re-enter this filter forever without a way to recognise its own query.
		 */
		if ( ! empty( $args['kzmielec_internal'] ) ) {
			return $args;
		}

		if ( array() === array_intersect( (array) $taxonomies, array( 'comparison_category' ) ) ) {
			return $args;
		}

		$translated = get_terms(
			array(
				'taxonomy'          => array( 'comparison_category' ),
				'hide_empty'        => false,
				'fields'            => 'ids',
				'meta_key'          => self::TERM_MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- nine terms, only while Polylang is inactive.
				'kzmielec_internal' => true,
			)
		);

		if ( is_wp_error( $translated ) || array() === $translated ) {
			return $args;
		}

		$args['exclude'] = array_merge(
			(array) ( $args['exclude'] ?? array() ),
			array_map( 'intval', $translated )
		);

		return $args;
	}

	/**
	 * Keep translated posts out of Yoast's sitemaps while Polylang is off.
	 *
	 * Yoast does not build its sitemaps with WP_Query — the post type provider
	 * queries the database directly — so the pre_get_posts guard cannot reach it.
	 * Measured with Polylang off: the page sitemap listed 72 URLs instead of 18 and
	 * the meetings sitemap 13 instead of 4, offering English, Ukrainian and Spanish
	 * pages to search engines as part of a Polish site with no hreflang to explain
	 * them.
	 *
	 * The ids are read with raw SQL on purpose. The obvious `get_posts()` call would
	 * come back empty, because `exclude_translations()` above adds a NOT EXISTS
	 * condition on this very meta key to every front-end query — including this one.
	 *
	 * @param array<int, int> $excluded Post ids Yoast already excludes.
	 * @return array<int, int>
	 */
	public function exclude_from_sitemap( $excluded ): array {
		$excluded = (array) $excluded;

		if ( $this->polylang_active() ) {
			return $excluded;
		}

		global $wpdb;

		$ours = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
				self::POST_MARKER
			)
		);

		return array_values(
			array_unique(
				array_merge( array_map( 'intval', $excluded ), array_map( 'intval', $ours ) )
			)
		);
	}

	/**
	 * Retire the language-prefixed addresses when Polylang is not there to serve them.
	 *
	 * Without Polylang there is no `/en/` any more, and left alone the result was
	 * worse than a plain 404. WordPress's own canonical guessing matches the bare
	 * prefix against post slugs, and the translated posts are still in the database:
	 * `/en/` answered 301 to `/en-que-creemos/` — a SPANISH page whose slug happens
	 * to start with "en" — which then answered 404, because the guard above keeps
	 * translated posts out of queries. A visitor following an indexed foreign URL
	 * took two hops to a dead end in the wrong language.
	 *
	 * Priority 5 so this runs before `redirect_canonical`, which is registered at 10
	 * and is the thing doing the guessing.
	 *
	 * The home page is the honest destination: the language version is gone, not
	 * moved. Mapping each foreign URL back to its Polish counterpart would mean
	 * reading Polylang's translation groups, and Polylang is precisely what is
	 * missing in this scenario.
	 *
	 * @return void
	 */
	public function retire_language_prefixes(): void {
		if ( $this->polylang_active() || is_admin() ) {
			return;
		}

		/*
		 * A post type archive under a foreign prefix has a better destination than
		 * the home page — the same archive in Polish — and `MeetingsArchiveSlugs`
		 * in the custom-posts plugin knows how to build it. Leaving it alone here is
		 * what lets that run.
		 */
		if ( is_post_type_archive() ) {
			return;
		}

		$request = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';
		$path    = trim( (string) wp_parse_url( $request, PHP_URL_PATH ), '/' );

		if ( '' === $path ) {
			return;
		}

		$segments = explode( '/', $path );

		if ( ! in_array( $segments[0], self::PREFIXES, true ) ) {
			return;
		}

		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
