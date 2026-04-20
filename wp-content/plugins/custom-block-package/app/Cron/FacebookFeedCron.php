<?php
/**
 * Facebook Feed Cron
 *
 * Schedules periodic refresh of the Facebook feed cache in the background
 * so the frontend never waits on the Graph API.
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\Cron;

use CustomBlockPackage\Services\FacebookFeedService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FacebookFeedCron
 *
 * Registers WP Cron event for Facebook feed refresh.
 */
class FacebookFeedCron {

	/**
	 * Cron hook name.
	 */
	public const HOOK = 'cbp_fb_cron_refresh';

	/**
	 * Constructor — registers hooks.
	 */
	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'add_interval' ) ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
		add_action( self::HOOK, array( $this, 'run' ) );
		add_action( 'init', array( $this, 'maybe_schedule' ) );
	}

	/**
	 * Add custom cron interval based on configured TTL.
	 *
	 * @param array<string, array<string, mixed>> $schedules Existing schedules.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_interval( array $schedules ): array {
		$ttl = (int) get_option( FacebookFeedService::OPTION_CACHE_TTL, FacebookFeedService::DEFAULT_TTL );
		if ( $ttl < MINUTE_IN_SECONDS ) {
			$ttl = FacebookFeedService::DEFAULT_TTL;
		}

		$schedules['cbp_fb_interval'] = array(
			'interval' => $ttl,
			'display'  => __( 'Custom Block Package — Facebook feed refresh', 'custom-block-package' ),
		);

		return $schedules;
	}

	/**
	 * Schedule the event if not already scheduled.
	 *
	 * @return void
	 */
	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'cbp_fb_interval', self::HOOK );
		}
	}

	/**
	 * Cron handler — refreshes Facebook feed.
	 *
	 * @return void
	 */
	public function run(): void {
		$service = new FacebookFeedService();
		$service->refresh();
	}

	/**
	 * Plugin activation — schedule event.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'cbp_fb_interval', self::HOOK );
		}
	}

	/**
	 * Plugin deactivation — unschedule event.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( self::HOOK );
		if ( false !== $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
		}
	}

	/**
	 * Reschedule event (call after TTL change).
	 *
	 * @return void
	 */
	public static function reschedule(): void {
		self::deactivate();
		self::activate();
	}
}
