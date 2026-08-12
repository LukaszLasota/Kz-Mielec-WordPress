<?php
/**
 * A fingerprint of the content state — for comparing before and after a dress rehearsal.
 *
 * Prints one number or one string per line, in a fixed order, so that two runs can be
 * compared with a plain `diff`. No post ids: after reproducing from production they WILL be
 * different, and that is fine — what is compared is the content, not row numbers.
 *
 * Usage:
 *   ddev wp eval-file scripts/tests/fingerprint.php > before.txt
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$langs = function_exists( 'pll_languages_list' ) ? (array) pll_languages_list() : array( 'pl' );
sort( $langs );

echo "languages: " . implode( ',', $langs ) . "\n";

// ── content counts per language ────────────────────────────────────────────
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

		printf( "count %s/%s: %d\n", $lang, $type, count( $ids ) );
	}
}

// ── taxonomy terms ─────────────────────────────────────────────────────────
foreach ( $langs as $lang ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'comparison_category',
			'hide_empty' => false,
			'lang'       => $lang,
		)
	);

	printf( "terms %s: %d\n", $lang, is_array( $terms ) ? count( $terms ) : 0 );
}

// ── page titles, alphabetically, per language ──────────────────────────────
foreach ( $langs as $lang ) {
	$titles = array();

	foreach ( get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => $lang ) ) as $page ) {
		$titles[] = $page->post_title;
	}

	sort( $titles );

	foreach ( $titles as $title ) {
		printf( "title %s: %s\n", $lang, $title );
	}
}

// ── contact data ───────────────────────────────────────────────────────────
if ( class_exists( '\Kzmielec\Contact\ContactData' ) ) {
	foreach ( \Kzmielec\Contact\ContactData::all() as $key => $value ) {
		printf( "contact %s: %s\n", $key, $value );
	}
}

// ── the contact lines in every language, as a visitor sees them ────────────
if ( class_exists( '\Kzmielec\Contact\ContactBindings' ) ) {
	// Slug -> locale, built pair by pair. Sorting the slug list would break the
	// correspondence with a separately fetched list of locales, so both are read together.
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

			printf( "line %s/%s: %s\n", $lang, $key, preg_replace( '/\s+/', ' ', (string) $line ) );
		}
	}
}

// ── meeting meta fields ────────────────────────────────────────────────────
$places = array();

foreach ( get_posts( array( 'post_type' => 'meetings', 'post_status' => 'publish', 'posts_per_page' => -1, 'suppress_filters' => true ) ) as $meeting ) {
	$lang     = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $meeting->ID ) : 'pl';
	$places[] = $lang . '|' . (string) get_post_meta( $meeting->ID, '_meeting_place', true ) . '|' . (string) get_post_meta( $meeting->ID, '_meeting_day_hour', true );
}

sort( $places );

foreach ( $places as $place ) {
	printf( "meeting %s\n", $place );
}

// ── traces of damage that has happened once already ────────────────────────
global $wpdb;

foreach ( array(
	'swallowed backslash uXXXX' => 'u01',
	'dead ddev e-mail'      => 'zbor@kzmielec.ddev.site',
	'Cyrillic street name'        => 'Промислова',
) as $label => $needle ) {
	$count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_status = 'publish'",
			'%' . $wpdb->esc_like( $needle ) . '%'
		)
	);

	printf( "damage %s: %d\n", $label, $count );
}
