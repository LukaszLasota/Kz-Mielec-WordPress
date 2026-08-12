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
		add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', [ $this, 'exclude_meetings_from_sitemap' ] );
	}

	/**
	 * Keep single meeting pages out of the sitemap, because they always redirect.
	 *
	 * Every one of them answers 301 to the archive — that is what
	 * `redirect_single_meeting()` below is for. A sitemap is a list of pages worth
	 * indexing, and a URL that never returns 200 is not one: Search Console reports
	 * it as "Page with redirect" and the crawl budget is spent for nothing.
	 *
	 * Measured: the meetings sitemap listed 16 URLs of which 12 redirected — three
	 * meetings in four languages. It is not a new problem, the Polish ones behaved
	 * this way before the site had other languages, but translating the content
	 * multiplied it by four.
	 *
	 * The archive itself stays in the sitemap. Yoast lists it separately from the
	 * single posts, and it is the page that should be indexed.
	 *
	 * Read with raw SQL on purpose: `get_posts()` here would be filtered by language
	 * (Polylang) or by the theme's translation guard (with Polylang off), and either
	 * way would return a subset — leaving some redirecting URLs in the sitemap.
	 *
	 * @param array<int, int> $excluded Post ids Yoast already excludes.
	 * @return array<int, int>
	 */
	public function exclude_meetings_from_sitemap( $excluded ): array {
		global $wpdb;

		$excluded = (array) $excluded;

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
				'meetings'
			)
		);

		return array_values(
			array_unique(
				array_merge( array_map( 'intval', $excluded ), array_map( 'intval', $ids ) )
			)
		);
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

		if ( ! $meeting instanceof \WP_Post ) {
			wp_safe_redirect( MeetingsArchiveSlugs::archive_url( 'pl' ), 301 );
			exit;
		}

		/*
		 * The archive in the MEETING's own language, taken from the post rather
		 * than from the ambient current language. The slug was hardcoded to
		 * `/zaplanuj-wizyte/` before, so every English, Ukrainian and Spanish
		 * meeting URL landed on the POLISH archive — measured:
		 * `/en/meetings/bible-study-and-prayer/` answered 301 to
		 * `/zaplanuj-wizyte/#bible-study-and-prayer`, a Polish page with an English
		 * anchor. Reading the post's language rather than `pll_current_language()`
		 * is deliberate: this runs during a redirect, where the ambient language is
		 * exactly the thing that was wrong.
		 */
		$lang = function_exists( 'pll_get_post_language' )
			? (string) pll_get_post_language( $meeting->ID )
			: 'pl';

		if ( '' === $lang || ! isset( MeetingsArchiveSlugs::SLUGS[ $lang ] ) ) {
			$lang = 'pl';
		}

		/*
		 * Ukrainian slugs are stored percent-encoded, so decoding before encoding
		 * keeps the anchor from being escaped twice — the same treatment the tiles
		 * give it, and the two have to agree or the jump silently misses.
		 */
		$anchor = '' !== $meeting->post_name
			? '#' . rawurlencode( rawurldecode( $meeting->post_name ) )
			: '';

		wp_safe_redirect( MeetingsArchiveSlugs::archive_url( $lang ) . $anchor, 301 );
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
