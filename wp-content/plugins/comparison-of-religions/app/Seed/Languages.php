<?php
/**
 * Which languages this site actually has, with or without Polylang.
 *
 * Everything about the seed has to survive Polylang being absent, and Polylang
 * being switched on later. This class is the only place that knows the
 * difference, so the exporter and the importer can be written as if the answer
 * were always simply "these languages".
 *
 * @package ComparisonOfReligions
 */

declare(strict_types=1);

namespace ComparisonOfReligions\Seed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reports the site's languages and the source language of the seed.
 */
class Languages {

	/**
	 * The language the seed data is authored in.
	 *
	 * Every other file is a translation of this one, and the identity key of a
	 * topic is its slug in this language. Hard-coded rather than read from
	 * Polylang because the files have to mean the same thing on a site that has
	 * no Polylang at all.
	 */
	public const SOURCE = 'pl';

	/**
	 * Is Polylang present and usable?
	 *
	 * The test is for the functions, not for the plugin being active: they are
	 * what this code calls, and they are what disappears when the plugin goes.
	 */
	public static function available(): bool {
		return function_exists( 'pll_languages_list' )
			&& function_exists( 'pll_set_post_language' )
			&& function_exists( 'pll_save_post_translations' );
	}

	/**
	 * Language slugs this site can hold content in.
	 *
	 * Without Polylang there is exactly one: the source language. That is not a
	 * degraded answer, it is the correct one - a single-language site has one
	 * language, and the seed for it is the Polish file.
	 *
	 * @return array<int, string>
	 */
	public static function site(): array {
		if ( ! self::available() ) {
			return [ self::SOURCE ];
		}

		/*
		 * `pll_languages_list()` returns slugs by default. An empty result means
		 * Polylang is installed but not configured yet, which is a real state on
		 * a fresh site: treat it as no Polylang rather than as no languages,
		 * otherwise the import would have nothing to do and would look broken.
		 */
		if ( ! function_exists( 'pll_languages_list' ) ) {
			return [ self::SOURCE ];
		}

		$slugs = pll_languages_list();

		if ( ! is_array( $slugs ) || [] === $slugs ) {
			return [ self::SOURCE ];
		}

		return array_values( array_map( 'strval', $slugs ) );
	}

	/**
	 * Does this site have a language of its own for the given slug?
	 *
	 * @param string $lang Language slug.
	 */
	public static function has( string $lang ): bool {
		return in_array( $lang, self::site(), true );
	}

	/**
	 * Is this post in the given language?
	 *
	 * Asked directly instead of pushing `lang` into the query, because Polylang's
	 * query filters do not run under WP-CLI - and, on a site that has not been
	 * told to translate this post type, they do not run at all. Trusting the
	 * query argument produced two real defects: an import that linked all four
	 * languages to the same post, and an export that wrote 148 topics into each
	 * of four language files instead of 37.
	 *
	 * A post with no language belongs to the source language: that is the state
	 * left behind by importing before Polylang existed. Without Polylang every
	 * post answers yes, because there is only one language.
	 *
	 * @param int    $post_id Post id.
	 * @param string $lang    Language slug.
	 */
	public static function post_speaks( int $post_id, string $lang ): bool {
		if ( ! self::available() || ! function_exists( 'pll_get_post_language' ) ) {
			return true;
		}

		$current = pll_get_post_language( $post_id );

		if ( ! is_string( $current ) || '' === $current ) {
			return self::SOURCE === $lang;
		}

		return $current === $lang;
	}

	/**
	 * The same question for a term.
	 *
	 * @param int    $term_id Term id.
	 * @param string $lang    Language slug.
	 */
	public static function term_speaks( int $term_id, string $lang ): bool {
		if ( ! self::available() || ! function_exists( 'pll_get_term_language' ) ) {
			return true;
		}

		$current = pll_get_term_language( $term_id );

		if ( ! is_string( $current ) || '' === $current ) {
			return self::SOURCE === $lang;
		}

		return $current === $lang;
	}

	/**
	 * Is there translated content here that nothing can currently tell apart?
	 *
	 * Deactivating Polylang takes its functions away but leaves its `language`
	 * taxonomy terms in the database. In that state every query answers with all
	 * languages at once, so an export would pour four languages into the source
	 * file and call it Polish. The exporter refuses instead of producing that,
	 * because the damage would be silent: the file would look plausible and
	 * would be wrong.
	 *
	 * Returns false on a genuinely single-language site, which has no such terms.
	 */
	public static function stale_translations(): bool {
		if ( self::available() ) {
			return false;
		}

		global $wpdb;

		/*
		 * Asked of the table, not of WordPress. When Polylang is deactivated its
		 * `language` taxonomy is no longer registered, so `taxonomy_exists()` says
		 * no and `get_terms()` finds nothing - while the terms sit in the database
		 * untouched. The first version of this guard used those functions and let
		 * an export through on a deactivated site, which wrote all four languages
		 * into the source file. A test caught it; the table does not lie.
		 */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- No API answers this once the taxonomy is unregistered, and the result must not be cached across a plugin being activated.
		$count = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s", 'language' )
		);

		return $count > 1;
	}
}
