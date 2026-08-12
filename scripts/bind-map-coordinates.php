<?php
/**
 * Lets the map blocks follow the congregation's contact settings.
 *
 * Run with:
 *   ddev wp eval-file scripts/bind-map-coordinates.php          (dry run)
 *   ddev wp eval-file scripts/bind-map-coordinates.php -- go    (writes)
 *
 * The four language versions of the front page each stored the same coordinates —
 * 50.299071, 21.4483254 — four copies of one fact, with nothing keeping them in step. The
 * map block now reads the pair from the `kzmielec_contact` option whenever the instance is
 * still on the placeholder pair that `block.json` ships. This script removes the stored
 * coordinates from those instances so they do exactly that.
 *
 * Only instances holding the congregation's own coordinates are touched. A map deliberately
 * pointed somewhere else keeps its own pair and goes on ignoring the settings, which is the
 * point of leaving that path open.
 *
 * Zoom, height, tile style and the popup text are left alone: they are per-instance design
 * choices, and the popup text is prose that differs by language.
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs this through eval(), where a declare
// would have to be the first statement of the script and so cannot appear.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$kz_go = in_array( 'go', (array) $args, true );

/** Coordinates recognised as the congregation's own. */
$kz_lat = '50.299071';
$kz_lng = '21.4483254';

global $wpdb;

$kz_ids = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('page','post') AND post_status IN ('publish','draft') AND post_content LIKE %s ORDER BY ID",
		'%' . $wpdb->esc_like( 'custom-block-package/map-block' ) . '%'
	)
);

WP_CLI::log( sprintf( 'wpisow z blokiem mapy: %d', count( $kz_ids ) ) );

$kz_changed = 0;
$kz_left    = 0;

foreach ( $kz_ids as $kz_id ) {
	$kz_id      = (int) $kz_id;
	$kz_content = (string) get_post_field( 'post_content', $kz_id );
	$kz_new     = $kz_content;
	$kz_hits    = 0;
	$kz_other   = 0;

	/*
	 * Each map block is one comment with a JSON payload. The payload is decoded and
	 * re-encoded rather than edited with a pattern, so a value that happens to look like
	 * another cannot be caught by accident.
	 */
	// The block is void, so its comment ends in `/-->`; the slash is optional here only
	// because a future non-void instance should not silently escape the repair.
	if ( preg_match_all( '#<!-- wp:custom-block-package/map-block (\{.*?\}) (/?)-->#s', $kz_content, $kz_matches, PREG_SET_ORDER ) ) {
		foreach ( $kz_matches as $kz_match ) {
			$kz_attrs = json_decode( $kz_match[1], true );

			if ( ! is_array( $kz_attrs ) ) {
				WP_CLI::warning( sprintf( '  #%d: nie udalo sie odczytac atrybutow bloku, pomijam', $kz_id ) );
				continue;
			}

			$kz_has_lat = isset( $kz_attrs['latitude'] ) && abs( (float) $kz_attrs['latitude'] - (float) $kz_lat ) < 0.000001;
			$kz_has_lng = isset( $kz_attrs['longitude'] ) && abs( (float) $kz_attrs['longitude'] - (float) $kz_lng ) < 0.000001;

			if ( ! $kz_has_lat || ! $kz_has_lng ) {
				++$kz_other;
				continue;
			}

			unset( $kz_attrs['latitude'], $kz_attrs['longitude'] );

			/*
			 * `JSON_UNESCAPED_UNICODE` is not cosmetic. Without it `wp_json_encode()`
			 * writes «ś» as `ś`, and `wp_update_post()` — which expects slashed
			 * input and unslashes what it is given — eats the backslash, leaving the
			 * literal text `u015b` in the page. The first run of this script did exactly
			 * that to the Polish and Ukrainian map captions. Gutenberg itself stores
			 * these attributes unescaped, so this also keeps the markup byte-identical
			 * to what the editor would write.
			 */
			$kz_new = str_replace(
				$kz_match[0],
				'<!-- wp:custom-block-package/map-block ' . wp_json_encode( $kz_attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ' ' . $kz_match[2] . '-->',
				$kz_new
			);

			++$kz_hits;
		}
	}

	if ( $kz_other > 0 ) {
		WP_CLI::log( sprintf( '  #%-5d blokow pod innym adresem, zostawionych bez zmian: %d', $kz_id, $kz_other ) );
		$kz_left += $kz_other;
	}

	if ( 0 === $kz_hits ) {
		continue;
	}

	++$kz_changed;

	$kz_lang = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $kz_id ) : '';

	WP_CLI::log( sprintf( '  #%-5d %-4s wspolrzednych zdjetych z blokow: %d', $kz_id, $kz_lang, $kz_hits ) );

	if ( $kz_go ) {
		// Slashed on the way in, because `wp_update_post()` unslashes. Without this any
		// backslash in the content is silently swallowed.
		wp_update_post(
			array(
				'ID'           => $kz_id,
				'post_content' => wp_slash( $kz_new ),
			)
		);
	}
}

WP_CLI::log( sprintf( 'wpisow do zmiany: %d, blokow zostawionych: %d', $kz_changed, $kz_left ) );

if ( $kz_go ) {
	wp_cache_flush();
	WP_CLI::success( 'Zapisane. Mapy biora wspolrzedne z ustawien danych kontaktowych.' );
} else {
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
}
