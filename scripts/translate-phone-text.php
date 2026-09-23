<?php
/**
 * Enters the en, uk and es translations of the text around the phone number.
 *
 * Dry run by default; writes only with `go`:
 *
 *   ddev wp eval-file - < scripts/translate-phone-text.php
 *   ddev wp eval-file - go < scripts/translate-phone-text.php
 *
 * The text is the "Tekst przy telefonie" field of the contact settings; the other
 * languages read it from Languages -> Translations, and this fills those entries.
 * The sentences are the hand-checked translations the theme catalogue held before the
 * text became a setting, plus "prezb." (presbyter). Polylang keys a translation by
 * its source, so this only helps while the field holds exactly the text below.
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs the file through eval(),
// where a declare cannot appear.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$kz_source = "tel.: {telefon} – pastor Zboru, prezb. Dariusz R. Hapoń\nUwaga: z tego numeru nie odczytujemy smsów.\nW celu kontaktu pisemnego prosimy użyć poczty email lub kontaktu ze Zborem poprzez messenger (facebook).";

/**
 * Language slug => translation.
 *
 * @var array<string, string>
 */
$kz_translations = array(
	'en' => "Tel.: {telefon} – Congregation Pastor, Presbyter Dariusz R. Hapoń\nPlease note: we do not read text messages sent to this number.\nTo contact us in writing, please use email or contact the Congregation via Messenger (Facebook).",
	'uk' => "тел.: {telefon} — пастор громади, пресвітер Даріуш Р. Гапонь\nУвага: з цього номера ми не читаємо SMS-повідомлення.\nДля письмового зв’язку просимо скористатися електронною поштою або зв’язатися з громадою через месенджер (Facebook).",
	'es' => "Tel.: {telefon} – pastor de la congregación, presbítero Dariusz R. Hapoń\nNota: no leemos los mensajes de texto en este número.\nPara ponerse en contacto por escrito, utilice el correo electrónico o póngase en contacto con la congregación a través de Messenger (Facebook).",
);

$kz_go = in_array( 'go', $args, true );

if ( ! function_exists( 'PLL' ) || ! class_exists( 'PLL_MO' ) || ! class_exists( '\Kzmielec\Contact\ContactData' ) ) {
	WP_CLI::error( 'Polylang or the kzmielec theme is not active.' );
}

$kz_current = \Kzmielec\Contact\ContactData::get( 'phone_text' );
if ( $kz_current !== $kz_source ) {
	WP_CLI::warning( 'The settings hold a different phone text, so these translations would not be used:' );
	WP_CLI::log( $kz_current );
	WP_CLI::error( 'Nothing written. Translate it in Languages -> Translations instead.' );
}

foreach ( $kz_translations as $kz_slug => $kz_target ) {
	$kz_language = PLL()->model->get_language( $kz_slug );
	if ( ! $kz_language ) {
		WP_CLI::warning( sprintf( 'No language %s, skipped.', $kz_slug ) );
		continue;
	}

	$kz_mo = new PLL_MO();
	$kz_mo->import_from_db( $kz_language );
	$kz_before = $kz_mo->translate( $kz_source );

	WP_CLI::log( sprintf( '%s: %s', $kz_slug, $kz_before === $kz_target ? 'already set' : 'will set' ) );

	if ( $kz_go ) {
		$kz_mo->add_entry( $kz_mo->make_entry( $kz_source, $kz_target ) );
		$kz_mo->export_to_db( $kz_language );
	}
}

if ( ! $kz_go ) {
	WP_CLI::success( 'Dry run, nothing written. Add `go` to write.' );
	return;
}

// The language objects cache the string translations, and the text is on every page.
PLL()->model->clean_languages_cache();
wp_cache_flush();
do_action( 'litespeed_purge_all' );

WP_CLI::success( 'Phone text translated.' );
