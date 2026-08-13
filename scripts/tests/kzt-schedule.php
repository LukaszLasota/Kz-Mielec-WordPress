<?php
/**
 * The meeting schedule: one stored pair, a label for people and dates for Google.
 * The assertion that matters: every generated occurrence really falls on the
 * stored weekday, at the stored time, in the future.
 */
$fails = array();
$ms_c  = '\CustomBlockPackage\Services\MeetingSchedule';

if ( ! class_exists( $ms_c ) ) {
	echo "FAIL\n  - brak klasy $ms_c\n";
	exit( 1 );
}

$post_id = wp_insert_post(
	array(
		'post_type'   => 'meetings',
		'post_status' => 'publish',
		'post_title'  => 'Nabożeństwo testowe',
	)
);

// ── no schedule stored ────────────────────────────────────────────────────
if ( '' !== $ms_c::label( (int) $post_id ) ) {
	$fails[] = 'label() invented a label for a meeting with no stored slot';
}
if ( array() !== $ms_c::occurrences( (int) $post_id ) ) {
	$fails[] = 'occurrences() invented dates for a meeting with no stored slot';
}

// ── a nonsense time must be refused, not guessed at ───────────────────────
update_post_meta( $post_id, $ms_c::META_WEEKDAY, 7 );
update_post_meta( $post_id, $ms_c::META_TIME, '25:99' );

if ( null !== $ms_c::schedule( (int) $post_id ) ) {
	$fails[] = 'schedule() accepted 25:99 as a time';
}

// ── the real thing: Sunday 10:30 ──────────────────────────────────────────
update_post_meta( $post_id, $ms_c::META_TIME, '10:30' );

$label = $ms_c::label( (int) $post_id );

// The Polish catalogue is the source, so the source strings come back as-is.
if ( 'Niedziela 10:30' !== $label ) {
	$fails[] = 'label() gave "' . $label . '" instead of "Niedziela 10:30"';
}

$dates = $ms_c::occurrences( (int) $post_id );

if ( count( $dates ) !== $ms_c::OCCURRENCES ) {
	$fails[] = 'occurrences() returned ' . count( $dates ) . ' dates, expected ' . $ms_c::OCCURRENCES;
}

$now      = new DateTimeImmutable( 'now', wp_timezone() );
$previous = null;

foreach ( $dates as $iso ) {
	$date = date_create_immutable( $iso );

	if ( false === $date ) {
		$fails[] = 'occurrences() returned an unparseable date: ' . $iso;
		continue;
	}

	if ( '7' !== $date->format( 'N' ) ) {
		$fails[] = $iso . ' is not a Sunday';
	}
	if ( '10:30' !== $date->format( 'H:i' ) ) {
		// Wall-clock time has to survive the March and October transitions,
		// which adding 604800 seconds to a timestamp does not.
		$fails[] = $iso . ' is not at 10:30 local time';
	}
	if ( $date <= $now ) {
		$fails[] = $iso . ' is in the past — a crawler would see a stale event';
	}
	if ( null !== $previous && $date <= $previous ) {
		$fails[] = $iso . ' does not come after the occurrence before it';
	}

	$previous = $date;
}

// ── the pair is read from the Polish post, not from each translation ──────
if ( function_exists( 'pll_get_post_translations' ) && $ms_c::source_post_id( (int) $post_id ) !== (int) $post_id ) {
	$fails[] = 'source_post_id() sent an unlinked post somewhere else';
}

wp_delete_post( (int) $post_id, true );

if ( $fails ) {
	echo "FAIL\n";
	foreach ( $fails as $f ) {
		echo "  - $f\n";
	}
	exit( 1 );
}
echo 'PASS: one stored pair yields the label and ' . $ms_c::OCCURRENCES . " future dated occurrences\n";
