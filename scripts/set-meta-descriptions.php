<?php
/**
 * Writes the hand-written meta descriptions into Yoast, in all four languages.
 *
 * Run with:
 *   ddev wp eval-file scripts/set-meta-descriptions.php --path=/var/www/html          (dry run)
 *   ddev wp eval-file scripts/set-meta-descriptions.php --path=/var/www/html -- go    (writes)
 *
 * The texts live in `scripts/data/meta-descriptions.php`; this only places them and
 * checks them. Yoast keeps a page's description in the `_yoast_wpseo_metadesc` post
 * meta and prefers it over any template or theme fallback, so writing that one key
 * per post is the whole job.
 *
 * The length check is not decoration. Under 120 characters wastes space Google would
 * have shown; over 158 is cut mid-word on desktop. A description that trips the
 * check is reported and NOT written — a bad description that looks deliberate is
 * worse than the fallback, because nobody will look at it again.
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs this through eval(), where a
// declare would have to be the first statement of the script and so cannot appear.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$kz_go = in_array( 'go', (array) $args, true );

$kz_min = 120;
$kz_max = 158;
$kz_key = '_yoast_wpseo_metadesc';

/*
 * Resolved against the WordPress root rather than `__DIR__`. Inside `wp eval-file`
 * this file is evaluated, not included, so `__DIR__` points at the WP-CLI phar and
 * happens to work only because the require falls back to the include path — not
 * something to rely on when the same script runs on the server.
 */
$kz_data_file = '';

/*
 * Three places, because the project root is the WordPress root locally but the two
 * are different on hosts that keep WordPress in a subdirectory, and the working
 * directory is whatever the operator happened to be in.
 */
foreach (
	array(
		ABSPATH . 'scripts/data/meta-descriptions.php',
		dirname( untrailingslashit( ABSPATH ) ) . '/scripts/data/meta-descriptions.php',
		getcwd() . '/scripts/data/meta-descriptions.php',
	) as $kz_candidate
) {
	if ( file_exists( $kz_candidate ) ) {
		$kz_data_file = $kz_candidate;
		break;
	}
}

if ( '' === $kz_data_file ) {
	WP_CLI::error( 'Nie znalazlem scripts/data/meta-descriptions.php — uruchom z katalogu projektu.' );
}

$kz_data = require $kz_data_file;

$kz_written = 0;
$kz_skipped = 0;
$kz_problem = array();
$kz_seen    = array();

foreach ( $kz_data as $kz_src => $kz_langs ) {
	foreach ( $kz_langs as $kz_lang => $kz_text ) {
		$kz_id = ( 'pl' === $kz_lang ) ? (int) $kz_src : (int) pll_get_post( (int) $kz_src, $kz_lang );

		if ( ! $kz_id ) {
			$kz_problem[] = sprintf( '#%d %s — brak wpisu w tym jezyku', $kz_src, $kz_lang );
			continue;
		}

		$kz_len = mb_strlen( $kz_text );

		if ( $kz_len < $kz_min || $kz_len > $kz_max ) {
			$kz_problem[] = sprintf(
				'#%d %s — %d znakow (poza %d-%d): %s',
				$kz_id,
				$kz_lang,
				$kz_len,
				$kz_min,
				$kz_max,
				mb_substr( $kz_text, 0, 50 )
			);
			++$kz_skipped;
			continue;
		}

		// Two pages sharing a description is the defect this replaces, so it is checked.
		$kz_fingerprint = $kz_lang . '|' . $kz_text;

		if ( isset( $kz_seen[ $kz_fingerprint ] ) ) {
			$kz_problem[] = sprintf( '#%d %s — duplikat opisu z #%d', $kz_id, $kz_lang, $kz_seen[ $kz_fingerprint ] );
			++$kz_skipped;
			continue;
		}

		$kz_seen[ $kz_fingerprint ] = $kz_id;

		if ( $kz_go ) {
			update_post_meta( $kz_id, $kz_key, $kz_text );
		}

		/*
		 * The static front page is a special case in Yoast: it takes its description
		 * from `wpseo_titles['metadesc-home-wpseo']` in Search Appearance and ignores
		 * the page's own field. Measured — writing only the post meta left the Polish
		 * home page still showing the theme's content scrape, while the three
		 * translated front pages, which Yoast treats as ordinary pages, picked their
		 * new descriptions up at once.
		 *
		 * The value found there was a comma-separated keyword list, not a description,
		 * and it carried a typo in the church's own name ("Zielonoswiątkowy").
		 */
		if ( (int) get_option( 'page_on_front' ) === $kz_id ) {
			if ( $kz_go ) {
				$kz_titles = get_option( 'wpseo_titles' );

				if ( is_array( $kz_titles ) ) {
					$kz_titles['metadesc-home-wpseo'] = $kz_text;
					update_option( 'wpseo_titles', $kz_titles );
				}
			}

			WP_CLI::log( sprintf( '   #%d to strona glowna — ustawiam takze metadesc-home-wpseo', $kz_id ) );
		}

		++$kz_written;
	}
}

/*
 * Yoast does not read the post meta on every request; it serves SEO output from its
 * own `wp_yoast_indexable` table, and writing the meta does not always refresh the
 * row. Measured: after writing all 84 descriptions, the three translated front pages
 * showed their new text immediately while the POLISH front page kept the theme's
 * content scrape — its indexable row still carried an empty description. Deleting
 * the affected rows makes Yoast rebuild them on the next request, which is cheaper
 * and more predictable than a full `wp yoast index`.
 */
if ( $kz_go ) {
	global $wpdb;

	$kz_table = $wpdb->prefix . 'yoast_indexable';

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $kz_table ) ) === $kz_table ) {
		$kz_ids = array_values( array_unique( array_map( 'intval', array_values( $kz_seen ) ) ) );

		if ( $kz_ids ) {
			$kz_in = implode( ',', $kz_ids );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- ids are cast to int above.
			$kz_removed = $wpdb->query(
				"DELETE FROM {$kz_table} WHERE object_type = 'home-page' OR ( object_type = 'post' AND object_id IN ({$kz_in}) )"
			);
			// phpcs:enable

			WP_CLI::log( sprintf( 'uniewaznionych wierszy w tabeli Yoasta: %d', (int) $kz_removed ) );
		}
	}
}

WP_CLI::log( sprintf( 'opisow gotowych do zapisu: %d, pominietych: %d', $kz_written, $kz_skipped ) );

if ( $kz_problem ) {
	WP_CLI::warning( sprintf( 'DO POPRAWY: %d', count( $kz_problem ) ) );
	foreach ( $kz_problem as $kz_p ) {
		WP_CLI::log( '   ' . $kz_p );
	}
} else {
	WP_CLI::log( 'wszystkie opisy w normie dlugosci i bez duplikatow' );
}

if ( $kz_go ) {
	WP_CLI::success( 'Zapisane.' );
} else {
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
}
