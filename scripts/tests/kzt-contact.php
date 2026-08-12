<?php
/**
 * Contact data: one source. The assertion that matters — a missing field yields a default
 * rather than a blank, because an empty line on four pages is worse than a stale one.
 */
$fails = array();
$cd    = '\Kzmielec\Contact\ContactData';

if ( ! class_exists( $cd ) ) {
	echo "FAIL\n  - missing class $cd\n";
	exit( 1 );
}

$before = get_option( $cd::OPTION, array() );

// ── defaults when the option is absent ─────────────────────────────────────
delete_option( $cd::OPTION );

if ( 'Przemysłowa 2' !== $cd::get( 'street' ) ) {
	$fails[] = 'no option: street does not fall back to its default, got "' . $cd::get( 'street' ) . '"';
}
if ( 'zbor@kzmielec.pl' !== $cd::get( 'email' ) ) {
	$fails[] = 'no option: email does not fall back to its default, got "' . $cd::get( 'email' ) . '"';
}
if ( 9 !== count( $cd::all() ) ) {
	$fails[] = 'all() returns ' . count( $cd::all() ) . ' fields, expected 9';
}
if ( '50.299071' !== $cd::get( 'latitude' ) || '21.4483254' !== $cd::get( 'longitude' ) ) {
	$fails[] = 'the map coordinates do not fall back to their defaults: ' . $cd::get( 'latitude' ) . ', ' . $cd::get( 'longitude' );
}

// ── an empty field also falls back to the default ──────────────────────────
update_option( $cd::OPTION, array( 'email' => '   ' ) );

if ( 'zbor@kzmielec.pl' !== $cd::get( 'email' ) ) {
	$fails[] = 'an empty field does not fall back to the default, got "' . $cd::get( 'email' ) . '"';
}

// ── the stored value wins, and surrounding spaces are trimmed ──────────────
update_option( $cd::OPTION, array( 'email' => ' inny@example.test ' ) );

if ( 'inny@example.test' !== $cd::get( 'email' ) ) {
	$fails[] = 'the option does not win, or spaces are not trimmed, got "' . $cd::get( 'email' ) . '"';
}

// ── machine-readable formats ───────────────────────────────────────────────
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

// ── an unknown key neither throws nor lies ─────────────────────────────────
if ( '' !== $cd::get( 'nie-ma-takiego-pola' ) ) {
	$fails[] = 'an unknown key returns "' . $cd::get( 'nie-ma-takiego-pola' ) . '", expected an empty string';
}

// ── lines composed from data and labels ────────────────────────────────────
$cb = '\Kzmielec\Contact\ContactBindings';

if ( ! class_exists( $cb ) ) {
	$fails[] = "missing class $cb";
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
		$fails[] = 'the address line does not carry the stored data: "' . $addr . '"';
	}

	$mail = (string) $cb::line( 'email' );
	if ( false === strpos( $mail, 'mailto:test@example.test' ) ) {
		$fails[] = 'the e-mail line carries no mailto link: "' . $mail . '"';
	}

	$tel = (string) $cb::line( 'phone' );
	if ( false === strpos( $tel, '111 222 333' ) || 2 !== substr_count( $tel, '<br>' ) ) {
		$fails[] = 'phone line: expected the number and two <br>, got "' . $tel . '"';
	}

	if ( false === strpos( (string) $cb::line( 'nip' ), '000-00-00-000' ) ) {
		$fails[] = 'the tax-number line does not carry the stored data';
	}
	if ( false === strpos( (string) $cb::line( 'iban' ), '11 2222 3333 4444 5555 6666 7777' ) ) {
		$fails[] = 'the account line does not carry the stored data';
	}

	if ( null !== $cb::line( 'nie-ma-takiego-klucza' ) ) {
		$fails[] = 'an unknown key must return null so core keeps the fallback text';
	}

	if ( ! get_block_bindings_source( $cb::SOURCE ) ) {
		$fails[] = 'zrodlo ' . $cb::SOURCE . ' is not registered with core';
	}
}

// ── ONE SOURCE: a change in the option must reach ALL four pages ───────────
// This is the only proof that the source is single. Reading a rendered page proves
// nothing: before this work every page looked right while each version held its own copy
// of the data — and every copy carried the dead address zbor@kzmielec.ddev.site.
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
			$fails[] = "post #$page_id does not read the e-mail from the shared source";
		}
		if ( false === strpos( $html, '11.222333' ) ) {
			$fails[] = "post #$page_id does not read the map coordinates from the shared source";
		}
	}

	// ── traces of swallowed backslashes: `u015b` where `ś` belongs ──────────
	// Guards a mistake made in this project: wp_json_encode() escapes non-ASCII
	// characters and wp_update_post() unslashes what it is given, so the backslash is
	// lost and the database keeps a bare `u015b`.
	foreach ( $pages as $page_id ) {
		$raw = (string) get_post_field( 'post_content', $page_id );

		if ( preg_match( '/u[0-9a-f]{4}/i', $raw, $m ) ) {
			$fails[] = "post #$page_id carries a swallowed backslash: '" . $m[0] . "'";
		}
	}
}

// ── restore the previous state ─────────────────────────────────────────────
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

echo "PASS: contact data read from one source, with working defaults\n";
