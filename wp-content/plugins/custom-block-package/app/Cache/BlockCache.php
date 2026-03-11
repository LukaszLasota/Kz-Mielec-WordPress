<?php
/**
 * Block transient cache helper.
 *
 * Single source of truth for cache-key prefixes and invalidation logic.
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BlockCache
 */
class BlockCache {

	/**
	 * Cache key prefix for the news slider block.
	 *
	 * @var string
	 */
	public const NEWS_SLIDER_PREFIX = 'news_slider_v2_';

	/**
	 * Cache key prefix for the meeting list block.
	 *
	 * @var string
	 */
	public const MEETING_LIST_PREFIX = 'meeting_v4_';

	/**
	 * Default cache TTL in seconds (30 minutes).
	 *
	 * @var int
	 */
	public const TTL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Build a cache key from a prefix and block attributes.
	 *
	 * @param string               $prefix     One of the *_PREFIX constants.
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function key( string $prefix, array $attributes ): string {
		return $prefix . md5( (string) wp_json_encode( $attributes ) );
	}

	/**
	 * Delete all transients that match a given prefix.
	 *
	 * @param string $prefix One of the *_PREFIX constants.
	 * @return void
	 */
	public static function flush( string $prefix ): void {
		global $wpdb;

		$escaped = self::escape_like( $prefix );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . $escaped . '%',
				'_transient_timeout_' . $escaped . '%'
			)
		);
	}

	/**
	 * Escape a string for use in a LIKE clause.
	 *
	 * @param string $value Raw string.
	 * @return string
	 */
	private static function escape_like( string $value ): string {
		global $wpdb;
		return $wpdb->esc_like( $value );
	}
}
