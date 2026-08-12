<?php
/**
 * Slownik terminow: pliki istnieja, parsuja sie, pokrywaja trzy jezyki.
 *
 * Uruchamianie: ddev wp eval-file - < scripts/tests/kzt-glossary.php
 *
 * @package KzmielecTranslate
 */

$fails = array();
$g_cls = '\KzmielecTranslate\Services\Glossary';

if ( ! class_exists( $g_cls ) ) {
	echo "FAIL\n  - brak klasy $g_cls\n";
	exit( 1 );
}

foreach ( array( 'EN-GB', 'UK', 'ES' ) as $lang ) {
	$pary = $g_cls::pairs( $lang );

	if ( count( $pary ) < 11 ) {
		$fails[] = "[$lang] slownik ma " . count( $pary ) . ' par, oczekiwano co najmniej 11';
	}

	// Nazwa wyznania musi byc wymuszona — bez niej rozjedzie sie miedzy 37 tematami.
	if ( ! isset( $pary['Kościół Zielonoświątkowy'] ) ) {
		$fails[] = "[$lang] brak wpisu dla \"Kościół Zielonoświątkowy\"";
	}
	if ( ! isset( $pary['Kościół Rzymskokatolicki'] ) ) {
		$fails[] = "[$lang] brak wpisu dla \"Kościół Rzymskokatolicki\"";
	}

	foreach ( $pary as $zrodlo => $cel ) {
		if ( '' === trim( (string) $cel ) ) {
			$fails[] = "[$lang] pusty cel dla \"$zrodlo\"";
		}
		if ( trim( (string) $zrodlo ) !== (string) $zrodlo ) {
			$fails[] = "[$lang] zbedne spacje w \"$zrodlo\"";
		}
	}
}

// Nieznany jezyk zwraca pusta tablice, nie wyjatek.
if ( array() !== $g_cls::pairs( 'DE' ) ) {
	$fails[] = 'nieznany jezyk nie zwrocil pustej tablicy';
}

/*
 * Bez klucza ensure() musi zwrocic pusty ciag, nie wyjatek: brak slownika nie
 * moze blokowac przebiegu, tylko obnizac spojnosc.
 */
if ( ! defined( 'KZMIELEC_DEEPL_API_KEY' ) && '' === (string) get_option( \KzmielecTranslate\Admin\DeeplSettings::OPTION_KEY, '' ) ) {
	foreach ( array( 'EN-GB', 'UK', 'ES', 'DE' ) as $lang ) {
		if ( '' !== $g_cls::ensure( $lang ) ) {
			$fails[] = "[$lang] ensure() bez klucza zwrocilo niepusta wartosc";
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
echo "PASS: slowniki poprawne dla trzech jezykow\n";