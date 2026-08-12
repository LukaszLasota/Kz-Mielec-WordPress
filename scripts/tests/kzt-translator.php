<?php
/**
 * The translator contract: the stub and the DeepL client must be interchangeable.
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

// 1. The same length and order as the input — substitution is positional.
if ( 3 !== count( $stub->translate( array( 'Jeden', 'Dwa', 'Trzy' ), 'EN-GB' ) ) ) {
	$fails[] = 'the stub did not return 3 items';
}

// 2. Deterministyczna.
if ( $stub->translate( array( 'Jeden' ), 'EN-GB' ) !== $stub->translate( array( 'Jeden' ), 'EN-GB' ) ) {
	$fails[] = 'the stub is not deterministic';
}

// 3. A different target language gives a different result — otherwise the test cannot tell languages apart.
if ( $stub->translate( array( 'Jeden' ), 'EN-GB' ) === $stub->translate( array( 'Jeden' ), 'UK' ) ) {
	$fails[] = 'the stub ignores the target language';
}

// 4. Puste wejscie nie wybucha.
if ( array() !== $stub->translate( array(), 'EN-GB' ) ) {
	$fails[] = 'puste wejscie nie zwrocilo pustej tablicy';
}

// 5. Without a key, from_settings() returns null rather than throwing.
if ( ! defined( 'KZMIELEC_DEEPL_API_KEY' ) ) {
	delete_option( \KzmielecTranslate\Admin\DeeplSettings::OPTION_KEY );

	if ( null !== \KzmielecTranslate\Services\DeeplClient::from_settings() ) {
		$fails[] = 'from_settings() without a key did not return null';
	}
}

// 6. Endpoint wybierany po sufiksie :fx, nie z konfiguracji.
$ref = new ReflectionMethod( '\KzmielecTranslate\Services\DeeplClient', 'endpoint_for' );
$ref->setAccessible( true );

if ( false === strpos( (string) $ref->invoke( null, '12ab34cd-5e6f-7890-abcd-ef1234567890:fx' ), 'api-free.deepl.com' ) ) {
	$fails[] = 'a :fx key did not reach the free endpoint';
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
echo "PASS: the translator contract holds\n";