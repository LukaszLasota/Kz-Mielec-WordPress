<?php
/**
 * Running a piece of work in another language.
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\I18n;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Locale
 *
 * Loads this plugin's catalogue in a chosen locale for the length of one
 * callback, then puts everything back.
 *
 * It exists because two callers need it and neither is about translation:
 * `Services\MeetingSchedule` builds a meeting label for each language after a
 * save, and `Rest\FacebookFeedController` answers a request that carries the
 * page's language but is not served from a language-prefixed URL. The logic
 * lived inside MeetingSchedule first; a second copy in the controller is
 * exactly the kind of duplication that drifts, and the detail below is the sort
 * that only one of the copies would have kept.
 */
final class Locale {

	/**
	 * This plugin's text domain.
	 */
	private const DOMAIN = 'custom-block-package';

	/**
	 * Run a callback with the catalogue loaded in another language.
	 *
	 * `unload_textdomain()` has to be told the domain is reloadable. Without the
	 * second argument WordPress remembers the domain as unloaded and refuses to
	 * load it again, so only the FIRST switch in a process has any effect — a
	 * save that walks four languages would then write the Polish label into
	 * three of them and report success.
	 *
	 * `switch_to_locale()` is deliberately not used: on this site it has already
	 * returned Polish for a non-Polish locale, because it rebuilds the core
	 * catalogue rather than a plugin's.
	 *
	 * @param string   $locale   Target locale, e.g. `en_GB`. Empty runs the callback untouched.
	 * @param callable $callback Work to run.
	 * @return string Whatever the callback returned.
	 */
	public static function with( string $locale, callable $callback ): string {
		if ( '' === $locale || determine_locale() === $locale ) {
			return (string) $callback();
		}

		$force = static function () use ( $locale ): string {
			return $locale;
		};

		add_filter( 'locale', $force, 99 );
		add_filter( 'determine_locale', $force, 99 );

		self::reload();

		try {
			return (string) $callback();
		} finally {
			remove_filter( 'locale', $force, 99 );
			remove_filter( 'determine_locale', $force, 99 );

			self::reload();
		}
	}

	/**
	 * Map a Polylang language slug to its locale.
	 *
	 * The slug is checked against the site's own list rather than trusted, which
	 * matters when it arrives in a request: the locale ends up in a filename
	 * passed to `load_plugin_textdomain()`, and an unknown language is answered
	 * in the default one rather than looked up on disk.
	 *
	 * @param string $slug Language slug, e.g. `en`.
	 * @return string Locale, or an empty string when the slug is not a language of this site.
	 */
	public static function for_slug( string $slug ): string {
		if ( '' === $slug || ! function_exists( 'pll_languages_list' ) ) {
			return '';
		}

		$slugs   = pll_languages_list( array( 'fields' => 'slug' ) );
		$locales = pll_languages_list( array( 'fields' => 'locale' ) );

		if ( ! is_array( $slugs ) || ! is_array( $locales ) ) {
			return '';
		}

		$index = array_search( $slug, $slugs, true );

		if ( false === $index || ! isset( $locales[ $index ] ) ) {
			return '';
		}

		return (string) $locales[ $index ];
	}

	/**
	 * The language a block should be rendered for.
	 *
	 * The post being rendered comes first and the request only second, because
	 * some of the places a block renders have no request to speak of: the editor
	 * goes through `/wp/v2/block-renderer/` and this plugin's own feed route
	 * through `/wp-json/…`, and in both Polylang answers with the default
	 * language whatever the page. An empty string means "do not narrow" — the
	 * right answer when Polylang is off, because then there is one language and
	 * everything is in it.
	 *
	 * @return string Language slug, or an empty string.
	 */
	public static function current_slug(): string {
		if ( ! function_exists( 'pll_get_post_language' ) ) {
			return '';
		}

		$post = get_post();

		if ( $post instanceof \WP_Post ) {
			$lang = pll_get_post_language( $post->ID );

			if ( is_string( $lang ) && '' !== $lang ) {
				return $lang;
			}
		}

		if ( function_exists( 'pll_current_language' ) ) {
			$lang = pll_current_language( 'slug' );

			if ( is_string( $lang ) && '' !== $lang ) {
				return $lang;
			}
		}

		return '';
	}

	/**
	 * Drop and re-read this plugin's catalogue for whatever locale now applies.
	 *
	 * @return void
	 */
	private static function reload(): void {
		unload_textdomain( self::DOMAIN, true );

		if ( ! defined( 'UP_PLUGIN_FILE' ) ) {
			return;
		}

		load_plugin_textdomain(
			self::DOMAIN,
			false,
			dirname( plugin_basename( UP_PLUGIN_FILE ) ) . '/languages'
		);
	}
}
