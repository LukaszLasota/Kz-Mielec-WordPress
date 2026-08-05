<?php
/**
 * FeedCachePurge class
 *
 * Drops the cached HTML of pages whose content comes from a social API rather
 * than from this site's database.
 *
 * @package Kzmielec\Core
 */

namespace Kzmielec\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class FeedCachePurge
 *
 * There are two caches between a new Facebook post and a visitor, and only one
 * of them was being refreshed. Each feed keeps its own copy of the API answer
 * and a cron replaces it on schedule; the page cache keeps the rendered HTML,
 * and nothing touched it, because a post appearing on Facebook is not an event
 * in WordPress and so fires none of the caching plugin's purge rules. Measured
 * on production before this class existed: the stored feed data was 6 minutes
 * old while the page being served was 9.7 hours old.
 *
 * Stale text is the mild half. Facebook signs its image URLs with an expiry — in
 * the copy measured, the earliest died 20.8 hours later and the last within four
 * days — so a page cached for seven days spends most of its life showing a
 * correct-looking layout with dead photos. Smash Balloon bakes a one-time token
 * into its markup too, and once that ages out the feed's "load more" button
 * stops answering.
 *
 * This lives in the theme rather than in `custom-block-package` on purpose. The
 * plugin that owns a feed announces that its data changed and knows nothing
 * about caching; deciding which URLs to drop is a site-level policy, and the
 * theme is where "this site runs LiteSpeed and prints two feeds on the front
 * page" is a true statement. It also survives the case that decided it: the
 * Instagram feed belongs to a separate plugin, so had the listener lived in our
 * block plugin, deactivating that plugin would have left the Instagram feed on
 * the site and silently stopped purging it.
 */
class FeedCachePurge implements ActionHookInterface {

	/**
	 * Blocks whose output is fetched from a social API.
	 *
	 * Matched against the block delimiter in `post_content`, so a page counts
	 * only when it really prints the block. The first is ours, the second is
	 * Smash Balloon's.
	 *
	 * @var array<int, string>
	 */
	private const FEED_BLOCKS = array(
		'custom-block-package/facebook-feed',
		'sbi/sbi-feed-block',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
	}

	/**
	 * Register WordPress action hooks.
	 *
	 * Three signals, because a feed refresh is announced differently by each
	 * plugin: `cbp_feed_refreshed` is fired by our own
	 * `FacebookFeedService::refresh()` and therefore covers its cron, the
	 * "refresh" button and the REST route in one; Smash Balloon starts its
	 * update on `sbi_feed_update` and finishes it in batches on
	 * `sbi_cron_additional_batch`, so both are needed to catch the moment the
	 * last batch lands. Purging the same URL twice costs nothing.
	 *
	 * The Smash Balloon hook names are a known seam: if the plugin renames them,
	 * the Instagram half stops purging, quietly. It degrades to the old
	 * behaviour rather than breaking, and there is no cleaner signal to listen
	 * for — that plugin stores its feed cache in its own database table, not in
	 * options.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'cbp_feed_refreshed', array( $this, 'purge_feed_pages' ) );
		add_action( 'sbi_feed_update', array( $this, 'purge_feed_pages' ), 20 );
		add_action( 'sbi_cron_additional_batch', array( $this, 'purge_feed_pages' ), 20 );

		/*
		 * The same for the buttons an editor presses by hand. Smash Balloon's
		 * "clear cache" empties rows in its own table (`sbi_clear_caches()` in
		 * `inc/if-functions.php`) and fires no action of its own, so without
		 * these three the page would keep serving its old copy and the click
		 * would appear to do nothing.
		 *
		 * Priority 1, deliberately: each handler answers with
		 * `wp_send_json_*()`, which ends the request, so anything registered
		 * after them never runs. Purging before the table is emptied is still
		 * correct — the cached page is dropped, and by the time the next visitor
		 * arrives the table is empty and the feed refetches.
		 */
		add_action( 'wp_ajax_sbi_clear_cache', array( $this, 'purge_feed_pages' ), 1 );
		add_action( 'wp_ajax_sbi_feed_saver_manager_clear_single_feed_cache', array( $this, 'purge_feed_pages' ), 1 );
		add_action( 'wp_ajax_sbi_feed_saver_manager_recache_feed', array( $this, 'purge_feed_pages' ), 1 );

		// Our own signal, raised by the admin-bar button in `FeedRefreshButton`
		// once it has asked both feeds for fresh data. Kept as an action rather
		// than a direct call so the button announces what happened and this
		// class stays the only place that decides what to drop.
		add_action( 'kzmielec_feeds_refreshed', array( $this, 'purge_feed_pages' ) );
	}

	/**
	 * Drop the cached HTML of every URL that prints a social feed.
	 *
	 * Uses `litespeed_purge_url`, not `litespeed_purge_all`. The wide purge also
	 * clears the combined CSS/JS and bumps the `?ver=` those files carry, which
	 * would make every visitor re-download them — too high a price for a
	 * refreshed feed. The URL purge marks that one address and nothing else.
	 *
	 * Safe when no cache plugin is installed: LiteSpeed registers this hook in
	 * `api.cls.php` only while it is active, and `do_action` on a hook nobody
	 * listens to does nothing at all. Swapping to a different caching plugin
	 * means changing this one method.
	 *
	 * @return void
	 */
	public function purge_feed_pages(): void {
		foreach ( $this->feed_urls() as $url ) {
			do_action( 'litespeed_purge_url', $url );
		}
	}

	/**
	 * URLs that render a social feed.
	 *
	 * The front page is included unconditionally: it is where both feeds live
	 * today, and when a Page is the front page its cached address is `/` rather
	 * than its permalink. The query then adds any other published post or page
	 * carrying one of the blocks, so moving a feed elsewhere needs no change
	 * here.
	 *
	 * @return array<int, string>
	 */
	private function feed_urls(): array {
		$urls = array( home_url( '/' ) );

		global $wpdb;

		$conditions = array();
		foreach ( self::FEED_BLOCKS as $block ) {
			$conditions[] = $wpdb->prepare(
				'post_content LIKE %s',
				'%' . $wpdb->esc_like( '<!-- wp:' . $block ) . '%'
			);
		}

		// Every LIKE fragment came out of `$wpdb->prepare()` above, so the only
		// thing interpolated here is the table name. There is no meta or
		// taxonomy to query through — the block lives in the content — so a LIKE
		// over post_content is the honest way to ask the question. It runs once
		// per feed refresh rather than per request, which is why the result is
		// not cached.
		$sql = "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND ( " . implode( ' OR ', $conditions ) . ' )';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- see above.
		$ids = $wpdb->get_col( $sql );

		foreach ( $ids as $id ) {
			$permalink = get_permalink( (int) $id );
			if ( is_string( $permalink ) && '' !== $permalink ) {
				$urls[] = $permalink;
			}
		}

		return array_values( array_unique( $urls ) );
	}
}
