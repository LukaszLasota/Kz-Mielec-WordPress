<?php
/**
 * Bloki, ktore pobieraja tresc, musza ja zawezac do jezyka renderowanego wpisu.
 *
 * Ten test istnieje, bo tego bledu NIE WIDAC na froncie. Polylang zawezal zapytania
 * sam, po jezyku zadania, wiec strona wygladala poprawnie, a edytor — ktory renderuje
 * bloki przez trase REST bez kontekstu jezyka — dostawal wszystkie cztery wersje naraz:
 * 12 spotkan zamiast 3, 148 tematow zamiast 37, 36 naglowkow zamiast 9.
 *
 * Sedno testu: ustawiamy wpis obcojezyczny jako biezacy, ZOSTAWIAJAC jezyk zadania
 * polski. To dokladnie sytuacja edytora. Blok ma odpowiedziec w jezyku wpisu.
 */
$fails = array();
$svc   = '\CustomBlockPackage\Services\NavigableTilesService';

if ( ! class_exists( $svc ) ) {
	echo "FAIL\n  - brak klasy $svc\n";
	exit( 1 );
}

if ( ! function_exists( 'pll_get_post' ) ) {
	echo "PASS: Polylang nieaktywny — zawezanie nie ma zastosowania\n";
	exit( 0 );
}

/** Strona glowna po polsku i jej tlumaczenia. */
$front = array( 'pl' => 131 );
foreach ( array( 'en', 'uk', 'es' ) as $l ) {
	$t = pll_get_post( 131, $l );
	if ( $t ) {
		$front[ $l ] = (int) $t;
	}
}

if ( 4 !== count( $front ) ) {
	$fails[] = 'nie znalazlem czterech wersji strony glownej, jest ' . count( $front );
}

// ── kafelki: liczba i jezyk ────────────────────────────────────────────────
foreach ( $front as $lang => $id ) {
	$GLOBALS['post'] = get_post( $id );
	setup_postdata( $GLOBALS['post'] );

	$meetings = $svc::get_meetings();
	$beliefs  = $svc::get_beliefs();

	if ( 3 !== count( $meetings ) ) {
		$fails[] = "$lang: spotkan " . count( $meetings ) . ', oczekiwano 3';
	}
	if ( 8 !== count( $beliefs ) ) {
		$fails[] = "$lang: stron wiary " . count( $beliefs ) . ', oczekiwano 8';
	}

	foreach ( array( 'spotkania' => $meetings, 'wiara' => $beliefs ) as $what => $items ) {
		foreach ( $items as $item ) {
			$got = (string) pll_get_post_language( (int) $item['id'] );
			if ( $got !== $lang ) {
				$fails[] = "$lang: $what — kafelek #" . $item['id'] . " jest w jezyku '$got'";
				break;
			}
		}
	}

	wp_reset_postdata();
}

// ── akordeon porownania: tematy i naglowki ─────────────────────────────────
$cmp = array( 'pl' => 83 );
foreach ( array( 'en', 'uk', 'es' ) as $l ) {
	$t = pll_get_post( 83, $l );
	if ( $t ) {
		$cmp[ $l ] = (int) $t;
	}
}

foreach ( $cmp as $lang => $id ) {
	$topics = get_posts(
		array(
			'post_type'      => 'comparison_topic',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'lang'           => $lang,
		)
	);
	$terms  = get_terms(
		array(
			'taxonomy'   => 'comparison_category',
			'hide_empty' => true,
			'lang'       => $lang,
		)
	);

	if ( 37 !== count( $topics ) ) {
		$fails[] = "$lang: tematow porownania " . count( $topics ) . ', oczekiwano 37';
	}
	if ( ! is_array( $terms ) || 9 !== count( $terms ) ) {
		$fails[] = "$lang: naglowkow akordeonu " . ( is_array( $terms ) ? count( $terms ) : 0 ) . ', oczekiwano 9';
	}
}

// ── kolejnosc akordeonu ta sama w kazdym jezyku ────────────────────────────
$by_lang = array();
foreach ( array_keys( $cmp ) as $lang ) {
	$ordered = get_terms(
		array(
			'taxonomy'   => 'comparison_category',
			'hide_empty' => true,
			'lang'       => $lang,
			'meta_key'   => 'sort_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'    => 'meta_value_num',
			'order'      => 'ASC',
		)
	);

	$by_lang[ $lang ] = is_array( $ordered ) ? wp_list_pluck( $ordered, 'term_id' ) : array();
}

if ( function_exists( 'pll_get_term' ) && $by_lang ) {
	foreach ( $by_lang['pl'] as $position => $pl_term ) {
		foreach ( array( 'en', 'uk', 'es' ) as $lang ) {
			if ( ! isset( $by_lang[ $lang ] ) ) {
				continue;
			}

			$translated = (int) pll_get_term( $pl_term, $lang );
			$found      = array_search( $translated, $by_lang[ $lang ], true );

			if ( $found !== $position ) {
				$fails[] = "kolejnosc akordeonu: pozycja $position w pl to pozycja " . var_export( $found, true ) . " w $lang";
			}
		}
	}
}

if ( $fails ) {
	echo "FAIL\n";
	foreach ( $fails as $f ) {
		echo "  - $f\n";
	}
	exit( 1 );
}

echo "PASS: bloki zawezaja tresc do jezyka wpisu, akordeon zgodny co do liczby i kolejnosci\n";
