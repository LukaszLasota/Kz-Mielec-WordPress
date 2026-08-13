<?php
/**
 * Fill the structured meeting schedule from the Polish free-text field.
 *
 * `_meeting_day_hour` used to be typed by hand, once per language. It is now
 * derived from a weekday number and an `HH:MM` time held on the Polish post, so
 * the existing Polish text has to be read back into that pair exactly once.
 *
 * Only the Polish posts are parsed. The English "Sunday 10.30 am", the Spanish
 * "Viernes a las 18:00" and the Ukrainian "Неділя, 10:30" are deliberately not
 * parsed at all: four wordings, three separators and a twelve-hour clock among
 * them is precisely the mess being removed, and the Polish original says the
 * same thing in one shape.
 *
 * Dry run unless the last argument is `go`:
 *
 *     wp eval-file scripts/backfill-meeting-schedule.php
 *     wp eval-file scripts/backfill-meeting-schedule.php go
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs this through eval(), where a
// declare is no longer the first statement and PHP refuses to compile the file.

use CustomBlockPackage\Services\MeetingSchedule;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

if ( ! class_exists( MeetingSchedule::class ) ) {
	WP_CLI::error( 'custom-block-package is not active, so there is nothing to write to.' );
}

/**
 * Polish weekday names as they appear in the field, mapped to ISO-8601 numbers.
 *
 * Lowercased before lookup, so "Niedziela" and "niedziela" both resolve.
 */
$kz_days = array(
	'poniedzialek' => 1,
	'poniedziałek' => 1,
	'wtorek'       => 2,
	'sroda'        => 3,
	'środa'        => 3,
	'czwartek'     => 4,
	'piatek'       => 5,
	'piątek'       => 5,
	'sobota'       => 6,
	'niedziela'    => 7,
);

$kz_go = in_array( 'go', $args, true );

$kz_meetings = get_posts(
	array(
		'post_type'        => 'meetings',
		'post_status'      => 'any',
		'numberposts'      => -1,
		'suppress_filters' => true,
	)
);

$kz_written = 0;
$kz_skipped = 0;
$kz_problem = array();

foreach ( $kz_meetings as $kz_meeting ) {
	$kz_lang = function_exists( 'pll_get_post_language' )
		? (string) pll_get_post_language( $kz_meeting->ID )
		: 'pl';

	if ( 'pl' !== $kz_lang && '' !== $kz_lang ) {
		continue;
	}

	$kz_text = trim( (string) get_post_meta( $kz_meeting->ID, '_meeting_day_hour', true ) );

	if ( '' === $kz_text ) {
		WP_CLI::line( sprintf( '  skip   %-34s no day or hour recorded', $kz_meeting->post_title ) );
		++$kz_skipped;
		continue;
	}

	$kz_weekday = 0;

	foreach ( $kz_days as $kz_name => $kz_number ) {
		if ( false !== mb_stripos( $kz_text, $kz_name ) ) {
			$kz_weekday = $kz_number;
			break;
		}
	}

	$kz_time = '';

	if ( 1 === preg_match( '/(\d{1,2})[:.](\d{2})/', $kz_text, $kz_match ) ) {
		$kz_time = sprintf( '%02d:%s', (int) $kz_match[1], $kz_match[2] );
	}

	if ( 0 === $kz_weekday || '' === $kz_time ) {
		$kz_problem[] = sprintf( '%s ("%s")', $kz_meeting->post_title, $kz_text );
		continue;
	}

	WP_CLI::line(
		sprintf(
			'  %-6s %-34s "%s" -> weekday %d, %s',
			$kz_go ? 'write' : 'would',
			$kz_meeting->post_title,
			$kz_text,
			$kz_weekday,
			$kz_time
		)
	);

	if ( $kz_go ) {
		update_post_meta( $kz_meeting->ID, MeetingSchedule::META_WEEKDAY, $kz_weekday );
		update_post_meta( $kz_meeting->ID, MeetingSchedule::META_TIME, $kz_time );
		MeetingSchedule::refresh_index( $kz_meeting->ID );
	}

	++$kz_written;
}

WP_CLI::line( '' );
WP_CLI::line( sprintf( 'parsed: %d, without a fixed slot: %d, unreadable: %d', $kz_written, $kz_skipped, count( $kz_problem ) ) );

foreach ( $kz_problem as $kz_line ) {
	WP_CLI::warning( 'could not read a weekday and an hour from: ' . $kz_line );
}

if ( ! $kz_go ) {
	WP_CLI::line( '' );
	WP_CLI::line( 'Dry run. Add `go` as the last argument to write.' );
	return;
}

/*
 * Print what every language now shows, because that is the part a reader can
 * check against the live page: the pair itself is only meaningful once it has
 * been turned back into a label.
 */
WP_CLI::line( '' );
WP_CLI::line( 'labels after the write:' );

foreach ( $kz_meetings as $kz_meeting ) {
	$kz_lang  = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $kz_meeting->ID ) : 'pl';
	$kz_index = (string) get_post_meta( $kz_meeting->ID, '_meeting_day_hour', true );

	WP_CLI::line(
		sprintf(
			'  %-3s %-36s %s',
			'' !== $kz_lang ? $kz_lang : '?',
			$kz_meeting->post_title,
			'' !== $kz_index ? '"' . $kz_index . '"' : '(none)'
		)
	);
}

WP_CLI::success( 'Schedule backfilled.' );
