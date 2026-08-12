<?php
/**
 * Corrects the hand-written Yoast SEO titles.
 *
 * Run with:
 *   ddev wp eval-file scripts/fix-seo-titles.php --path=/var/www/html          (dry run)
 *   ddev wp eval-file scripts/fix-seo-titles.php --path=/var/www/html -- go    (writes)
 *
 * Four pages carry a hand-written SEO title (`_yoast_wpseo_title`) rather than
 * relying on the template, in each of the four languages. Reviewing them turned up
 * three kinds of defect. These strings are what a searcher reads in the results
 * list, so they are worth the same care as the page itself.
 *
 * 1. UKRAINIAN NAMED THE DENOMINATION THREE DIFFERENT WAYS on one site:
 *    «Пентекостальна церква» in the site title and the language switcher,
 *    «Церква Християн Віри Євангельської» in the comparison table, and
 *    «Церква П'ятидесятників» in this SEO title. All three are defensible
 *    renderings; using all three is not. They are unified on the first, which is
 *    the name the site gives itself.
 *
 * 2. ENGLISH SAID "Catholic churches", plural, as though comparing Pentecostal
 *    teaching with several Catholic bodies. The page compares it with one.
 *
 * 3. THE POLISH TITLE MISSPELLED THE CHURCH'S OWN NAME — "Koscioła" without the ś.
 *    Invisible in the page body, because this string only ever appears in the
 *    browser tab and in search results, which is exactly why nobody caught it.
 *
 * The `%%title%%`, `%%page%%`, `%%sep%%` and `%%sitename%%` placeholders are
 * Yoast's and are preserved verbatim.
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs this through eval(), where a
// declare would have to be the first statement of the script and so cannot appear.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$kz_go = in_array( 'go', (array) $args, true );

/**
 * Post id => [ exact current title, corrected title ].
 *
 * Keyed by the id in each language rather than by the Polish source, because a
 * hand-written SEO title is not a translation of another one — each was written for
 * its own language.
 */
$kz_titles = array(

	/*
	 * prawo (pl) — two defects in one string. "Koscioła" was missing its ś, and the
	 * trailing `%%title%%` placeholder appends the PAGE title to a sentence that
	 * already names the subject, so the tab and the search result both read
	 * "Dokumenty prawne Kościoła Zielonoświątkowego prawo". The same placeholder sat
	 * on all four language versions of this page: "… Pentecostal Church law",
	 * "… Пентекостальної церкви право", "… Iglesia Pentecostal derecho". Replaced with
	 * the separator and site name, which is what the comparison pages already use.
	 */
	88  => array(
		'Dokumenty prawne Koscioła Zielonoświątkowego %%title%%',
		'Dokumenty prawne Kościoła Zielonoświątkowego %%sep%% %%sitename%%',
	),

	// law (en).
	488 => array(
		'Legal documents of the Pentecostal Church %%title%%',
		'Legal documents of the Pentecostal Church %%sep%% %%sitename%%',
	),

	// derecho (es).
	686 => array(
		'Documentos jurídicos de la Iglesia Pentecostal %%title%%',
		'Documentos jurídicos de la Iglesia Pentecostal %%sep%% %%sitename%%',
	),

	// differences in religious beliefs (en) — one Catholic Church, not several.
	489 => array(
		'A comparison of the Pentecostal Church and Catholic churches %%page%% %%sep%% %%sitename%%',
		'A comparison of the Pentecostal Church and the Roman Catholic Church %%page%% %%sep%% %%sitename%%',
	),

	// релігійна різниця (uk) — unified denomination name, and the Catholic
	// church's name capitalised as a proper name, as it is in the table.
	629 => array(
		'Порівняння Церкви Християн Віри Євангельської та католицької церкви %%page%% %%sep%% %%sitename%%',
		'Порівняння Пентекостальної церкви та Римо-Католицької Церкви %%page%% %%sep%% %%sitename%%',
	),

	// право (uk) — third variant of the denomination name, unified.
	628 => array(
		'Юридичні документи Церкви П&#x27;ятидесятників %%title%%',
		'Юридичні документи Пентекостальної церкви %%sep%% %%sitename%%',
	),
);

$kz_done    = 0;
$kz_problem = array();

foreach ( $kz_titles as $kz_id => $kz_pair ) {
	list( $kz_old, $kz_new ) = $kz_pair;

	$kz_current = (string) get_post_meta( $kz_id, '_yoast_wpseo_title', true );

	if ( $kz_current === $kz_new ) {
		WP_CLI::log( sprintf( '  #%-4d juz poprawiony', $kz_id ) );
		continue;
	}

	if ( $kz_current !== $kz_old ) {
		$kz_problem[] = sprintf( '#%d — tytul nie zgadza sie z oczekiwanym:%s     jest: %s', $kz_id, PHP_EOL, $kz_current );
		continue;
	}

	WP_CLI::log( sprintf( "  #%-4d\n     - %s\n     + %s", $kz_id, $kz_old, $kz_new ) );

	if ( $kz_go ) {
		update_post_meta( $kz_id, '_yoast_wpseo_title', $kz_new );
	}

	++$kz_done;
}

if ( $kz_go && $kz_done ) {
	/*
	 * Yoast serves titles from its own indexable table, which a post meta write does
	 * not always refresh — the same trap as with the descriptions. Deleting the rows
	 * makes it rebuild them on the next request.
	 */
	global $wpdb;

	$kz_table = $wpdb->prefix . 'yoast_indexable';

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $kz_table ) ) === $kz_table ) {
		$kz_in = implode( ',', array_map( 'intval', array_keys( $kz_titles ) ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- ids cast to int.
		$wpdb->query( "DELETE FROM {$kz_table} WHERE object_type = 'post' AND object_id IN ({$kz_in})" );
	}
}

WP_CLI::log( sprintf( 'tytulow poprawionych: %d', $kz_done ) );

if ( $kz_problem ) {
	WP_CLI::warning( sprintf( 'NIEZGODNOSCI: %d', count( $kz_problem ) ) );
	foreach ( $kz_problem as $kz_p ) {
		WP_CLI::log( '   ' . $kz_p );
	}
}

if ( $kz_go ) {
	WP_CLI::success( 'Zapisane.' );
} else {
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
}
