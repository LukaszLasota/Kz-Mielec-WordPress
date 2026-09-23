<?php
/**
 * Enters the en, uk and es translations of the Instagram feed's custom bio.
 *
 * Dry run by default; writes only with `go`:
 *
 *   ddev wp eval-file - < scripts/translate-instagram-bio.php
 *   ddev wp eval-file - go < scripts/translate-instagram-bio.php
 *
 * The theme's InstagramBio class swaps the bio on the translated pages for the
 * entry in Languages -> Translations; this fills that entry. Hand-written, and
 * "zbor" follows the glossary of kzmielec-translate: congregation / громада /
 * congregacion. It can be corrected in the panel afterwards.
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs the file through eval(),
// where a declare cannot appear.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

/**
 * Source string => language slug => translation.
 *
 * @var array<string, array<string, string>>
 */
$kz_translations = array(
	'Instagram zboru' => array(
		'en' => 'The congregation on Instagram',
		'uk' => 'Громада в Instagram',
		'es' => 'La congregación en Instagram',
	),
);

$kz_go = in_array( 'go', $args, true );

if ( ! function_exists( 'PLL' ) || ! class_exists( 'PLL_MO' ) ) {
	WP_CLI::error( 'Polylang is not active.' );
}

global $wpdb;
$kz_bios = array();
foreach ( (array) $wpdb->get_col( "SELECT settings FROM {$wpdb->prefix}sbi_feeds" ) as $kz_json ) {
	$kz_settings = json_decode( (string) $kz_json, true );
	if ( is_array( $kz_settings ) && ! empty( $kz_settings['custombio'] ) ) {
		$kz_bios[] = trim( (string) $kz_settings['custombio'] );
	}
}

foreach ( array_keys( $kz_translations ) as $kz_source ) {
	if ( ! in_array( $kz_source, $kz_bios, true ) ) {
		WP_CLI::warning( sprintf( 'No feed has the bio "%s" (found: %s). Its translation is entered anyway.', $kz_source, $kz_bios ? implode( ', ', $kz_bios ) : 'none' ) );
	}
}

foreach ( array( 'en', 'uk', 'es' ) as $kz_slug ) {
	$kz_language = PLL()->model->get_language( $kz_slug );
	if ( ! $kz_language ) {
		WP_CLI::warning( sprintf( 'No language %s, skipped.', $kz_slug ) );
		continue;
	}

	$kz_mo = new PLL_MO();
	$kz_mo->import_from_db( $kz_language );

	foreach ( $kz_translations as $kz_source => $kz_targets ) {
		if ( ! isset( $kz_targets[ $kz_slug ] ) ) {
			continue;
		}

		$kz_current = $kz_mo->translate( $kz_source );
		WP_CLI::log( sprintf( '%s: "%s" -> "%s"%s', $kz_slug, $kz_current, $kz_targets[ $kz_slug ], $kz_current === $kz_targets[ $kz_slug ] ? ' (already set)' : '' ) );
		$kz_mo->add_entry( $kz_mo->make_entry( $kz_source, $kz_targets[ $kz_slug ] ) );
	}

	if ( $kz_go ) {
		$kz_mo->export_to_db( $kz_language );
	}
}

if ( ! $kz_go ) {
	WP_CLI::success( 'Dry run, nothing written. Add `go` to write.' );
	return;
}

// The language objects cache the string translations.
PLL()->model->clean_languages_cache();
wp_cache_flush();

WP_CLI::success( 'Instagram bio translated.' );
