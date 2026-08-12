<?php
/**
 * Brings the meetings' "place" field into line with the one address the site now has.
 *
 * Run with:
 *   ddev wp eval-file scripts/fix-meeting-place.php          (dry run)
 *   ddev wp eval-file scripts/fix-meeting-place.php -- go    (writes)
 *
 * The `_meeting_place` field is deliberately NOT bound to the contact data: a meeting has
 * every right to happen somewhere else, and taking the field away would remove that. What
 * it is not allowed to do is spell the congregation's own address in a form that does not
 * exist. Three Ukrainian meetings carried «вул. Промислова, 2, 39-300 Мілець» — the street
 * name translated into Ukrainian rather than kept, the town transliterated from the wrong
 * stem («Мілець» against «Мелець» used everywhere else), and nothing in it findable on a
 * street sign, in the postal system or in a map service. The same repair was applied to
 * page content on 2026-08-11; this field was missed because `fix-proper-names.php` only
 * looked at post content and the comparison columns.
 *
 * English carried "2 Przemysłowa Street" and Spanish "Calle Przemysłowa, 2" — both real
 * conventions of their own languages, and both wrong for a Polish postal address.
 *
 * The replacement is not written out here. It is read from
 * `ContactBindings::line( 'address' )` in the language of the meeting, which is the very
 * string the contact section shows, so the two cannot drift apart. Only values that are a
 * KNOWN variant of the congregation's address are touched; anything else is left exactly
 * as the editor typed it.
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs this through eval(), where a declare
// would have to be the first statement of the script and so cannot appear.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$kz_go = in_array( 'go', (array) $args, true );

if ( ! class_exists( '\Kzmielec\Contact\ContactBindings' ) ) {
	WP_CLI::error( 'Brak klasy ContactBindings — najpierw wdroz kod motywu.' );
}

/**
 * Values recognised as the congregation's own address, in every form found in the field.
 *
 * Matching a known list rather than a pattern is deliberate: a pattern loose enough to
 * catch «Промислова» would also catch a genuinely different place on the same street.
 *
 * @var array<int, string>
 */
$kz_known = array(
	'ul. Przemysłowa 2, 39-300 Mielec',
	'ul. Przemysłowa 2',
	'2 Przemysłowa Street, 39-300 Mielec',
	'Calle Przemysłowa, 2, 39-300 Mielec',
	'calle Przemysłowa, 2, 39-300 Mielec',
	'вул. Промислова, 2, 39-300 Мілець',
	'вул. Промислова, 2, 39-300 Мелець',
	'вул. Przemysłowa 2, 39-300 Mielec',
);

$kz_meetings = get_posts(
	array(
		'post_type'        => 'meetings',
		'post_status'      => array( 'publish', 'draft' ),
		'posts_per_page'   => -1,
		'fields'           => 'ids',
		'suppress_filters' => true,
	)
);

$kz_changed = 0;
$kz_left    = 0;

foreach ( $kz_meetings as $kz_id ) {
	$kz_place = (string) get_post_meta( $kz_id, '_meeting_place', true );

	if ( '' === trim( $kz_place ) ) {
		continue;
	}

	$kz_lang = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $kz_id, 'locale' ) : '';
	$kz_lang = is_string( $kz_lang ) ? $kz_lang : '';

	$kz_want = (string) \Kzmielec\Contact\ContactBindings::with_locale(
		$kz_lang,
		static function (): string {
			return (string) \Kzmielec\Contact\ContactBindings::line( 'address' );
		}
	);

	if ( ! in_array( $kz_place, $kz_known, true ) ) {
		WP_CLI::log( sprintf( '  #%-5d POMIJAM (nie jest adresem zboru): %s', $kz_id, $kz_place ) );
		++$kz_left;
		continue;
	}

	if ( $kz_place === $kz_want ) {
		continue;
	}

	++$kz_changed;

	WP_CLI::log( sprintf( '  #%-5d %-6s %s', $kz_id, $kz_lang, $kz_place ) );
	WP_CLI::log( sprintf( '  %-6s %-6s -> %s', '', '', $kz_want ) );

	if ( $kz_go ) {
		update_post_meta( $kz_id, '_meeting_place', $kz_want );
	}
}

WP_CLI::log( sprintf( 'do zmiany: %d, zostawionych bez zmian: %d, spotkan razem: %d', $kz_changed, $kz_left, count( $kz_meetings ) ) );

if ( $kz_go ) {
	wp_cache_flush();
	WP_CLI::success( 'Zapisane.' );
} else {
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
}
