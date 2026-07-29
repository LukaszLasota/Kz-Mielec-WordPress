<?php
/**
 * Facebook Feed Settings Page
 *
 * Admin UI for configuring Facebook Graph API access and cache settings.
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\Admin;

use CustomBlockPackage\Cache\BlockCache;
use CustomBlockPackage\Cron\FacebookFeedCron;
use CustomBlockPackage\Services\FacebookFeedService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FacebookSettings
 *
 * Registers an admin menu item for Facebook feed configuration,
 * handles form submission, test connection, and manual cache refresh.
 */
class FacebookSettings {

	/**
	 * Menu slug.
	 */
	public const MENU_SLUG = 'cbp-facebook-feed';

	/**
	 * Nonce action for save form.
	 */
	private const NONCE_SAVE = 'cbp_fb_save_settings';

	/**
	 * Nonce field name for save form.
	 */
	private const NONCE_SAVE_FIELD = 'cbp_fb_save_nonce';

	/**
	 * Nonce action for actions (test, refresh).
	 */
	private const NONCE_ACTION = 'cbp_fb_action';

	/**
	 * Nonce field name for actions.
	 */
	private const NONCE_ACTION_FIELD = 'cbp_fb_action_nonce';

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_notices', array( $this, 'render_error_notice' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
	}

	/**
	 * Show an admin notice on all admin pages when the last API call failed.
	 *
	 * @return void
	 */
	public function render_error_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$error = (string) get_option( FacebookFeedService::OPTION_LAST_ERROR, '' );
		if ( '' === $error ) {
			return;
		}

		// If the feed still has posts to serve (cache/backup), a failed refresh is
		// not a hard outage — show a dismissible warning rather than a red banner.
		// A genuinely empty feed stays an error. Both are dismissible so the admin
		// can always close them; a successful refresh clears the state entirely.
		$service   = new FacebookFeedService();
		$has_posts = $service->get_total_count() > 0;

		$class = $has_posts ? 'notice-warning' : 'notice-error';
		$intro = $has_posts
			? __( 'Facebook Feed — ostatnie odświeżenie nie powiodło się (wyświetlane są zapisane posty):', 'custom-block-package' )
			: __( 'Facebook Feed — błąd połączenia:', 'custom-block-package' );

		$settings_url = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		?>
		<div class="notice <?php echo esc_attr( $class ); ?> is-dismissible">
			<p>
				<strong><?php echo esc_html( $intro ); ?></strong>
				<code><?php echo esc_html( $error ); ?></code>
			</p>
			<p>
				<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Otwórz ustawienia Facebook Feed', 'custom-block-package' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Register dashboard widget showing feed status.
	 *
	 * @return void
	 */
	public function add_dashboard_widget(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'cbp_fb_dashboard',
			__( 'Facebook Feed — status', 'custom-block-package' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget content.
	 *
	 * @return void
	 */
	public function render_dashboard_widget(): void {
		$service      = new FacebookFeedService();
		$total        = $service->get_total_count();
		$last_sync    = (int) get_option( FacebookFeedService::OPTION_LAST_SYNC, 0 );
		$last_err     = (string) get_option( FacebookFeedService::OPTION_LAST_ERROR, '' );
		$page_id      = (string) get_option( FacebookFeedService::OPTION_PAGE_ID, '' );
		$token        = (string) get_option( FacebookFeedService::OPTION_ACCESS_TOKEN, '' );
		$settings_url = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		?>
		<ul style="margin: 0;">
			<li>
				<strong><?php esc_html_e( 'Strona FB:', 'custom-block-package' ); ?></strong>
				<?php echo $page_id ? esc_html( $page_id ) : '<em>' . esc_html__( 'nie skonfigurowana', 'custom-block-package' ) . '</em>'; ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Token:', 'custom-block-package' ); ?></strong>
				<?php if ( $token ) : ?>
					<span style="color: #46b450;">●</span> <?php esc_html_e( 'ustawiony', 'custom-block-package' ); ?>
				<?php else : ?>
					<span style="color: #dc3232;">●</span> <?php esc_html_e( 'brak', 'custom-block-package' ); ?>
				<?php endif; ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Posty w cache:', 'custom-block-package' ); ?></strong>
				<?php echo esc_html( (string) $total ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Ostatnia synchronizacja:', 'custom-block-package' ); ?></strong>
				<?php
				if ( $last_sync > 0 ) {
					echo esc_html(
						sprintf(
							/* translators: %s: relative time */
							__( '%s temu', 'custom-block-package' ),
							human_time_diff( $last_sync, time() )
						)
					);
				} else {
					esc_html_e( 'nigdy', 'custom-block-package' );
				}
				?>
			</li>
		</ul>

		<?php if ( '' !== $last_err ) : ?>
			<div style="background: #fbeaea; border-left: 4px solid #dc3232; padding: 8px 12px; margin-top: 12px;">
				<strong style="color: #dc3232;"><?php esc_html_e( 'Błąd:', 'custom-block-package' ); ?></strong>
				<code style="display: block; margin-top: 4px; word-break: break-all;"><?php echo esc_html( $last_err ); ?></code>
			</div>
		<?php endif; ?>

		<p style="margin-top: 12px;">
			<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-primary">
				<?php esc_html_e( 'Przejdź do ustawień', 'custom-block-package' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Register admin menu item.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		add_menu_page(
			__( 'Facebook Feed', 'custom-block-package' ),
			__( 'Facebook Feed', 'custom-block-package' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' ),
			'dashicons-facebook-alt',
			62
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'custom-block-package' ) );
		}

		$this->handle_form_submission();
		$this->handle_actions();

		$page_id   = (string) get_option( FacebookFeedService::OPTION_PAGE_ID, '' );
		$token     = (string) get_option( FacebookFeedService::OPTION_ACCESS_TOKEN, '' );
		$ttl       = (int) get_option( FacebookFeedService::OPTION_CACHE_TTL, FacebookFeedService::DEFAULT_TTL );
		$last_sync = (int) get_option( FacebookFeedService::OPTION_LAST_SYNC, 0 );
		$last_err  = (string) get_option( FacebookFeedService::OPTION_LAST_ERROR, '' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'cbp_fb' ); ?>

			<h2><?php esc_html_e( 'API Configuration', 'custom-block-package' ); ?></h2>
			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_SAVE, self::NONCE_SAVE_FIELD ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="cbp_fb_page_id"><?php esc_html_e( 'Page ID or username', 'custom-block-package' ); ?></label>
						</th>
						<td>
							<input type="text" id="cbp_fb_page_id" name="cbp_fb_page_id" value="<?php echo esc_attr( $page_id ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Facebook page username (e.g. Kzmielec) or numeric ID.', 'custom-block-package' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="cbp_fb_access_token"><?php esc_html_e( 'Page Access Token', 'custom-block-package' ); ?></label>
						</th>
						<td>
							<textarea id="cbp_fb_access_token" name="cbp_fb_access_token" rows="4" class="large-text code"><?php echo esc_textarea( $token ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Long-lived Page Access Token generated via Graph API Explorer. Treat as password.', 'custom-block-package' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="cbp_fb_cache_ttl"><?php esc_html_e( 'Refresh interval', 'custom-block-package' ); ?></label>
						</th>
						<td>
							<select id="cbp_fb_cache_ttl" name="cbp_fb_cache_ttl">
								<?php
								$options = array(
									HOUR_IN_SECONDS      => __( 'Every 1 hour', 'custom-block-package' ),
									2 * HOUR_IN_SECONDS  => __( 'Every 2 hours', 'custom-block-package' ),
									6 * HOUR_IN_SECONDS  => __( 'Every 6 hours', 'custom-block-package' ),
									12 * HOUR_IN_SECONDS => __( 'Every 12 hours', 'custom-block-package' ),
									DAY_IN_SECONDS       => __( 'Every 24 hours', 'custom-block-package' ),
								);
								foreach ( $options as $value => $label ) :
									?>
									<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( $ttl, $value ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" name="cbp_fb_save" class="button button-primary">
						<?php esc_html_e( 'Save settings', 'custom-block-package' ); ?>
					</button>
				</p>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Actions', 'custom-block-package' ); ?></h2>
			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_ACTION_FIELD ); ?>
				<p>
					<button type="submit" name="cbp_fb_test" class="button">
						<?php esc_html_e( 'Test connection', 'custom-block-package' ); ?>
					</button>
					<button type="submit" name="cbp_fb_refresh" class="button">
						<?php esc_html_e( 'Refresh cache now', 'custom-block-package' ); ?>
					</button>
					<button type="submit" name="cbp_fb_mock" class="button">
						<?php esc_html_e( 'Load mock data (for testing)', 'custom-block-package' ); ?>
					</button>
				</p>
				<p class="description">
					<?php esc_html_e( 'Mock data populates the cache with 30 fake posts (via picsum.photos) to preview the block UI without a real token.', 'custom-block-package' ); ?>
				</p>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Status', 'custom-block-package' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Last successful sync', 'custom-block-package' ); ?></th>
					<td>
						<?php
						if ( $last_sync > 0 ) {
							echo esc_html(
								sprintf(
									/* translators: %s: relative time */
									__( '%s ago', 'custom-block-package' ),
									human_time_diff( $last_sync, time() )
								)
							);
							echo ' <code>' . esc_html( (string) wp_date( 'Y-m-d H:i', $last_sync ) ) . '</code>';
						} else {
							esc_html_e( 'Never', 'custom-block-package' );
						}
						?>
					</td>
				</tr>
				<?php if ( '' !== $last_err ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Last error', 'custom-block-package' ); ?></th>
						<td><code><?php echo esc_html( $last_err ); ?></code></td>
					</tr>
				<?php endif; ?>
			</table>
		</div>
		<?php
	}

	/**
	 * Handle settings form submission.
	 *
	 * @return void
	 */
	private function handle_form_submission(): void {
		if ( ! isset( $_POST['cbp_fb_save'] ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_SAVE_FIELD ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_SAVE_FIELD ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_SAVE ) ) {
			wp_die( esc_html__( 'Security check failed.', 'custom-block-package' ) );
		}

		$page_id = isset( $_POST['cbp_fb_page_id'] )
			? sanitize_text_field( wp_unslash( $_POST['cbp_fb_page_id'] ) )
			: '';
		$token   = isset( $_POST['cbp_fb_access_token'] )
			? sanitize_textarea_field( wp_unslash( $_POST['cbp_fb_access_token'] ) )
			: '';
		$ttl     = isset( $_POST['cbp_fb_cache_ttl'] )
			? absint( wp_unslash( $_POST['cbp_fb_cache_ttl'] ) )
			: FacebookFeedService::DEFAULT_TTL;

		update_option( FacebookFeedService::OPTION_PAGE_ID, $page_id );
		update_option( FacebookFeedService::OPTION_ACCESS_TOKEN, $token );
		update_option( FacebookFeedService::OPTION_CACHE_TTL, $ttl );

		// Reschedule cron with new TTL.
		FacebookFeedCron::reschedule();

		add_settings_error(
			'cbp_fb',
			'saved',
			__( 'Settings saved.', 'custom-block-package' ),
			'updated'
		);
	}

	/**
	 * Handle test connection and manual refresh actions.
	 *
	 * @return void
	 */
	private function handle_actions(): void {
		if (
			! isset( $_POST['cbp_fb_test'] ) &&
			! isset( $_POST['cbp_fb_refresh'] ) &&
			! isset( $_POST['cbp_fb_mock'] )
		) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_ACTION_FIELD ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_ACTION_FIELD ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'custom-block-package' ) );
		}

		$service = new FacebookFeedService();

		if ( isset( $_POST['cbp_fb_test'] ) ) {
			$result = $service->test_connection();
			add_settings_error(
				'cbp_fb',
				'test',
				$result['message'],
				$result['success'] ? 'updated' : 'error'
			);
			return;
		}

		if ( isset( $_POST['cbp_fb_refresh'] ) ) {
			BlockCache::flush( BlockCache::FACEBOOK_FEED_PREFIX );
			$success = $service->refresh();
			add_settings_error(
				'cbp_fb',
				'refresh',
				$success
					? __( 'Cache refreshed successfully.', 'custom-block-package' )
					: __( 'Cache refresh failed. Check the error above.', 'custom-block-package' ),
				$success ? 'updated' : 'error'
			);
			return;
		}

		if ( isset( $_POST['cbp_fb_mock'] ) ) {
			BlockCache::flush( BlockCache::FACEBOOK_FEED_PREFIX );
			$service->load_mock_data();
			add_settings_error(
				'cbp_fb',
				'mock',
				__( 'Mock data loaded. Open a page with the Facebook Feed block to preview.', 'custom-block-package' ),
				'updated'
			);
		}
	}
}
