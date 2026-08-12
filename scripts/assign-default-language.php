<?php
/**
 * Assigns the default language to content that has none, using Polylang's own mechanism.
 *
 * Run with:
 *   ddev wp eval-file scripts/assign-default-language.php          (dry run)
 *   ddev wp eval-file scripts/assign-default-language.php -- go    (writes)
 *
 * This was the last step of the deployment that could not be repeated without a human.
 * `setup-polylang-languages.php` creates the languages and says so in its own header:
 * assigning existing content is Polylang's business, done by clicking a notice in the
 * admin. The dress rehearsal on a copy of the production database is what made the gap
 * visible — a reproduction path is only as reproducible as its least scriptable step.
 *
 * It is not done by hand here either. `PLL()->model->set_language_in_mass()` is the very
 * method Polylang's own admin notice calls, so posts, pages, custom types AND taxonomy
 * terms are handled the way the plugin handles them, including its recursion past the
 * thousand-object batch. Guessing a language is not involved: every object without one
 * gets the default, which on this site is Polish, and on a fresh production database
 * everything is Polish.
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs this through eval(), where a declare
// would have to be the first statement of the script and so cannot appear.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$kz_go = in_array( 'go', (array) $args, true );

if ( ! function_exists( 'PLL' ) || ! PLL() instanceof PLL_Base ) {
	WP_CLI::error( 'Polylang nieaktywny — najpierw krok 5 runbooka.' );
}

$kz_model = PLL()->model;

if ( ! method_exists( $kz_model, 'set_language_in_mass' ) || ! method_exists( $kz_model, 'get_objects_with_no_lang' ) ) {
	WP_CLI::error( 'Ta wersja Polylanga nie ma set_language_in_mass() — sprawdz API przed dalszym krokiem.' );
}

$kz_default = $kz_model->languages->get_default();

if ( empty( $kz_default ) ) {
	WP_CLI::error( 'Brak jezyka domyslnego — najpierw setup-polylang-languages.php -- go.' );
}

WP_CLI::log( sprintf( 'jezyk domyslny: %s (%s)', $kz_default->slug, $kz_default->locale ) );

/*
 * The count is read before and after so the report says what happened rather than that
 * something happened. `get_objects_with_no_lang()` answers with one array per type.
 */
$kz_before = (array) $kz_model->get_objects_with_no_lang( 1000 );
$kz_total  = 0;

foreach ( $kz_before as $kz_type => $kz_ids ) {
	$kz_count = is_array( $kz_ids ) ? count( $kz_ids ) : 0;

	if ( 0 === $kz_count ) {
		continue;
	}

	$kz_total += $kz_count;

	WP_CLI::log( sprintf( '  bez jezyka: %-14s %d', $kz_type, $kz_count ) );
}

if ( 0 === $kz_total ) {
	WP_CLI::success( 'Nic do przypisania — cala tresc ma juz jezyk.' );
	return;
}

if ( ! $kz_go ) {
	WP_CLI::log( sprintf( 'razem do przypisania: %d', $kz_total ) );
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
	return;
}

$kz_model->set_language_in_mass();

$kz_after = (array) $kz_model->get_objects_with_no_lang( 1000 );
$kz_left  = 0;

foreach ( $kz_after as $kz_ids ) {
	$kz_left += is_array( $kz_ids ) ? count( $kz_ids ) : 0;
}

$kz_model->clean_languages_cache();
wp_cache_flush();

WP_CLI::log( sprintf( 'przypisanych: %d, bez jezyka nadal: %d', $kz_total - $kz_left, $kz_left ) );

if ( $kz_left > 0 ) {
	WP_CLI::warning( 'Czesc tresci nadal bez jezyka — obejrzyj, zanim uznasz krok za zamkniety.' );
} else {
	WP_CLI::success( 'Zapisane. Cala tresc ma jezyk domyslny.' );
}
