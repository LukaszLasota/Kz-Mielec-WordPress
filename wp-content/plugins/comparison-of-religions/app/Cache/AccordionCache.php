<?php
/**
 * Accordion block transient cache helper.
 *
 * Single source of truth for cache-key prefix and invalidation logic.
 *
 * @package ComparisonOfReligions
 */

declare(strict_types=1);

namespace ComparisonOfReligions\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AccordionCache
 */
class AccordionCache {

	/**
	 * Cache key prefix for the comparison accordion block.
	 *
	 * @var string
	 */
	public const PREFIX = 'cor_accordion_';

	/**
	 * Default cache TTL in seconds (30 minutes).
	 *
	 * @var int
	 */
	public const TTL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Build a cache key from block attributes and the current language.
	 *
	 * The language is part of the key, and it has to be. The block renders the
	 * comparison topics, which Polylang filters per language — so one key shared
	 * by four languages means whichever language renders first is served to all of
	 * them. Measured on `/en/differences-in-religious-beliefs/`: the English page
	 * came out with Polish category names and 56 occurrences of "Kościół
	 * Zielonoświątkowy" against 5 of "Pentecostal Church".
	 *
	 * The failure is invisible to a structural test: the accordion existed,
	 * toggled and passed every functional check — only its contents came from the
	 * wrong language.
	 *
	 * `function_exists()` keeps this working with Polylang switched off, where the
	 * key simply loses its language part.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function key( array $attributes ): string {
		$lang = function_exists( 'pll_current_language' ) ? (string) pll_current_language( 'slug' ) : '';

		return self::PREFIX . md5( (string) wp_json_encode( $attributes ) . '|' . $lang );
	}

	/**
	 * Delete all transients that match the accordion prefix.
	 *
	 * @return void
	 */
	public static function flush(): void {
		global $wpdb;

		$escaped = $wpdb->esc_like( self::PREFIX );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . $escaped . '%',
				'_transient_timeout_' . $escaped . '%'
			)
		);
	}
}
