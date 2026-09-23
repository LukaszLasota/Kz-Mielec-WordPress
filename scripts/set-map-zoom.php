<?php
/**
 * Sets the zoom of the map blocks on the four front pages.
 *
 * Dry run by default; writes only with `go`:
 *
 *   ddev wp eval-file - < scripts/set-map-zoom.php
 *   ddev wp eval-file - go < scripts/set-map-zoom.php
 *
 * Zoom is a per-instance design choice, stored in each language's copy of the
 * front page, so the four copies are changed together. Only the zoom attribute
 * is touched; the rest of the content is re-serialised by core, and a page whose
 * content would change in any other way is skipped.
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs the file through eval(),
// where a declare cannot appear.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

const KZ_MAP_ZOOM = 17;

$kz_go = in_array( 'go', $args, true );

global $wpdb;
$kz_ids = $wpdb->get_col(
	"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = 'page' AND post_content LIKE '%<!-- wp:custom-block-package/map-block%'"
);

/**
 * Set zoom on every map block in a block tree.
 *
 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
 * @param int                              $count  Blocks changed, by reference.
 * @return array<int, array<string, mixed>>
 */
function kz_set_zoom( array $blocks, int &$count ): array {
	foreach ( $blocks as $i => $block ) {
		if ( 'custom-block-package/map-block' === $block['blockName'] ) {
			$old = $block['attrs']['zoom'] ?? 16;
			if ( KZ_MAP_ZOOM !== $old ) {
				$blocks[ $i ]['attrs']['zoom'] = KZ_MAP_ZOOM;
				++$count;
			}
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$blocks[ $i ]['innerBlocks'] = kz_set_zoom( $block['innerBlocks'], $count );
		}
	}
	return $blocks;
}

foreach ( $kz_ids as $kz_id ) {
	$kz_content = (string) get_post_field( 'post_content', (int) $kz_id );
	$kz_count   = 0;
	$kz_new     = serialize_blocks( kz_set_zoom( parse_blocks( $kz_content ), $kz_count ) );

	if ( 0 === $kz_count ) {
		WP_CLI::log( sprintf( '%d: already %d', $kz_id, KZ_MAP_ZOOM ) );
		continue;
	}

	// Guard: the only difference must be inside the map block comment.
	$kz_strip = static function ( string $html ): string {
		return (string) preg_replace( '#<!-- wp:custom-block-package/map-block \{.*?\} /-->#s', '', $html );
	};
	if ( $kz_strip( $kz_content ) !== $kz_strip( $kz_new ) ) {
		WP_CLI::warning( sprintf( '%d: re-serialising would change more than the map, skipped', $kz_id ) );
		continue;
	}

	WP_CLI::log( sprintf( '%d %s: %d map(s) -> zoom %d', $kz_id, get_the_title( (int) $kz_id ), $kz_count, KZ_MAP_ZOOM ) );

	if ( $kz_go ) {
		$wpdb->update( $wpdb->posts, array( 'post_content' => $kz_new ), array( 'ID' => (int) $kz_id ) );
		clean_post_cache( (int) $kz_id );
	}
}

if ( ! $kz_go ) {
	WP_CLI::success( 'Dry run, nothing written. Add `go` to write.' );
	return;
}

do_action( 'litespeed_purge_all' );
WP_CLI::success( 'Map zoom set.' );
