<?php
/**
 * Kontrakt tlumacza: zaslepka i klient DeepL musza byc wymienne.
 *
 * Uruchamianie: ddev wp eval-file - < scripts/tests/kzt-translator.php
 * Sciezki hosta nie istnieja w kontenerze DDEV, dlatego przez stdin.
 *
 * @package KzmielecTranslate
 */

$fails = array();

foreach ( array(
	'KzmielecTranslate\Services\TranslatorInterface',
	'KzmielecTranslate\Services\StubTranslator',
	'KzmielecTranslate\Services\DeeplClient',
	'KzmielecTranslate\Admin\DeeplSettings',
) as $k ) {
	if ( ! interface_exists( $k ) && ! class_exists( $k ) ) {
		$fails[] = "brak: $k";
	}
}

if ( $fails ) {
	echo "FAIL\n";
	foreach ( $fails as $f ) {
		echo "  - $f\n";
	}
	exit( 1 );
}

$stub = new \KzmielecTranslate\Services\StubTranslator();

// 1. Ta sama dlugosc i kolejnosc co wejscie — podstawianie jest pozycyjne.
if ( 3 !== count( $stub->translate( array( 'Jeden', 'Dwa', 'Trzy' ), 'EN-GB' ) ) ) {
	$fails[] = 'zaslepka nie zwrocila 3 elementow';
}

// 2. Deterministyczna.
if ( $stub->translate( array( 'Jeden' ), 'EN-GB' ) !== $stub->translate( array( 'Jeden' ), 'EN-GB' ) ) {
	$fails[] = 'zaslepka nie jest deterministyczna';
}

// 3. Rozny jezyk docelowy daje rozny wynik — inaczej test nie odrozni jezykow.
if ( $stub->translate( array( 'Jeden' ), 'EN-GB' ) === $stub->translate( array( 'Jeden' ), 'UK' ) ) {
	$fails[] = 'zaslepka ignoruje jezyk docelowy';
}

// 4. Puste wejscie nie wybucha.
if ( array() !== $stub->translate( array(), 'EN-GB' ) ) {
	$fails[] = 'puste wejscie nie zwrocilo pustej tablicy';
}

// 5. Bez klucza from_settings() zwraca null, nie wyjatek.
if ( ! defined( 'KZMIELEC_DEEPL_API_KEY' ) ) {
	delete_option( \KzmielecTranslate\Admin\DeeplSettings::OPTION_KEY );

	if ( null !== \KzmielecTranslate\Services\DeeplClient::from_settings() ) {
		$fails[] = 'from_settings() bez klucza nie zwrocilo null';
	}
}

// 6. Endpoint wybierany po sufiksie :fx, nie z konfiguracji.
$ref = new ReflectionMethod( '\KzmielecTranslate\Services\DeeplClient', 'endpoint_for' );
$ref->setAccessible( true );

if ( false === strpos( (string) $ref->invoke( null, '12ab34cd-5e6f-7890-abcd-ef1234567890:fx' ), 'api-free.deepl.com' ) ) {
	$fails[] = 'klucz :fx nie trafil na endpoint darmowy';
}
if ( false !== strpos( (string) $ref->invoke( null, '12ab34cd-5e6f-7890-abcd-ef1234567890' ), 'api-free' ) ) {
	$fails[] = 'klucz platny trafil na endpoint darmowy';
}

if ( $fails ) {
	echo "FAIL\n";
	foreach ( $fails as $f ) {
		echo "  - $f\n";
	}
	exit( 1 );
}
echo "PASS: kontrakt tlumacza spelniony\n";