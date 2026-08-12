<?php
/**
 * Ochrona markupu blokow — najwazniejszy test silnika tlumaczacego.
 *
 * Blad tutaj oznacza wpisy, ktorych nie da sie otworzyc w edytorze, albo
 * przetlumaczone `targetId`, ktore cicho psuje strzalki przewijania.
 *
 * Uruchamianie: ddev wp eval-file - < scripts/tests/kzt-blocks.php
 *
 * @package KzmielecTranslate
 */

$fails = array();
$b_cls = '\KzmielecTranslate\Services\BlockSafeText';

if ( ! class_exists( $b_cls ) ) {
	echo "FAIL\n  - brak klasy $b_cls\n";
	exit( 1 );
}

$stub = new \KzmielecTranslate\Services\StubTranslator();

$zrodlo = '<!-- wp:custom-block-package/dynamic-images {"heading":"Kościół Zielonoświątkowy","className":"is-style-banner-hero","imgDesktopURL":"https://example.test/a.jpg"} /-->
<!-- wp:heading {"anchor":"three","className":"is-style-section-line"} -->
<h2 class="wp-block-heading is-style-section-line" id="three">Nasze spotkania</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Zapraszamy w każdą niedzielę.</p>
<!-- /wp:paragraph -->
<!-- wp:custom-block-package/accordion-item {"title":"Ustawa z dnia 20 lutego 1997 r."} -->
<div class="accordion-item"><p>Treść ustawy.</p></div>
<!-- /wp:custom-block-package/accordion-item -->
<!-- wp:custom-block-package/scroll-arrow {"targetId":"three","direction":"up","ariaLabel":"Przewiń do góry strony"} /-->
<!-- wp:custom-block-package/navigable-tiles {"dataSource":"meetings"} /-->';

$wynik = $b_cls::translate_content( $zrodlo, $stub, 'EN-GB' );

$nazwy = static function ( string $c ): array {
	$out = array();
	$ch  = static function ( $bs ) use ( &$ch, &$out ) {
		foreach ( $bs as $b ) {
			if ( $b['blockName'] ) {
				$out[] = $b['blockName'];
			}
			if ( ! empty( $b['innerBlocks'] ) ) {
				$ch( $b['innerBlocks'] );
			}
		}
	};
	$ch( parse_blocks( $c ) );
	return $out;
};

$attr = static function ( array $bs, string $blok, string $a ) use ( &$attr ) {
	foreach ( $bs as $b ) {
		if ( ( $b['blockName'] ?? '' ) === $blok && isset( $b['attrs'][ $a ] ) ) {
			return $b['attrs'][ $a ];
		}
		if ( ! empty( $b['innerBlocks'] ) ) {
			$r = $attr( $b['innerBlocks'], $blok, $a );
			if ( null !== $r ) {
				return $r;
			}
		}
	}
	return null;
};

// 1. Struktura blokow nietknieta.
if ( $nazwy( $zrodlo ) !== $nazwy( $wynik ) ) {
	$fails[] = 'struktura blokow zmieniona: ' . implode( ',', $nazwy( $wynik ) );
}

// 2. Tekst przetlumaczony i nic nie zgubione.
if ( false === strpos( $wynik, '[EN-GB] ' ) ) {
	$fails[] = 'nic nie zostalo przetlumaczone';
}
foreach ( array( 'Nasze spotkania', 'Zapraszamy', 'Treść ustawy' ) as $frag ) {
	if ( false === strpos( $wynik, $frag ) ) {
		$fails[] = "zgubiony tekst: $frag";
	}
}

$blocks = parse_blocks( $wynik );

// 3. Atrybuty z bialej listy przetlumaczone.
foreach ( array(
	array( 'custom-block-package/dynamic-images', 'heading' ),
	array( 'custom-block-package/scroll-arrow', 'ariaLabel' ),
	array( 'custom-block-package/accordion-item', 'title' ),
) as $para ) {
	$v = (string) $attr( $blocks, $para[0], $para[1] );

	if ( 0 !== strpos( $v, '[EN-GB] ' ) ) {
		$fails[] = "atrybut {$para[0]}→{$para[1]} nieprzetlumaczony: \"$v\"";
	}
}

// 4. Atrybuty techniczne NIETKNIETE — asercja chroniaca dzialanie strony.
foreach ( array(
	array( 'custom-block-package/scroll-arrow', 'targetId', 'three' ),
	array( 'custom-block-package/scroll-arrow', 'direction', 'up' ),
	array( 'core/heading', 'anchor', 'three' ),
	array( 'core/heading', 'className', 'is-style-section-line' ),
	array( 'custom-block-package/dynamic-images', 'className', 'is-style-banner-hero' ),
	array( 'custom-block-package/dynamic-images', 'imgDesktopURL', 'https://example.test/a.jpg' ),
	array( 'custom-block-package/navigable-tiles', 'dataSource', 'meetings' ),
) as $t ) {
	$v = (string) $attr( $blocks, $t[0], $t[1] );

	if ( $v !== $t[2] ) {
		$fails[] = "atrybut techniczny {$t[0]}→{$t[1]} ZMIENIONY: \"$v\" (byl \"{$t[2]}\")";
	}
}

// 5. Wynik da sie sparsowac i zserializowac — czyli otworzy sie w edytorze.
if ( '' === trim( serialize_blocks( parse_blocks( $wynik ) ) ) ) {
	$fails[] = 'wynik nie serializuje sie';
}

// 6. segments() zglasza tyle, ile faktycznie zostanie wyslane.
$segs = $b_cls::segments( $zrodlo );

if ( count( $segs ) < 6 ) {
	$fails[] = 'segments() zwrocilo ' . count( $segs ) . ', oczekiwano >=6';
}
foreach ( $segs as $s ) {
	if ( '' === trim( (string) $s ) ) {
		$fails[] = 'segments() zwrocilo pusty segment';
	}
}

// 7. Drugi przebieg nie psuje struktury.
if ( $nazwy( $b_cls::translate_content( $wynik, $stub, 'UK' ) ) !== $nazwy( $zrodlo ) ) {
	$fails[] = 'drugi przebieg zmienil strukture blokow';
}

// 8. Pusta tresc nie wybucha.
if ( '' !== $b_cls::translate_content( '', $stub, 'EN-GB' ) ) {
	$fails[] = 'pusta tresc nie zwrocila pustego wyniku';
}

// 9. Prawdziwa strona glowna: struktura i atrybuty techniczne bez zmian.
$front = (int) get_option( 'page_on_front' );

if ( $front > 0 ) {
	$src = (string) get_post( $front )->post_content;
	$dst = $b_cls::translate_content( $src, $stub, 'EN-GB' );

	if ( $nazwy( $src ) !== $nazwy( $dst ) ) {
		$fails[] = 'strona glowna: struktura blokow zmieniona';
	}
	foreach ( array( 'targetId', 'anchor', 'dataSource', 'className', 'imgDesktopURL', 'tileStyle' ) as $a ) {
		if ( substr_count( $src, '"' . $a . '":' ) !== substr_count( $dst, '"' . $a . '":' ) ) {
			$fails[] = "strona glowna: zmieniona liczba atrybutow \"$a\"";
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
echo 'PASS: markup blokow chroniony, biala lista dziala (' . count( $segs ) . " segmentow na probce)\n";