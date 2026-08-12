<?php
/**
 * Tworzenie tlumaczenia wpisu: powiazanie jezykowe, tresc, slug, idempotencja,
 * zapis par zrodlo-tlumaczenie.
 *
 * Uruchamianie: ddev wp eval-file - < scripts/tests/kzt-post.php
 *
 * @package KzmielecTranslate
 */

$fails = array();
$pt_c  = '\KzmielecTranslate\Translators\PostTranslator';
$ss_c  = '\KzmielecTranslate\Services\SegmentStore';

foreach ( array( $pt_c, $ss_c ) as $k ) {
	if ( ! class_exists( $k ) ) {
		$fails[] = "brak klasy $k";
	}
}
if ( $fails ) {
	echo "FAIL\n";
	foreach ( $fails as $f ) {
		echo "  - $f\n";
	}
	exit( 1 );
}

// Wpis testowy, usuwany na koncu niezaleznie od wyniku.
$src = (int) wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Test tlumaczenia',
		'post_content' => "<!-- wp:paragraph -->\n<p>Zapraszamy w niedzielę.</p>\n<!-- /wp:paragraph -->",
	)
);
pll_set_post_language( $src, 'pl' );

$t   = new $pt_c( new \KzmielecTranslate\Services\StubTranslator() );
$tid = 0;

// 1. Tryb raportowania nie zapisuje niczego.
$raport = $t->translate( $src, 'en', false );

if ( 0 !== $raport['created'] ) {
	$fails[] = 'tryb raportowania utworzyl wpis';
}
if ( $raport['segments'] < 1 || $raport['chars'] < 1 ) {
	$fails[] = 'raport nie policzyl segmentow lub znakow';
}
if ( pll_get_post( $src, 'en' ) ) {
	$fails[] = 'tryb raportowania powiazal tlumaczenie';
}

// 2. Zapis tworzy powiazany wpis w docelowym jezyku.
$wynik = $t->translate( $src, 'en', true );
$tid   = (int) $wynik['target_id'];

if ( ! $tid ) {
	$fails[] = 'nie zwrocono target_id';
} else {
	if ( 'en' !== pll_get_post_language( $tid ) ) {
		$fails[] = 'jezyk tlumaczenia to ' . var_export( pll_get_post_language( $tid ), true );
	}
	if ( (int) pll_get_post( $src, 'en' ) !== $tid ) {
		$fails[] = 'brak powiazania zrodlo->tlumaczenie';
	}

	$tp = get_post( $tid );

	// 3. Tresc i tytul przetlumaczone, struktura blokow zachowana.
	if ( false === strpos( (string) $tp->post_content, '[EN-GB]' ) ) {
		$fails[] = 'tresc nieprzetlumaczona';
	}
	if ( false === strpos( (string) $tp->post_content, 'wp:paragraph' ) ) {
		$fails[] = 'zgubiony znacznik bloku';
	}
	if ( false === strpos( (string) $tp->post_title, '[EN-GB]' ) ) {
		$fails[] = 'tytul nieprzetlumaczony';
	}

	// 4. Slug przeliczony z przetlumaczonego tytulu.
	if ( (string) $tp->post_name === (string) get_post( $src )->post_name ) {
		$fails[] = 'slug nie zostal przeliczony: ' . $tp->post_name;
	}

	// 5. Pary zrodlo-tlumaczenie zapisane — bez nich Planu C nie da sie wykonac.
	$pary = $ss_c::all( $tid );

	if ( count( $pary ) < 2 ) {
		$fails[] = 'zapisano ' . count( $pary ) . ' par, oczekiwano >=2 (tytul + tresc)';
	}
	foreach ( $pary as $p ) {
		if ( '' === trim( $p['source'] ) || '' === trim( $p['translation'] ) ) {
			$fails[] = 'para z pustym polem: ' . $p['field'];
		}
	}

	// 6. Idempotencja: drugie uruchomienie bez --force nie tworzy duplikatu.
	$drugie = $t->translate( $src, 'en', true );

	if ( 0 !== $drugie['created'] ) {
		$fails[] = 'drugie uruchomienie utworzylo duplikat';
	}
	if ( (int) $drugie['target_id'] !== $tid ) {
		$fails[] = 'drugie uruchomienie zwrocilo inne target_id';
	}

	// 7. Nieznany jezyk nie wybucha i nic nie tworzy.
	if ( 0 !== (int) $t->translate( $src, 'de', true )['target_id'] ) {
		$fails[] = 'nieznany jezyk utworzyl wpis';
	}
}

if ( $tid ) {
	wp_delete_post( $tid, true );
}
wp_delete_post( $src, true );

if ( $fails ) {
	echo "FAIL\n";
	foreach ( $fails as $f ) {
		echo "  - $f\n";
	}
	exit( 1 );
}
echo "PASS: tlumaczenie wpisu, powiazanie i zapis par dzialaja\n";