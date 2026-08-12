<?php
/**
 * Dane kontaktowe: jedno zrodlo. Najwazniejsza asercja — brak pola nie daje pustki,
 * tylko wartosc domyslna, bo pusta linia na czterech stronach jest gorsza niz stara.
 */
$fails = array();
$cd    = '\Kzmielec\Contact\ContactData';

if ( ! class_exists( $cd ) ) {
	echo "FAIL\n  - brak klasy $cd\n";
	exit( 1 );
}

$before = get_option( $cd::OPTION, array() );

// ── wartosci domyslne, gdy opcji nie ma ────────────────────────────────────
delete_option( $cd::OPTION );

if ( 'Przemysłowa 2' !== $cd::get( 'street' ) ) {
	$fails[] = 'brak opcji: street nie wraca do wartosci domyslnej, jest "' . $cd::get( 'street' ) . '"';
}
if ( 'zbor@kzmielec.pl' !== $cd::get( 'email' ) ) {
	$fails[] = 'brak opcji: email nie wraca do wartosci domyslnej, jest "' . $cd::get( 'email' ) . '"';
}
if ( 9 !== count( $cd::all() ) ) {
	$fails[] = 'all() zwraca ' . count( $cd::all() ) . ' pol, oczekiwano 9';
}
if ( '50.299071' !== $cd::get( 'latitude' ) || '21.4483254' !== $cd::get( 'longitude' ) ) {
	$fails[] = 'wspolrzedne mapy nie wracaja do wartosci domyslnych: ' . $cd::get( 'latitude' ) . ', ' . $cd::get( 'longitude' );
}

// ── puste pole tez schodzi na wartosc domyslna ─────────────────────────────
update_option( $cd::OPTION, array( 'email' => '   ' ) );

if ( 'zbor@kzmielec.pl' !== $cd::get( 'email' ) ) {
	$fails[] = 'puste pole nie schodzi na wartosc domyslna, jest "' . $cd::get( 'email' ) . '"';
}

// ── wartosc z opcji wygrywa, a spacje sa obcinane ──────────────────────────
update_option( $cd::OPTION, array( 'email' => ' inny@example.test ' ) );

if ( 'inny@example.test' !== $cd::get( 'email' ) ) {
	$fails[] = 'opcja nie wygrywa albo spacje nie sa obcinane, jest "' . $cd::get( 'email' ) . '"';
}

// ── formaty maszynowe ──────────────────────────────────────────────────────
update_option(
	$cd::OPTION,
	array(
		'phone' => '669 189 992',
		'email' => 'zbor@kzmielec.pl',
	)
);

if ( '+48669189992' !== $cd::phone_e164() ) {
	$fails[] = 'phone_e164() = "' . $cd::phone_e164() . '", oczekiwano "+48669189992"';
}
if ( 'tel:+48669189992' !== $cd::phone_href() ) {
	$fails[] = 'phone_href() = "' . $cd::phone_href() . '"';
}
if ( 'mailto:zbor@kzmielec.pl' !== $cd::email_href() ) {
	$fails[] = 'email_href() = "' . $cd::email_href() . '"';
}

// ── nieznany klucz nie wywala sie i nie klamie ─────────────────────────────
if ( '' !== $cd::get( 'nie-ma-takiego-pola' ) ) {
	$fails[] = 'nieznany klucz zwraca "' . $cd::get( 'nie-ma-takiego-pola' ) . '", oczekiwano pustki';
}

// ── linie skladane z danych i etykiet ──────────────────────────────────────
$cb = '\Kzmielec\Contact\ContactBindings';

if ( ! class_exists( $cb ) ) {
	$fails[] = "brak klasy $cb";
} else {
	update_option(
		$cd::OPTION,
		array(
			'street'   => 'Testowa 9',
			'postcode' => '00-001',
			'city'     => 'Testowo',
			'phone'    => '111 222 333',
			'nip'      => '000-00-00-000',
			'email'    => 'test@example.test',
			'iban'     => '11 2222 3333 4444 5555 6666 7777',
		)
	);

	$addr = (string) $cb::line( 'address' );
	if ( false === strpos( $addr, 'Testowa 9' ) || false === strpos( $addr, '00-001 Testowo' ) ) {
		$fails[] = 'linia adresu nie zawiera danych z opcji: "' . $addr . '"';
	}

	$mail = (string) $cb::line( 'email' );
	if ( false === strpos( $mail, 'mailto:test@example.test' ) ) {
		$fails[] = 'linia e-maila nie zawiera odnosnika mailto: "' . $mail . '"';
	}

	$tel = (string) $cb::line( 'phone' );
	if ( false === strpos( $tel, '111 222 333' ) || 2 !== substr_count( $tel, '<br>' ) ) {
		$fails[] = 'linia telefonu: oczekiwano numeru i dwoch <br>, jest "' . $tel . '"';
	}

	if ( false === strpos( (string) $cb::line( 'nip' ), '000-00-00-000' ) ) {
		$fails[] = 'linia NIP nie zawiera danych z opcji';
	}
	if ( false === strpos( (string) $cb::line( 'iban' ), '11 2222 3333 4444 5555 6666 7777' ) ) {
		$fails[] = 'linia konta nie zawiera danych z opcji';
	}

	if ( null !== $cb::line( 'nie-ma-takiego-klucza' ) ) {
		$fails[] = 'nieznany klucz musi zwrocic null, zeby rdzen zostawil tekst zapasowy';
	}

	if ( ! get_block_bindings_source( $cb::SOURCE ) ) {
		$fails[] = 'zrodlo ' . $cb::SOURCE . ' nie jest zarejestrowane w rdzeniu';
	}
}

// ── JEDNO ZRODLO: zmiana w opcji musi dojsc do WSZYSTKICH czterech stron ───
// To jedyny dowod, ze zrodlo jest jedno. Odczyt gotowej strony niczego nie dowodzi,
// bo przed ta praca wygladala poprawnie, mimo ze kazda wersja miala wlasna kopie
// danych — i kazda z nich niosla martwy adres zbor@kzmielec.ddev.site.
if ( function_exists( 'pll_get_post' ) ) {
	$pages = array( 131 );
	foreach ( array( 'en', 'uk', 'es' ) as $l ) {
		$t = pll_get_post( 131, $l );
		if ( $t ) {
			$pages[] = (int) $t;
		}
	}

	$sentinel = 'znacznik-testowy-' . 'kzt@example.test';
	update_option(
		$cd::OPTION,
		array_merge(
			$cd::DEFAULTS,
			array(
				'email'    => $sentinel,
				'latitude' => '11.222333',
			)
		)
	);

	foreach ( $pages as $page_id ) {
		$html = apply_filters( 'the_content', (string) get_post_field( 'post_content', $page_id ) );

		if ( false === strpos( $html, $sentinel ) ) {
			$fails[] = "wpis #$page_id nie czyta e-maila ze wspolnego zrodla";
		}
		if ( false === strpos( $html, '11.222333' ) ) {
			$fails[] = "wpis #$page_id nie czyta wspolrzednych mapy ze wspolnego zrodla";
		}
	}

	// ── slady zjedzonych ukosnikow: `u015b` tam, gdzie ma byc `ś` ──────────
	// Pilnuje bledu, ktory sam popelnilem: wp_json_encode() eskejpuje znaki spoza
	// ASCII, a wp_update_post() odslania tresc, wiec ukosnik przepada i w bazie
	// zostaje goly `u015b`.
	foreach ( $pages as $page_id ) {
		$raw = (string) get_post_field( 'post_content', $page_id );

		if ( preg_match( '/u[0-9a-f]{4}/i', $raw, $m ) ) {
			$fails[] = "wpis #$page_id nosi slad zjedzonego ukosnika: '" . $m[0] . "'";
		}
	}
}

// ── przywroc stan ──────────────────────────────────────────────────────────
if ( is_array( $before ) && $before ) {
	update_option( $cd::OPTION, $before );
} else {
	delete_option( $cd::OPTION );
}

if ( $fails ) {
	echo "FAIL\n";
	foreach ( $fails as $f ) {
		echo "  - $f\n";
	}
	exit( 1 );
}

echo "PASS: dane kontaktowe czytane z jednego zrodla, z wartosciami domyslnymi\n";
