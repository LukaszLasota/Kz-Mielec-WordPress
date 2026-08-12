<?php
/**
 * Translates the site title into en, uk and es.
 *
 * Run with: ddev wp eval-file scripts/translate-site-title.php --path=/var/www/html
 *
 * One option covers every visible occurrence. `blogname` feeds the logo's alt text
 * (header.php), the footer copyright line (footer.php), the title tag suffix, the
 * Yoast JSON-LD and the feed titles — nine occurrences per foreign page, all of
 * them Polish before this ran.
 *
 * The strings are hand-written, not machine-translated. DeepL renders
 * "Zielonoświątkowy" unreliably and has no way of knowing that "Zbór" is the local
 * congregation of a denomination rather than a word for a gathering. The Ukrainian
 * uses "громада", which is what Ukrainian-speaking Protestants call a local
 * congregation; "збір" would read as "a collection".
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)` here on purpose: `wp eval-file` runs the file
// through eval(), where a declare must be the first statement of the script and
// therefore cannot appear at all.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

/**
 * Source string => language slug => translation.
 *
 * @var array<string, array<string, string>>
 */
$tlumaczenia = array(
	'Kościół Zielonoświątkowy Zbór w Mielcu' => array(
		'en' => 'Pentecostal Church – Mielec Congregation',
		'uk' => 'Пентекостальна церква – громада в Мельці',
		'es' => 'Iglesia Pentecostal – Congregación de Mielec',
	),
);

if ( ! function_exists( 'PLL' ) || ! class_exists( 'PLL_MO' ) ) {
	WP_CLI::error( 'Polylang nie jest aktywny.' );
}

$biezaca = (string) get_option( 'blogname' );

foreach ( array_keys( $tlumaczenia ) as $zrodlo ) {
	if ( $zrodlo !== $biezaca ) {
		WP_CLI::warning( sprintf( 'blogname to "%s", a skrypt tlumaczy "%s" — sprawdz.', $biezaca, $zrodlo ) );
	}
}

foreach ( array( 'en', 'uk', 'es' ) as $slug ) {
	$jezyk = PLL()->model->get_language( $slug );

	if ( ! $jezyk ) {
		WP_CLI::warning( sprintf( 'Brak jezyka %s — pomijam.', $slug ) );
		continue;
	}

	$mo = new PLL_MO();
	$mo->import_from_db( $jezyk );

	foreach ( $tlumaczenia as $zrodlo => $cele ) {
		if ( ! isset( $cele[ $slug ] ) ) {
			continue;
		}

		$mo->add_entry( $mo->make_entry( $zrodlo, $cele[ $slug ] ) );
	}

	$mo->export_to_db( $jezyk );

	WP_CLI::log( sprintf( '%s: %s', $slug, $tlumaczenia[ $biezaca ][ $slug ] ?? '—' ) );
}

// The string translations live in a post per language; the language objects cache
// them, so without this the front end keeps serving the Polish title.
PLL()->model->clean_languages_cache();
wp_cache_flush();

WP_CLI::success( 'Nazwa witryny przetlumaczona.' );
