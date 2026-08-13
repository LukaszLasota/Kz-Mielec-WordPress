<?php
/**
 * Corrects one stray capital letter in the visible heading of the home page.
 *
 * Production carried `<h2 id="two">Zaplanuj wizytĘ</h2>` - a capital E-ogonek at
 * the end of a lower-case word. Nothing applies `text-transform` to that
 * heading, so a visitor read it exactly as Google did, and Google quoted it in
 * the search result for the home page.
 *
 * Only the Polish content was affected. The menu links were already correct, and
 * the three translations say "Plan your visit", "Заплануйте візит" and
 * "Planifica tu visita".
 *
 * Why a script for one character: the same fix run by hand from /tmp is how the
 * repository and the server drifted apart once already. This is keyed to the
 * string, never to a post id - production and the local database do not number
 * their posts the same way.
 *
 * Comparison is byte-for-byte on purpose. MySQL's default collation treats
 * `Ę` and `ę` as the same letter, so a plain LIKE reports a match on every page
 * that spells the word correctly.
 *
 * Dry run unless the last argument is `go`:
 *
 *     wp eval-file scripts/fix-visit-heading-case.php
 *     wp eval-file scripts/fix-visit-heading-case.php go
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs this through eval(), where a
// declare is no longer the first statement and PHP refuses to compile the file.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$kz_wrong = 'wizyt' . "\xC4\x98"; // wizytE with the capital ogonek.
$kz_right = 'wizyt' . "\xC4\x99"; // wizyte with the small one.

$kz_go = in_array( 'go', $args, true );

global $wpdb;

/*
 * LIKE BINARY finds the capital form only. Revisions are read as well but never
 * written: they are the record of what the page used to say, and rewriting
 * history to hide a typo would remove the evidence of when it appeared.
 */
$kz_rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT ID, post_title, post_type, post_status FROM {$wpdb->posts} WHERE post_content LIKE BINARY %s",
		'%' . $wpdb->esc_like( $kz_wrong ) . '%'
	)
);

$kz_written  = 0;
$kz_revision = 0;

foreach ( $kz_rows as $kz_row ) {
	if ( 'revision' === $kz_row->post_type ) {
		++$kz_revision;
		continue;
	}

	$kz_content = (string) get_post_field( 'post_content', $kz_row->ID );
	$kz_count   = substr_count( $kz_content, $kz_wrong );
	$kz_lang    = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $kz_row->ID ) : 'pl';

	WP_CLI::line(
		sprintf(
			'  %-6s id=%-5d %-4s %-14s %s (%d occurrence%s)',
			$kz_go ? 'write' : 'would',
			$kz_row->ID,
			'' !== $kz_lang ? $kz_lang : '-',
			$kz_row->post_type,
			$kz_row->post_title,
			$kz_count,
			1 === $kz_count ? '' : 's'
		)
	);

	if ( ! $kz_go ) {
		continue;
	}

	$kz_result = wp_update_post(
		array(
			'ID'           => $kz_row->ID,
			// Slashed, because wp_update_post() unslashes what it is given.
			'post_content' => wp_slash( str_replace( $kz_wrong, $kz_right, $kz_content ) ),
		),
		true
	);

	if ( is_wp_error( $kz_result ) ) {
		WP_CLI::warning( sprintf( 'id=%d not saved: %s', $kz_row->ID, $kz_result->get_error_message() ) );
		continue;
	}

	++$kz_written;
}

WP_CLI::line( '' );
WP_CLI::line(
	sprintf(
		'live posts affected: %d, written: %d, revisions left untouched: %d',
		count( $kz_rows ) - $kz_revision,
		$kz_written,
		$kz_revision
	)
);

if ( ! $kz_go ) {
	WP_CLI::line( '' );
	WP_CLI::line( 'Dry run. Add `go` as the last argument to write.' );
	return;
}

// Read the heading back out of the database rather than trusting the write.
foreach ( $kz_rows as $kz_row ) {
	if ( 'revision' === $kz_row->post_type ) {
		continue;
	}

	$kz_fresh = (string) get_post_field( 'post_content', $kz_row->ID );

	if ( 1 === preg_match( '/<h2[^>]*id="two"[^>]*>([^<]*)<\/h2>/', $kz_fresh, $kz_match ) ) {
		WP_CLI::line( sprintf( '  id=%d heading now reads: "%s"', $kz_row->ID, $kz_match[1] ) );
	}

	if ( false !== strpos( $kz_fresh, $kz_wrong ) ) {
		WP_CLI::warning( sprintf( 'id=%d still contains the capital form', $kz_row->ID ) );
	}
}

WP_CLI::success( 'Heading corrected.' );
