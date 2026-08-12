<?php
/**
 * Odcisk stanu tresci — do porownania przed i po probie generalnej.
 *
 * Wypisuje jedna liczbe albo jeden napis na wiersz, w stalej kolejnosci, zeby dwa
 * przebiegi dawaly sie porownac zwyklym `diff`. Zadnych identyfikatorow, bo te po
 * odtworzeniu z produkcji BEDA inne i to jest w porzadku — porownujemy tresc, nie
 * numery wierszy w bazie.
 *
 * Uzycie:
 *   ddev wp eval-file scripts/tests/fingerprint.php > przed.txt
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$langs = function_exists( 'pll_languages_list' ) ? (array) pll_languages_list() : array( 'pl' );
sort( $langs );

echo "jezyki: " . implode( ',', $langs ) . "\n";

// ── liczby tresci na jezyk ─────────────────────────────────────────────────
foreach ( $langs as $lang ) {
	foreach ( array( 'page', 'post', 'meetings', 'comparison_topic' ) as $type ) {
		$ids = get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'lang'           => $lang,
			)
		);

		printf( "liczba %s/%s: %d\n", $lang, $type, count( $ids ) );
	}
}

// ── terminy taksonomii ────────────────────────────────────────────────────
foreach ( $langs as $lang ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'comparison_category',
			'hide_empty' => false,
			'lang'       => $lang,
		)
	);

	printf( "terminy %s: %d\n", $lang, is_array( $terms ) ? count( $terms ) : 0 );
}

// ── tytuly stron, alfabetycznie, per jezyk ────────────────────────────────
foreach ( $langs as $lang ) {
	$titles = array();

	foreach ( get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => $lang ) ) as $page ) {
		$titles[] = $page->post_title;
	}

	sort( $titles );

	foreach ( $titles as $title ) {
		printf( "tytul %s: %s\n", $lang, $title );
	}
}

// ── dane kontaktowe ───────────────────────────────────────────────────────
if ( class_exists( '\Kzmielec\Contact\ContactData' ) ) {
	foreach ( \Kzmielec\Contact\ContactData::all() as $key => $value ) {
		printf( "kontakt %s: %s\n", $key, $value );
	}
}

// ── linie kontaktowe w kazdym jezyku, tak jak je widzi odwiedzajacy ───────
if ( class_exists( '\Kzmielec\Contact\ContactBindings' ) ) {
	// Slug -> locale, zbudowane para po parze. Sortowanie listy slugow zerwaloby
	// odpowiednioscz osobno pobrana lista locale, wiec bierzemy je razem.
	$locales = array();

	if ( function_exists( 'pll_languages_list' ) ) {
		$slugs      = (array) pll_languages_list( array( 'fields' => 'slug' ) );
		$loc_values = (array) pll_languages_list( array( 'fields' => 'locale' ) );

		foreach ( $slugs as $index => $slug ) {
			$locales[ (string) $slug ] = (string) ( $loc_values[ $index ] ?? '' );
		}
	}

	foreach ( $langs as $lang ) {
		foreach ( \Kzmielec\Contact\ContactBindings::KEYS as $key ) {
			$line = \Kzmielec\Contact\ContactBindings::with_locale(
				$locales[ $lang ] ?? '',
				static function () use ( $key ) {
					return wp_strip_all_tags( (string) \Kzmielec\Contact\ContactBindings::line( $key ) );
				}
			);

			printf( "linia %s/%s: %s\n", $lang, $key, preg_replace( '/\s+/', ' ', (string) $line ) );
		}
	}
}

// ── pola meta spotkan ─────────────────────────────────────────────────────
$places = array();

foreach ( get_posts( array( 'post_type' => 'meetings', 'post_status' => 'publish', 'posts_per_page' => -1, 'suppress_filters' => true ) ) as $meeting ) {
	$lang     = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $meeting->ID ) : 'pl';
	$places[] = $lang . '|' . (string) get_post_meta( $meeting->ID, '_meeting_place', true ) . '|' . (string) get_post_meta( $meeting->ID, '_meeting_day_hour', true );
}

sort( $places );

foreach ( $places as $place ) {
	printf( "spotkanie %s\n", $place );
}

// ── slady uszkodzen, ktore juz raz wystapily ──────────────────────────────
global $wpdb;

foreach ( array(
	'zjedzony ukosnik uXXXX' => 'u01',
	'martwy email ddev'      => 'zbor@kzmielec.ddev.site',
	'cyrylicka ulica'        => 'Промислова',
) as $label => $needle ) {
	$count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_status = 'publish'",
			'%' . $wpdb->esc_like( $needle ) . '%'
		)
	);

	printf( "uszkodzenie %s: %d\n", $label, $count );
}
