<?php
/**
 * Odtwarza powiazania jezykowe osieroconych tlumaczen.
 *
 * Potrzebne po bledzie, w ktorym `pll_save_post_translations()` dostawalo tylko
 * pare zrodlo-cel i zastepowalo cala grupe, wymazujac jezyki wgrane wczesniej.
 * Kod jest naprawiony, ale wpisy juz utworzone trzeba pozszywac.
 *
 * Dopasowanie idzie po `_kzt_segments`: kazde tlumaczenie ma zapisany wiersz
 * `post_title` z ORYGINALNYM polskim tytulem, wiec zrodlo da sie odnalezc bez
 * zgadywania.
 *
 * Uruchamianie: ddev wp eval-file - < scripts/tests/kzt-relink.php
 *
 * @package KzmielecTranslate
 */

$typy   = array( 'page', 'meetings', 'comparison_topic' );
$jezyki = array( 'en', 'uk', 'es' );

// Indeks polskich wpisow: typ + tytul => id.
$indeks = array();

foreach ( get_posts(
	array(
		'post_type'   => $typy,
		'post_status' => array( 'publish', 'draft' ),
		'numberposts' => -1,
		'lang'        => 'pl',
	)
) as $p ) {
	$klucz = $p->post_type . '|' . $p->post_title;

	if ( isset( $indeks[ $klucz ] ) ) {
		$indeks[ $klucz ] = 'AMBIGUOUS';
		continue;
	}

	$indeks[ $klucz ] = (int) $p->ID;
}

$grupy      = array();
$bez_zrodla = array();
$niejednozn = array();

foreach ( $jezyki as $lang ) {
	foreach ( get_posts(
		array(
			'post_type'   => $typy,
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => -1,
			'lang'        => $lang,
		)
	) as $p ) {
		$zrodlo_tytul = '';

		foreach ( \KzmielecTranslate\Services\SegmentStore::all( (int) $p->ID ) as $row ) {
			if ( 'post_title' === $row['field'] ) {
				$zrodlo_tytul = $row['source'];
				break;
			}
		}

		if ( '' === $zrodlo_tytul ) {
			$bez_zrodla[] = $lang . ' #' . $p->ID . ' "' . $p->post_title . '"';
			continue;
		}

		$klucz = $p->post_type . '|' . $zrodlo_tytul;
		$src   = $indeks[ $klucz ] ?? null;

		if ( null === $src ) {
			$bez_zrodla[] = $lang . ' #' . $p->ID . ' → brak polskiego "' . $zrodlo_tytul . '"';
			continue;
		}

		if ( 'AMBIGUOUS' === $src ) {
			$niejednozn[] = $lang . ' #' . $p->ID . ' → wiele polskich "' . $zrodlo_tytul . '"';
			continue;
		}

		$grupy[ $src ]['pl']    = $src;
		$grupy[ $src ][ $lang ] = (int) $p->ID;
	}
}

$zapisane = 0;

foreach ( $grupy as $src => $grupa ) {
	// Zachowujemy to, co juz jest powiazane, i dokladamy odnalezione.
	$grupa = array_merge( (array) pll_get_post_translations( (int) $src ), $grupa );

	pll_save_post_translations( $grupa );
	++$zapisane;
}

if ( function_exists( 'PLL' ) ) {
	PLL()->model->clean_languages_cache();
}

printf( "  grup zapisanych: %d\n", $zapisane );

foreach ( $jezyki as $lang ) {
	$maja = 0;
	$ile  = 0;

	foreach ( get_posts(
		array(
			'post_type'   => $typy,
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => -1,
			'lang'        => 'pl',
			'fields'      => 'ids',
		)
	) as $id ) {
		++$ile;

		if ( pll_get_post( (int) $id, $lang ) ) {
			++$maja;
		}
	}

	printf( "  pl→%s: %d z %d\n", $lang, $maja, $ile );
}

if ( $bez_zrodla ) {
	printf( "\n  BEZ ZRODLA (%d):\n", count( $bez_zrodla ) );
	foreach ( array_slice( $bez_zrodla, 0, 10 ) as $x ) {
		echo "    $x\n";
	}
}

if ( $niejednozn ) {
	printf( "\n  NIEJEDNOZNACZNE (%d):\n", count( $niejednozn ) );
	foreach ( array_slice( $niejednozn, 0, 10 ) as $x ) {
		echo "    $x\n";
	}
}
