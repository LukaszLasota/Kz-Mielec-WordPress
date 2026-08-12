<?php
/**
 * Terminology glossary: the files exist, they parse, and they cover three languages.
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
		$fails[] = "[$lang] the glossary has " . count( $pary ) . ' pairs, expected at least 11';
	}

	// The denomination name has to be forced — without it, it drifts across 37 topics.
	if ( ! isset( $pary['Kościół Zielonoświątkowy'] ) ) {
		$fails[] = "[$lang] no entry for \"Kościół Zielonoświątkowy\"";
	}
	if ( ! isset( $pary['Kościół Rzymskokatolicki'] ) ) {
		$fails[] = "[$lang] no entry for \"Kościół Rzymskokatolicki\"";
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

// An unknown language returns an empty array, not an exception.
if ( array() !== $g_cls::pairs( 'DE' ) ) {
	$fails[] = 'an unknown language did not return an empty array';
}

/*
 * Without a key, ensure() must return an empty string rather than throw: a missing glossary
 * may lower consistency, but it must not block the run.
 */
if ( ! defined( 'KZMIELEC_DEEPL_API_KEY' ) && '' === (string) get_option( \KzmielecTranslate\Admin\DeeplSettings::OPTION_KEY, '' ) ) {
	foreach ( array( 'EN-GB', 'UK', 'ES', 'DE' ) as $lang ) {
		if ( '' !== $g_cls::ensure( $lang ) ) {
			$fails[] = "[$lang] ensure() without a key returned a non-empty value";
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
echo "PASS: the glossaries are correct for all three languages\n";