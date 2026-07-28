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
	 * Build a cache key from block attributes.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function key( array $attributes ): string {
		return self::PREFIX . md5( (string) wp_json_encode( $attributes ) );
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
