<?php
/**
 * FeedRefreshButton class
 *
 * One button in the admin bar that makes both social feeds current.
 *
 * @package Kzmielec\Core
 */

namespace Kzmielec\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class FeedRefreshButton
 *
 * Making a new Facebook or Instagram post appear used to take two visits to two
 * different settings screens, and knowing which of three caches each button
 * actually cleared. This collapses it into one click, available wherever the
 * admin bar is — including the front end, so an editor can look at the page and
 * refresh it without leaving it.
 *
 * The button does three things in order: asks our Facebook feed for fresh posts,
 * empties Smash Balloon's stored copy so its next render refetches, and then
 * announces that both happened. `FeedCachePurge` listens for that announcement
 * and drops the cached pages, which is what makes the change visible.
 *
 * Note what it does NOT do: it never touches the combined CSS/JS. LiteSpeed's
 * own "Purge All" does, and that makes every visitor re-download both files — a
 * price worth paying after a deployment and never worth paying for a feed.
 */
class FeedRefreshButton implements ActionHookInterface {

	/**
	 * Query action name, used for both the admin-post route and its nonce.
	 */
	private const ACTION = 'kzmielec_refresh_feeds';

	/**
	 * Cron hook of the Facebook feed, owned by `custom-block-package`.
	 *
	 * Referenced as a string rather than through the plugin's class so the theme
	 * carries no hard dependency on it: with the plugin gone the action simply
	 * has no listener and nothing happens.
	 */
	private const FACEBOOK_REFRESH_HOOK = 'cbp_fb_cron_refresh';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
	}

	/**
	 * Register WordPress action hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'admin_bar_menu', array( $this, 'add_button' ), 90 );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_notices', array( $this, 'show_result' ) );
	}

	/**
	 * Add the button to the admin bar.
	 *
	 * @param \WP_Admin_Bar $admin_bar The admin bar instance.
	 * @return void
	 */
	public function add_button( \WP_Admin_Bar $admin_bar ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'    => 'kzmielec-refresh-feeds',
				'title' => __( 'Odśwież feedy', 'kzmielec' ),
				'href'  => wp_nonce_url(
					admin_url( 'admin-post.php?action=' . self::ACTION ),
					self::ACTION
				),
				'meta'  => array(
					'title' => __( 'Pobiera nowe posty z Facebooka i Instagrama i czyści cache stron z feedem', 'kzmielec' ),
				),
			)
		);
	}

	/**
	 * Refresh both feeds, then have the cached pages dropped.
	 *
	 * @return void
	 */
	public function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Brak uprawnień do odświeżania feedów.', 'kzmielec' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION );

		// Facebook: the plugin's cron handler calls the API and replaces the
		// stored posts.
		do_action( self::FACEBOOK_REFRESH_HOOK );

		// Instagram: Smash Balloon keeps its copy in its own table and offers a
		// plain function to blank it. Emptied here rather than refetched,
		// because their fetch runs on render.
		if ( function_exists( 'sbi_clear_caches' ) ) {
			\sbi_clear_caches();
		}

		/**
		 * Fires after an editor has asked both feeds for fresh data by hand.
		 *
		 * @since 1.0.0
		 */
		do_action( 'kzmielec_feeds_refreshed' );

		$back = wp_get_referer();

		wp_safe_redirect(
			add_query_arg( 'kzmielec-feeds', 'refreshed', is_string( $back ) && '' !== $back ? $back : admin_url() )
		);
		exit;
	}

	/**
	 * Confirm the refresh in the admin, when the redirect landed there.
	 *
	 * A front-end redirect carries the same query argument but shows no notice;
	 * the point there is simply to come back to the reloaded page.
	 *
	 * @return void
	 */
	public function show_result(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check of a redirect marker, changes nothing.
		$flag = isset( $_GET['kzmielec-feeds'] ) ? sanitize_key( wp_unslash( $_GET['kzmielec-feeds'] ) ) : '';

		if ( 'refreshed' !== $flag ) {
			return;
		}

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'Feedy odświeżone, cache stron wyczyszczony.', 'kzmielec' )
		);
	}
}
