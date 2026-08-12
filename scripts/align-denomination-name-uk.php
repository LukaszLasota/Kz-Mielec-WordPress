<?php
/**
 * Aligns the Ukrainian name of the congregation's own denomination.
 *
 * Run with:
 *   ddev wp eval-file scripts/align-denomination-name-uk.php --path=/var/www/html          (dry run)
 *   ddev wp eval-file scripts/align-denomination-name-uk.php --path=/var/www/html -- go    (writes)
 *
 * WHY. The comparison table names the two denominations in every one of its 37
 * topics. DeepL rendered "Kościół Zielonoświątkowy" in Ukrainian as
 * «Церква Християн Віри Євангельської» — which is not a translation but the actual
 * name of a specific Ukrainian denomination (ЦХВЄ). Two problems follow from that.
 *
 * The column describes the teaching of the POLISH Pentecostal Church, so naming a
 * Ukrainian church body invites a Ukrainian reader to think these are the positions
 * of their own denomination. And the site already calls itself
 * «Пентекостальна церква» in its title and in the language switcher, so the same
 * body appeared under two different names on the same page.
 *
 * The Roman Catholic column needs no change: «Римо-Католицька Церква» is both a
 * translation and the name that church uses in Ukrainian.
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs this through eval(), where a
// declare would have to be the first statement of the script and so cannot appear.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$kz_go = in_array( 'go', (array) $args, true );

$kz_from = 'Церква Християн Віри Євангельської';
$kz_to   = 'Пентекостальна церква';

$kz_ids = get_posts(
	array(
		'post_type'      => 'comparison_topic',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'post_status'    => 'publish',
		'lang'           => 'uk',
	)
);

$kz_changed = 0;

foreach ( $kz_ids as $kz_id ) {
	if ( function_exists( 'pll_get_post_language' ) && pll_get_post_language( $kz_id ) !== 'uk' ) {
		continue;
	}

	$kz_churches = get_post_meta( $kz_id, 'churches', true );

	if ( ! is_array( $kz_churches ) ) {
		continue;
	}

	$kz_touched = false;

	foreach ( $kz_churches as $kz_i => $kz_church ) {
		if ( ! isset( $kz_church['church_name'] ) || trim( (string) $kz_church['church_name'] ) !== $kz_from ) {
			continue;
		}

		$kz_churches[ $kz_i ]['church_name'] = $kz_to;
		$kz_touched                          = true;
	}

	if ( ! $kz_touched ) {
		continue;
	}

	++$kz_changed;

	if ( $kz_go ) {
		update_post_meta( $kz_id, 'churches', $kz_churches );
	}
}

WP_CLI::log( sprintf( 'tematow do zmiany: %d (z %d ukrainskich)', $kz_changed, count( $kz_ids ) ) );
WP_CLI::log( sprintf( '  z: %s', $kz_from ) );
WP_CLI::log( sprintf( '  na: %s', $kz_to ) );

if ( $kz_go ) {
	/*
	 * The accordion caches its rendered HTML per language, so the old name would
	 * keep showing until the transients expired on their own.
	 */
	if ( class_exists( '\ComparisonOfReligions\Cache\AccordionCache' ) ) {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cor_accordion_%' OR option_name LIKE '_transient_timeout_cor_accordion_%'" );
	}

	WP_CLI::success( 'Zapisane, cache akordeonu wyczyszczony.' );
} else {
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
}
