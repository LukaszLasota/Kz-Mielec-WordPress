<?php
/**
 * The single source of truth for when a meeting happens.
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\Services;

use CustomBlockPackage\I18n\Locale;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MeetingSchedule
 *
 * A meeting's day and hour used to be one free-text field, `_meeting_day_hour`,
 * typed per language: "Niedziela 10:30" in Polish but "Sunday 10.30 am" in
 * English, "Viernes a las 18:00" in Spanish and "Неділя, 18:00" in Ukrainian —
 * four spellings, four separators, twelve-hour clock in one of them. Prose in
 * four shapes cannot be parsed back into a date, which is why Google reported
 * both events on the front page as invalid: `startDate` is required and there
 * was nothing to build one from.
 *
 * So the day and the hour are stored as a weekday number and an `HH:MM` string,
 * once, on the Polish post. Everything else is derived from that pair: the
 * visible label in each language, and the ISO-8601 dates in the schema graph.
 *
 * `_meeting_day_hour` still exists and is still written, but nobody types into
 * it any more — it is regenerated from the pair whenever a meeting is saved.
 * It has to keep existing because the theme lists it in
 * `Setup::SEARCHABLE_META_KEYS`, so site search finds a meeting by the word
 * "niedziela". Deleting the field would have taken that away silently, with the
 * pages still rendering perfectly.
 */
class MeetingSchedule {

	/**
	 * Meta key: weekday as an ISO-8601 number, 1 = Monday … 7 = Sunday, 0 = none.
	 */
	public const META_WEEKDAY = '_meeting_weekday';

	/**
	 * Meta key: start time as `HH:MM` in 24-hour form, '' = none.
	 */
	public const META_TIME = '_meeting_time';

	/**
	 * How many upcoming occurrences the schema graph describes.
	 *
	 * One would be the obvious choice and would be wrong. LiteSpeed's
	 * `cache-ttl_pub` on this host is 604800 seconds, so a visitor — or a
	 * crawler — can be served HTML generated a week ago. A single "next
	 * Sunday" computed at render time would then be a date in the past, which
	 * is worse than no date at all. Six weeks of occurrences means the oldest
	 * cacheable copy still advertises future ones, and it matches what Google
	 * asks for anyway: each occurrence of a recurring event as its own event.
	 */
	public const OCCURRENCES = 6;

	/**
	 * Weekday names, indexed by the ISO-8601 number stored in the meta.
	 *
	 * Deliberately not `WP_Locale` and not `wp_date()`. Those read the weekday
	 * names out of WordPress core's own catalogue, which is rebuilt by
	 * `switch_to_locale()` but not by filtering `determine_locale` — and
	 * `switch_to_locale()` has already been measured on this site to hand back
	 * Polish while reporting success. Seven strings in the plugin's own
	 * catalogue are translated by exactly the same mechanism as every other
	 * string here, so they cannot disagree with the rest of the page.
	 *
	 * @return array<int, string>
	 */
	public static function weekday_names(): array {
		return array(
			1 => _x( 'Poniedziałek', 'weekday of a meeting', 'custom-block-package' ),
			2 => _x( 'Wtorek', 'weekday of a meeting', 'custom-block-package' ),
			3 => _x( 'Środa', 'weekday of a meeting', 'custom-block-package' ),
			4 => _x( 'Czwartek', 'weekday of a meeting', 'custom-block-package' ),
			5 => _x( 'Piątek', 'weekday of a meeting', 'custom-block-package' ),
			6 => _x( 'Sobota', 'weekday of a meeting', 'custom-block-package' ),
			7 => _x( 'Niedziela', 'weekday of a meeting', 'custom-block-package' ),
		);
	}

	/**
	 * Resolve the post holding the authoritative pair.
	 *
	 * The pair lives on the Polish post and nowhere else. Copying it onto the
	 * translations would put four editable copies of one fact back in the
	 * database, which is the arrangement this class exists to remove.
	 *
	 * With Polylang switched off there is only the Polish post, and
	 * `pll_get_post_translations()` is gone with the plugin, so the post is its
	 * own source.
	 *
	 * @param int $post_id Any translation of a meeting.
	 * @return int Post ID to read the pair from.
	 */
	public static function source_post_id( int $post_id ): int {
		if ( ! function_exists( 'pll_get_post_translations' ) ) {
			return $post_id;
		}

		$translations = pll_get_post_translations( $post_id );

		if ( ! is_array( $translations ) || empty( $translations['pl'] ) ) {
			return $post_id;
		}

		return (int) $translations['pl'];
	}

	/**
	 * Read the stored pair.
	 *
	 * @param int $post_id Any translation of a meeting.
	 * @return array{weekday: int, time: string}|null Null when the meeting has no fixed slot.
	 */
	public static function schedule( int $post_id ): ?array {
		$source = self::source_post_id( $post_id );

		$weekday = (int) get_post_meta( $source, self::META_WEEKDAY, true );
		$time    = (string) get_post_meta( $source, self::META_TIME, true );

		if ( $weekday < 1 || $weekday > 7 || 1 !== preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $time ) ) {
			return null;
		}

		return array(
			'weekday' => $weekday,
			'time'    => $time,
		);
	}

	/**
	 * Build the visible label in the current locale.
	 *
	 * Both the separator and the clock are translatable, because the wording
	 * already differed per language before this class existed and normalising
	 * every visitor onto the Polish shape would have been a content change
	 * dressed up as a refactor. English keeps its twelve-hour "10.30 am".
	 *
	 * @param int $post_id Any translation of a meeting.
	 * @return string Empty when the meeting has no fixed slot.
	 */
	public static function label( int $post_id ): string {
		$schedule = self::schedule( $post_id );

		if ( null === $schedule ) {
			return '';
		}

		$names = self::weekday_names();

		/*
		 * PHP's own format characters are used, not WordPress's, because `H`,
		 * `i`, `g` and `a` mean the same thing in every locale — `a` is always
		 * lowercase English "am"/"pm", which is exactly what the English page
		 * already showed. Nothing here depends on a catalogue being loaded.
		 */
		$format = _x( 'H:i', 'meeting time format, PHP date() syntax', 'custom-block-package' );

		$time = \DateTimeImmutable::createFromFormat(
			'H:i',
			$schedule['time'],
			wp_timezone()
		);

		if ( false === $time ) {
			return '';
		}

		return sprintf(
			/* translators: 1: weekday name, 2: time of day. Only the separator differs per language. */
			_x( '%1$s %2$s', 'weekday and time of a meeting', 'custom-block-package' ),
			$names[ $schedule['weekday'] ],
			$time->format( $format )
		);
	}

	/**
	 * List the next few occurrences as ISO-8601 timestamps.
	 *
	 * @param int $post_id Any translation of a meeting.
	 * @param int $count   How many occurrences to return.
	 * @return array<int, string> ISO-8601 strings with an offset, oldest first; empty when unscheduled.
	 */
	public static function occurrences( int $post_id, int $count = self::OCCURRENCES ): array {
		$schedule = self::schedule( $post_id );

		if ( null === $schedule || $count < 1 ) {
			return array();
		}

		$timezone = wp_timezone();

		$now = new \DateTimeImmutable( 'now', $timezone );

		$first = \DateTimeImmutable::createFromFormat(
			'!Y-m-d H:i',
			$now->format( 'Y-m-d' ) . ' ' . $schedule['time'],
			$timezone
		);

		if ( false === $first ) {
			return array();
		}

		/*
		 * Walk forward to the wanted weekday. Doing it a day at a time rather
		 * than with a relative string keeps the wall-clock time fixed across
		 * the March and October transitions: "next Sunday 10:30" has to stay
		 * 10:30 local, and adding 604800 seconds to a timestamp does not.
		 */
		while ( (int) $first->format( 'N' ) !== $schedule['weekday'] || $first <= $now ) {
			$first = $first->modify( '+1 day' );
		}

		$dates = array();

		for ( $week = 0; $week < $count; $week++ ) {
			$dates[] = $first->modify( sprintf( '+%d week', $week ) )->format( 'c' );
		}

		return $dates;
	}

	/**
	 * Regenerate the derived `_meeting_day_hour` on a meeting and its translations.
	 *
	 * Called after a save, so the field the theme's site search indexes keeps
	 * matching the pair an editor just changed — in every language, not only
	 * the one the administrator happens to be using.
	 *
	 * @param int $post_id Any translation of a meeting.
	 * @return void
	 */
	public static function refresh_index( int $post_id ): void {
		$posts = array();

		if ( function_exists( 'pll_get_post_translations' ) ) {
			$translations = pll_get_post_translations( $post_id );

			if ( is_array( $translations ) ) {
				foreach ( $translations as $translated_id ) {
					$posts[] = (int) $translated_id;
				}
			}
		}

		if ( array() === $posts ) {
			$posts = array( $post_id );
		}

		foreach ( $posts as $translated_id ) {
			$locale = function_exists( 'pll_get_post_language' )
				? (string) pll_get_post_language( $translated_id, 'locale' )
				: '';

			$label = Locale::with(
				$locale,
				static function () use ( $translated_id ): string {
					return self::label( $translated_id );
				}
			);

			update_post_meta( $translated_id, \CustomBlockPackage\Admin\MeetingMeta::META_DAY_HOUR, $label );
		}
	}
}
