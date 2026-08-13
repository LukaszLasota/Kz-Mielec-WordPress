<?php
/**
 * Navigable Tiles Service.
 *
 * Unified data fetcher for navigable-tiles block (meetings CPT or beliefs pages).
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\Services;

use CustomBlockPackage\Admin\MeetingMeta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NavigableTilesService
 */
class NavigableTilesService {

	/**
	 * Option key for belief page IDs (defined in theme).
	 */
	private const OPTION_BELIEF_PAGES = 'kzmielec_belief_pages';

	/**
	 * Meta key for belief page hover image (defined in theme).
	 */
	private const META_BELIEF_HOVER_IMAGE = '_belief_hover_image';

	/**
	 * Language the tiles should be built for.
	 *
	 * On the front end Polylang narrows every query to the language of the request, so
	 * nothing has to be said out loud. In the block editor there is no such request: the
	 * block renders through the `/wp/v2/block-renderer/` route, Polylang has no language
	 * to narrow by, and an unqualified query answers with every language at once — twelve
	 * meetings instead of three, which both duplicates the tiles and bursts the layout the
	 * captions sit in.
	 *
	 * The language is therefore taken from the post being rendered, which that route makes
	 * the current post, and only then from the request. An empty string means "do not
	 * narrow" — which is the right answer when Polylang is switched off, because then there
	 * is one language and every post is in it.
	 *
	 * @return string Language slug, or an empty string.
	 */
	private static function current_language(): string {
		if ( ! function_exists( 'pll_get_post_language' ) ) {
			return '';
		}

		$post = get_post();

		if ( $post instanceof \WP_Post ) {
			$lang = pll_get_post_language( $post->ID );

			if ( is_string( $lang ) && '' !== $lang ) {
				return $lang;
			}
		}

		if ( function_exists( 'pll_current_language' ) ) {
			$lang = pll_current_language( 'slug' );

			if ( is_string( $lang ) && '' !== $lang ) {
				return $lang;
			}
		}

		return '';
	}

	/**
	 * Get all meetings as normalized items.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_meetings(): array {
		$args = array(
			'post_type'      => 'meetings',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		);

		$lang = self::current_language();

		if ( '' !== $lang ) {
			$args['lang'] = $lang;
		}

		$query = new \WP_Query( $args );

		$items = array();

		if ( ! $query->have_posts() ) {
			return $items;
		}

		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();

			if ( false === $post_id ) {
				continue;
			}

			// get_post_field() is declared as returning array|int|string, so it
			// cannot be cast straight to string — narrow it instead.
			//
			// $day_hour is built in the language of this page from the shared
			// weekday/time pair; the derived meta of the same name is only a
			// search index.
			$post_name = get_post_field( 'post_name', $post_id );
			$anchor    = is_string( $post_name ) ? $post_name : '';
			$day_hour  = MeetingSchedule::label( $post_id );
			$hover_id  = (int) get_post_meta( $post_id, MeetingMeta::META_HOVER_IMAGE, true );
			$base_id   = (int) get_post_thumbnail_id( $post_id );

			/*
			 * The archive slug is asked for, never spelled out. It used to be
			 * hardcoded as `/zaplanuj-wizyte/`, which bypassed the per-language
			 * archive addresses entirely: on every foreign version all three
			 * meeting tiles pointed at the Polish archive — 342 links across the
			 * site, each carrying a correctly translated anchor to a page in the
			 * wrong language.
			 *
			 * `get_post_type_archive_link()` is filtered by
			 * MeetingsArchiveSlugs::filter_archive_link and returns
			 * `/en/plan-your-visit/`, `/uk/zaplanuyte-vizyt/` or
			 * `/es/planifica-tu-visita/` as appropriate — and the Polish slug when
			 * Polylang is off.
			 */
			$archive = get_post_type_archive_link( 'meetings' );

			/*
			 * Decoded before encoding, because a Cyrillic or accented slug is
			 * already percent-encoded in the database and encoding it again turns
			 * `%d0%b2` into `%25d0%25b2` — an anchor that matches nothing on the
			 * page. Decode-then-encode is idempotent and safe for plain ASCII
			 * slugs too.
			 */
			$link = ( $anchor && is_string( $archive ) )
				? trailingslashit( $archive ) . '#' . rawurlencode( rawurldecode( $anchor ) )
				: (string) get_permalink( $post_id );

			$items[] = array(
				'id'             => $post_id,
				'page_id'        => $post_id,
				'title'          => (string) get_the_title( $post_id ),
				'link'           => $link,
				// Both the URL and the id: the id lets the renderer emit srcset and
				// intrinsic dimensions, the URL stays as the fallback for an image
				// that is no longer in the media library.
				'image_base'     => $base_id ? (string) wp_get_attachment_image_url( $base_id, 'full' ) : '',
				'image_hover'    => $hover_id ? (string) wp_get_attachment_image_url( $hover_id, 'full' ) : '',
				'image_base_id'  => $base_id,
				'image_hover_id' => $hover_id,
				'day_hour'       => $day_hour,
				'anchor'         => $anchor,
				'is_current'     => false, // Filled in render.
			);
		}

		wp_reset_postdata();

		return $items;
	}

	/**
	 * Get all beliefs as normalized items.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_beliefs(): array {
		$page_ids = (array) get_option( self::OPTION_BELIEF_PAGES, array() );
		$page_ids = array_filter( array_map( 'intval', $page_ids ) );

		if ( empty( $page_ids ) ) {
			return array();
		}

		$items = array();

		foreach ( $page_ids as $original_id ) {
			$page_id = self::resolve_translated_id( $original_id );
			$page    = get_post( $page_id );

			if ( ! $page instanceof \WP_Post || 'publish' !== $page->post_status ) {
				continue;
			}

			$base_id  = (int) get_post_thumbnail_id( $page_id );
			$hover_id = (int) get_post_meta( $page_id, self::META_BELIEF_HOVER_IMAGE, true );

			$items[] = array(
				'id'             => $page_id,
				'page_id'        => $page_id,
				'title'          => (string) get_the_title( $page_id ),
				'link'           => (string) get_permalink( $page_id ),
				// Both the URL and the id: the id lets the renderer emit srcset and
				// intrinsic dimensions, the URL stays as the fallback for an image
				// that is no longer in the media library.
				'image_base'     => $base_id ? (string) wp_get_attachment_image_url( $base_id, 'full' ) : '',
				'image_hover'    => $hover_id ? (string) wp_get_attachment_image_url( $hover_id, 'full' ) : '',
				'image_base_id'  => $base_id,
				'image_hover_id' => $hover_id,
				'day_hour'       => '',
				'anchor'         => '',
				'is_current'     => false,
			);
		}

		return $items;
	}

	/**
	 * Resolve a post ID to the Polylang-translated version when available.
	 *
	 * @param int $post_id Original post ID.
	 * @return int
	 */
	private static function resolve_translated_id( int $post_id ): int {
		if ( ! function_exists( 'pll_get_post' ) ) {
			return $post_id;
		}

		/*
		 * The language is named rather than left to Polylang for the same reason as in
		 * `get_meetings()`: asked without one, `pll_get_post()` answers in the language of
		 * the current request, and the block editor's rendering route has none. The belief
		 * tiles would then show Polish pages while the Ukrainian page is open in the editor.
		 */
		$lang       = self::current_language();
		$translated = '' !== $lang ? pll_get_post( $post_id, $lang ) : pll_get_post( $post_id );

		return $translated ? (int) $translated : $post_id;
	}
}
