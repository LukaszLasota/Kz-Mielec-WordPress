<?php
/**
 * Points the contact paragraphs at the one source instead of copying the data.
 *
 * Run with:
 *   ddev wp eval-file scripts/bind-contact-data.php          (dry run)
 *   ddev wp eval-file scripts/bind-contact-data.php -- go    (writes)
 *
 * Before this, the address, phone number, tax number, e-mail and bank account existed in
 * four independent copies — one per language version of the front page — plus a fifth
 * hardcoded in the theme's structured data. They had already drifted: every visible copy
 * of the e-mail read `zbor@kzmielec.ddev.site`, a dead address left behind by the domain
 * search-replace that follows a database copy from production, while only the
 * machine-readable copy was right. Nobody noticed, because a wrong e-mail address looks
 * exactly like a right one.
 *
 * The five paragraphs between the `#four` heading and the map block are regenerated
 * wholesale rather than matched line by line. Matching with regular expressions damaged
 * Scripture quotations and legal gazette item numbers earlier in this same body of work;
 * regenerating a known run of blocks is deterministic and cannot half-apply.
 *
 * The fallback text inside each paragraph is written in the page's own language, so a page
 * rendered without the binding source shows Ukrainian lines on the Ukrainian page rather
 * than Polish ones.
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

/*
 * The front pages are DERIVED, never listed.
 *
 * They used to be written out as 131/478/618/676, which is how this script was built and
 * how it would have failed on production. Reproducing the site from a fresh copy of the
 * production database gives the translations new ids — the same four pages came back as
 * 131/404/462/520 — so three of the four were skipped and one Polish page was rewritten.
 * Nothing was damaged only because a stray id happened to land on a post with no `#four`
 * heading; had it landed on one, this script would have rewritten the wrong page.
 *
 * `page_on_front` is the one fact that survives reproduction, and Polylang answers for the
 * rest.
 */
$kz_front = (int) get_option( 'page_on_front' );

if ( $kz_front <= 0 ) {
	WP_CLI::error( 'Brak strony glownej (page_on_front) — nie wiem, ktore strony przepisac.' );
}

$kz_pages = array();
$kz_lang  = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $kz_front ) : 'pl';
$kz_pages[ '' !== $kz_lang ? $kz_lang : 'pl' ] = $kz_front;

if ( function_exists( 'pll_languages_list' ) && function_exists( 'pll_get_post' ) ) {
	foreach ( (array) pll_languages_list() as $kz_slug ) {
		$kz_slug = (string) $kz_slug;

		if ( isset( $kz_pages[ $kz_slug ] ) ) {
			continue;
		}

		$kz_translated = pll_get_post( $kz_front, $kz_slug );

		if ( $kz_translated ) {
			$kz_pages[ $kz_slug ] = (int) $kz_translated;
		} else {
			WP_CLI::warning( sprintf( 'brak tlumaczenia strony glownej na %s — pomijam ten jezyk', $kz_slug ) );
		}
	}
}

WP_CLI::log( 'strony glowne: ' . implode( ', ', array_map( static function ( $l, $i ) {
	return $l . '=#' . $i;
}, array_keys( $kz_pages ), $kz_pages ) ) );

// ── 1. Seed the option, if it is not there yet ────────────────────────────────
$kz_option = get_option( \Kzmielec\Contact\ContactData::OPTION, array() );

if ( ! is_array( $kz_option ) || ! $kz_option ) {
	WP_CLI::log( 'opcja: zasiewam wartosciami domyslnymi (email zbor@kzmielec.pl)' );

	if ( $kz_go ) {
		update_option( \Kzmielec\Contact\ContactData::OPTION, \Kzmielec\Contact\ContactData::DEFAULTS );
	}
} else {
	WP_CLI::log( 'opcja: juz istnieje, nie ruszam' );
}

// ── 2. Rebuild the paragraph run on each page ─────────────────────────────────
$kz_changed = 0;
$kz_skipped = 0;

foreach ( $kz_pages as $kz_lang => $kz_id ) {
	$kz_content = (string) get_post_field( 'post_content', $kz_id );

	if ( '' === $kz_content ) {
		WP_CLI::warning( "wpis #{$kz_id} ({$kz_lang}): pusta tresc, pomijam" );
		++$kz_skipped;
		continue;
	}

	$kz_anchor = strpos( $kz_content, 'id="four"' );

	if ( false === $kz_anchor ) {
		WP_CLI::warning( "wpis #{$kz_id} ({$kz_lang}): brak naglowka #four, pomijam" );
		++$kz_skipped;
		continue;
	}

	// The run starts after the "#four" heading block and ends at the map block.
	$kz_head = strpos( $kz_content, '<!-- /wp:heading -->', $kz_anchor );
	$kz_map  = strpos( $kz_content, '<!-- wp:custom-block-package/map-block' );

	if ( false === $kz_head || false === $kz_map || $kz_map <= $kz_head ) {
		WP_CLI::warning( "wpis #{$kz_id} ({$kz_lang}): nie znalazlem odcinka miedzy naglowkiem #four a mapa, pomijam" );
		++$kz_skipped;
		continue;
	}

	$kz_head += strlen( '<!-- /wp:heading -->' );
	$kz_old   = substr( $kz_content, $kz_head, $kz_map - $kz_head );

	// Fallback text in the page's own language. `switch_to_locale()` cannot do this —
	// see the reasoning in ContactBindings::with_locale().
	$kz_locale = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $kz_id, 'locale' ) : '';
	$kz_locale = is_string( $kz_locale ) ? $kz_locale : '';

	$kz_new = \Kzmielec\Contact\ContactBindings::with_locale(
		$kz_locale,
		static function (): string {
			$blocks = array();

			foreach ( \Kzmielec\Contact\ContactBindings::KEYS as $key ) {
				$blocks[] = sprintf(
					'<!-- wp:paragraph {"style":{"typography":{"textAlign":"center"}},"metadata":{"bindings":{"content":{"source":"%1$s","args":{"key":"%2$s"}}}}} -->' . "\n" .
					'<p class="has-text-align-center">%3$s</p>' . "\n" .
					'<!-- /wp:paragraph -->',
					\Kzmielec\Contact\ContactBindings::SOURCE,
					$key,
					(string) \Kzmielec\Contact\ContactBindings::line( $key )
				);
			}

			return "\n\n" . implode( "\n\n", $blocks ) . "\n\n";
		}
	);

	if ( $kz_new === $kz_old ) {
		WP_CLI::log( "wpis #{$kz_id} ({$kz_lang}): bez zmian" );
		continue;
	}

	++$kz_changed;

	WP_CLI::log( "wpis #{$kz_id} ({$kz_lang}): odcinek do podmiany, " . strlen( $kz_old ) . ' -> ' . strlen( $kz_new ) . ' znakow' );

	if ( ! $kz_go ) {
		WP_CLI::log( "  --- PRZED ---\n" . trim( $kz_old ) );
		WP_CLI::log( "  --- PO ---\n" . trim( $kz_new ) );
		continue;
	}

	wp_update_post(
		array(
			'ID'           => $kz_id,
			'post_content' => substr( $kz_content, 0, $kz_head ) . $kz_new . substr( $kz_content, $kz_map ),
		)
	);
}

WP_CLI::log( sprintf( 'stron do zmiany: %d, pominietych: %d, z %d', $kz_changed, $kz_skipped, count( $kz_pages ) ) );

if ( $kz_skipped > 0 ) {
	WP_CLI::warning( 'Pominieto strony — obejrzyj je, zanim uznasz to za skonczone.' );
}

if ( $kz_go ) {
	wp_cache_flush();
	WP_CLI::success( 'Zapisane. Dane kontaktowe pochodza teraz z jednego zrodla.' );
} else {
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
}
