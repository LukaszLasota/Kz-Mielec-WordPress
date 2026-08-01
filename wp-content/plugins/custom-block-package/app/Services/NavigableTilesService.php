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
	 * Get all meetings as normalized items.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_meetings(): array {
		$query = new \WP_Query(
			array(
				'post_type'      => 'meetings',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

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
			$post_name = get_post_field( 'post_name', $post_id );
			$anchor    = is_string( $post_name ) ? $post_name : '';
			$day_hour  = (string) get_post_meta( $post_id, MeetingMeta::META_DAY_HOUR, true );
			$hover_id  = (int) get_post_meta( $post_id, MeetingMeta::META_HOVER_IMAGE, true );
			$base_id   = (int) get_post_thumbnail_id( $post_id );

			$link = $anchor
				? home_url( '/zaplanuj-wizyte/#' . rawurlencode( $anchor ) )
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

		$translated = pll_get_post( $post_id );
		return $translated ? (int) $translated : $post_id;
	}
}
