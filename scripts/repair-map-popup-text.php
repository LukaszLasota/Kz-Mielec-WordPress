<?php
/**
 * Puts the map captions back after they were mangled into `u015b`-style text.
 *
 * Run with:
 *   ddev wp eval-file scripts/repair-map-popup-text.php          (dry run)
 *   ddev wp eval-file scripts/repair-map-popup-text.php -- go    (writes)
 *
 * `bind-map-coordinates.php` re-encoded the map block's attributes with
 * `wp_json_encode()`, which writes «ś» as the escape `ś`. `wp_update_post()` expects
 * slashed input and unslashes whatever it is handed, so the backslash disappeared and the
 * page kept the literal characters `u015b`. The Polish caption became
 * "Kou015bciu00f3u0142 Zielonou015bwiu0105tkowy Zbu00f3r w Mielcu" and the Ukrainian one
 * lost every letter it had. English and Spanish were untouched, having no character
 * outside ASCII to escape.
 *
 * The caption is not retyped here. It is read back from the newest revision that predates
 * the damage — one whose map block carries no such escape — so the text is the editor's
 * own, not a guess of mine.
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs this through eval(), where a declare
// would have to be the first statement of the script and so cannot appear.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$kz_go = in_array( 'go', (array) $args, true );

/**
 * Pull the map block's attributes out of a piece of content.
 *
 * @param string $content Post content.
 * @return array<string, mixed>|null
 */
$kz_attrs_of = static function ( $content ) {
	if ( ! preg_match( '#<!-- wp:custom-block-package/map-block (\{.*?\}) (/?)-->#s', (string) $content, $m ) ) {
		return null;
	}

	$decoded = json_decode( $m[1], true );

	return is_array( $decoded ) ? $decoded : null;
};

/** Text carrying a swallowed escape, e.g. `u015b` where `ś` belongs. */
$kz_is_mangled = static function ( $text ) {
	return 1 === preg_match( '/u[0-9a-f]{4}/i', (string) $text );
};

global $wpdb;

$kz_ids = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('page','post') AND post_status IN ('publish','draft') AND post_content LIKE %s ORDER BY ID",
		'%' . $wpdb->esc_like( 'custom-block-package/map-block' ) . '%'
	)
);

$kz_fixed   = 0;
$kz_healthy = 0;

foreach ( $kz_ids as $kz_id ) {
	$kz_id      = (int) $kz_id;
	$kz_content = (string) get_post_field( 'post_content', $kz_id );
	$kz_attrs   = $kz_attrs_of( $kz_content );

	if ( null === $kz_attrs ) {
		WP_CLI::warning( sprintf( '  #%d: nie odczytalem atrybutow bloku mapy, pomijam', $kz_id ) );
		continue;
	}

	$kz_caption = isset( $kz_attrs['popupText'] ) ? (string) $kz_attrs['popupText'] : '';

	if ( ! $kz_is_mangled( $kz_caption ) ) {
		++$kz_healthy;
		continue;
	}

	// Newest revision whose caption is still whole.
	$kz_good = '';

	foreach ( wp_get_post_revisions( $kz_id, array( 'posts_per_page' => 30 ) ) as $kz_rev ) {
		$kz_rev_attrs = $kz_attrs_of( $kz_rev->post_content );

		if ( null === $kz_rev_attrs || ! isset( $kz_rev_attrs['popupText'] ) ) {
			continue;
		}

		$kz_candidate = (string) $kz_rev_attrs['popupText'];

		if ( '' !== $kz_candidate && ! $kz_is_mangled( $kz_candidate ) ) {
			$kz_good = $kz_candidate;
			break;
		}
	}

	if ( '' === $kz_good ) {
		WP_CLI::warning( sprintf( '  #%d: nie znalazlem zdrowej rewizji, ZOSTAWIAM bez zmian', $kz_id ) );
		continue;
	}

	$kz_attrs['popupText'] = $kz_good;

	$kz_new = preg_replace(
		'#<!-- wp:custom-block-package/map-block \{.*?\} (/?)-->#s',
		'<!-- wp:custom-block-package/map-block ' . str_replace( '$', '\\$', wp_json_encode( $kz_attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) . ' $1-->',
		$kz_content,
		1
	);

	if ( ! is_string( $kz_new ) || $kz_new === $kz_content ) {
		WP_CLI::warning( sprintf( '  #%d: podmiana nie doszla do skutku, pomijam', $kz_id ) );
		continue;
	}

	++$kz_fixed;

	$kz_lang = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $kz_id ) : '';

	WP_CLI::log( sprintf( '  #%-5d %-4s bylo: %s', $kz_id, $kz_lang, mb_substr( $kz_caption, 0, 54 ) ) );
	WP_CLI::log( sprintf( '  %-5s %-4s ma byc: %s', '', '', mb_substr( $kz_good, 0, 54 ) ) );

	if ( $kz_go ) {
		wp_update_post(
			array(
				'ID'           => $kz_id,
				'post_content' => wp_slash( $kz_new ),
			)
		);
	}
}

WP_CLI::log( sprintf( 'do naprawy: %d, zdrowych: %d, wpisow z mapa: %d', $kz_fixed, $kz_healthy, count( $kz_ids ) ) );

if ( $kz_go ) {
	wp_cache_flush();
	WP_CLI::success( 'Zapisane.' );
} else {
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
}
