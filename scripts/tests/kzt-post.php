<?php
/**
 * Creating a post translation: the language link, content, slug, idempotence and the
 * recorded source-translation pairs.
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

// 1. The reporting mode writes nothing.
$raport = $t->translate( $src, 'en', false );

if ( 0 !== $raport['created'] ) {
	$fails[] = 'the reporting mode created a post';
}
if ( $raport['segments'] < 1 || $raport['chars'] < 1 ) {
	$fails[] = 'the report counted no segments or characters';
}
if ( pll_get_post( $src, 'en' ) ) {
	$fails[] = 'tryb raportowania powiazal tlumaczenie';
}

// 2. Writing creates a linked post in the target language.
$wynik = $t->translate( $src, 'en', true );
$tid   = (int) $wynik['target_id'];

if ( ! $tid ) {
	$fails[] = 'nie zwrocono target_id';
} else {
	if ( 'en' !== pll_get_post_language( $tid ) ) {
		$fails[] = 'the translation language is ' . var_export( pll_get_post_language( $tid ), true );
	}
	if ( (int) pll_get_post( $src, 'en' ) !== $tid ) {
		$fails[] = 'the source->translation link is missing';
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

	// 5. Source-translation pairs recorded — without them the editorial pass is impossible.
	$pary = $ss_c::all( $tid );

	if ( count( $pary ) < 2 ) {
		$fails[] = 'recorded ' . count( $pary ) . ' pairs, expected >=2 (title + content)';
	}
	foreach ( $pary as $p ) {
		if ( '' === trim( $p['source'] ) || '' === trim( $p['translation'] ) ) {
			$fails[] = 'para z pustym polem: ' . $p['field'];
		}
	}

	// 6. Idempotence: a second run without --force creates no duplicate.
	$drugie = $t->translate( $src, 'en', true );

	if ( 0 !== $drugie['created'] ) {
		$fails[] = 'the second run created a duplicate';
	}
	if ( (int) $drugie['target_id'] !== $tid ) {
		$fails[] = 'drugie uruchomienie zwrocilo inne target_id';
	}

	// 7. An unknown language neither blows up nor creates anything.
	if ( 0 !== (int) $t->translate( $src, 'de', true )['target_id'] ) {
		$fails[] = 'an unknown language created a post';
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
echo "PASS: post translation, linking and pair recording all work\n";