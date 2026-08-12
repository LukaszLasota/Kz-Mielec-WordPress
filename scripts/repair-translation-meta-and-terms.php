<?php
/**
 * Uzupelnia to, czego pierwsze przebiegi tlumaczenia nie przeniosly.
 *
 * Uruchamianie: ddev wp eval-file - < scripts/repair-translation-meta-and-terms.php
 *
 * Naprawia trzy braki, wszystkie wykryte dopiero po obejrzeniu strony, bo kazdy
 * z nich pozostawial strone zwracajaca 200 i przechodzaca testy funkcjonalne:
 *
 * 1. `_wp_page_template` — przypisanie szablonu. `wp_insert_post()` go nie kopiuje,
 *    wiec przetlumaczone strony wiary renderowaly sie szablonem domyslnym zamiast
 *    `page-belief.php`, ktory rysuje kafelki nawigacji wiary.
 * 2. `_belief_hover_image` — ID nakladki pokazywanej na kafelku po najechaniu.
 * 3. Terminy taksonomii `comparison_category` — nie byly tlumaczone WCALE, wiec
 *    akordeon porownania nie mial kategorii do grupowania i strona wychodzila pusta.
 *    Same przetlumaczone terminy nie wystarcza: kazdy przetlumaczony temat trzeba
 *    jeszcze pod nie podpiac.
 *
 * Tresc nie jest tlumaczona ponownie — koszt to wylacznie 9 nazw terminow na jezyk.
 *
 * @package KzmielecTranslate
 */

if ( ! function_exists( 'pll_get_post' ) ) {
	echo "  BLAD: Polylang nieaktywny\n";
	return;
}

$jezyki   = \KzmielecTranslate\Translators\PostTranslator::DEEPL_LANG;
$klient   = \KzmielecTranslate\Services\DeeplClient::from_settings();
$kopiowane = array( '_wp_page_template', '_belief_hover_image' );

if ( null === $klient ) {
	echo "  BLAD: brak klucza DeepL — terminow nie da sie przetlumaczyc\n";
	return;
}

// ── 1. Pola stron ─────────────────────────────────────────────────────────
$skopiowane = 0;

foreach ( get_posts(
	array(
		'post_type'   => 'page',
		'post_status' => array( 'publish', 'draft' ),
		'numberposts' => -1,
		'lang'        => 'pl',
	)
) as $p ) {
	foreach ( array_keys( $jezyki ) as $lang ) {
		$t = (int) pll_get_post( $p->ID, $lang );

		if ( ! $t ) {
			continue;
		}

		foreach ( $kopiowane as $key ) {
			$v = get_post_meta( $p->ID, $key, true );

			if ( '' !== $v && null !== $v && get_post_meta( $t, $key, true ) !== $v ) {
				update_post_meta( $t, $key, $v );
				++$skopiowane;
			}
		}
	}
}

printf( "  1. pola stron skopiowane: %d\n", $skopiowane );

// ── 2. Terminy taksonomii ─────────────────────────────────────────────────
foreach ( $jezyki as $lang => $deepl ) {
	$glossary   = \KzmielecTranslate\Services\Glossary::ensure( $deepl );
	$translator = \KzmielecTranslate\Services\DeeplClient::from_settings( $glossary );

	if ( null === $translator ) {
		continue;
	}

	$r = ( new \KzmielecTranslate\Translators\TermTranslator( $translator ) )->translate_all( $lang, $deepl, true );

	printf( "  2. terminy %s: przetlumaczono %d, utworzono %d, znakow %d\n", $lang, $r['terms'], $r['created'], $r['chars'] );
}

// ── 3. Przypisanie tematow do przetlumaczonych terminow ───────────────────
$przypisane = 0;
$stub       = new \KzmielecTranslate\Services\StubTranslator();
$term_tr    = new \KzmielecTranslate\Translators\TermTranslator( $stub );

foreach ( get_posts(
	array(
		'post_type'   => 'comparison_topic',
		'post_status' => array( 'publish', 'draft' ),
		'numberposts' => -1,
		'lang'        => 'pl',
		'fields'      => 'ids',
	)
) as $id ) {
	foreach ( array_keys( $jezyki ) as $lang ) {
		$t = (int) pll_get_post( (int) $id, $lang );

		if ( $t && $term_tr->assign( (int) $id, $t, $lang ) > 0 ) {
			++$przypisane;
		}
	}
}

printf( "  3. tematow podpietych pod kategorie: %d\n", $przypisane );

PLL()->model->clean_languages_cache();

// ── Kontrola ──────────────────────────────────────────────────────────────
echo "\n  === kontrola ===\n";

foreach ( array_merge( array( 'pl' ), array_keys( $jezyki ) ) as $lang ) {
	$terminy = get_terms(
		array(
			'taxonomy'   => 'comparison_category',
			'hide_empty' => false,
			'lang'       => $lang,
		)
	);

	$bez_kat = 0;

	foreach ( get_posts(
		array(
			'post_type'   => 'comparison_topic',
			'numberposts' => -1,
			'lang'        => $lang,
			'fields'      => 'ids',
		)
	) as $id ) {
		$t = wp_get_post_terms( (int) $id, 'comparison_category', array( 'fields' => 'ids' ) );

		if ( is_wp_error( $t ) || array() === $t ) {
			++$bez_kat;
		}
	}

	$bez_szablonu = 0;

	foreach ( get_posts(
		array(
			'post_type'   => 'page',
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => -1,
			'lang'        => $lang,
			'fields'      => 'ids',
		)
	) as $id ) {
		if ( 'page-belief.php' === get_post_meta( (int) $id, '_wp_page_template', true ) ) {
			continue;
		}
		++$bez_szablonu;
	}

	printf(
		"  %-4s kategorii=%-3d tematow bez kategorii=%-3d stron bez szablonu belief=%d\n",
		$lang,
		is_wp_error( $terminy ) ? 0 : count( $terminy ),
		$bez_kat,
		$bez_szablonu
	);
}
