<?php
/**
 * BeliefSettings class
 *
 * Handles belief pages configuration in WordPress admin.
 *
 * @package Kzmielec\Admin
 */

namespace Kzmielec\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class BeliefSettings
 *
 * Provides admin interface for selecting and ordering belief pages
 * displayed in the circle navigation on page-belief.php template.
 */
class BeliefSettings implements ActionHookInterface {

	/**
	 * Option name for stored belief page IDs.
	 */
	public const OPTION_NAME = 'kzmielec_belief_pages';

	/**
	 * Nonce action name for security verification.
	 */
	private const NONCE_ACTION = 'save_belief_settings';

	/**
	 * Nonce field name.
	 */
	private const NONCE_NAME = 'belief_settings_nonce';

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
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
	}

	/**
	 * Add belief settings page as subpage of Theme Settings.
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		add_submenu_page(
			ThemeSettingsPage::MENU_SLUG,
			'Strony wiary',
			'Strony wiary',
			'manage_options',
			'kzmielec-belief',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Display belief settings page.
	 *
	 * @return void
	 */
	public function display_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Nie masz wystarczajacych uprawnien, aby uzyskac dostep do tej strony.', 'kzmielec' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in handle_form_submission().
		if ( isset( $_POST['submit_belief_settings'] ) ) {
			$this->handle_form_submission();
		}

		$this->render_settings_form();
	}

	/**
	 * Handle form submission with security checks.
	 *
	 * @return void
	 */
	private function handle_form_submission(): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Weryfikacja bezpieczenstwa nie powiodla sie. Sprobuj ponownie.', 'kzmielec' ) );
		}

		$page_ids = array();
		if ( isset( $_POST['belief_pages'] ) && is_array( $_POST['belief_pages'] ) ) {
			$page_ids = array_map( 'absint', $_POST['belief_pages'] );
			$page_ids = array_filter( $page_ids );
		}

		update_option( self::OPTION_NAME, $page_ids );

		add_settings_error(
			'kzmielec_belief',
			'belief_saved',
			__( 'Strony wiary zostaly zapisane.', 'kzmielec' ),
			'updated'
		);
	}

	/**
	 * Render settings form HTML.
	 *
	 * @return void
	 */
	private function render_settings_form(): void {
		$saved_pages = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $saved_pages ) ) {
			$saved_pages = array();
		}

		$all_pages = get_pages(
			array(
				'sort_column' => 'menu_order,post_title',
				'post_status' => 'publish',
			)
		);
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'kzmielec_belief' ); ?>

			<p><?php esc_html_e( 'Wybierz strony, ktore beda wyswietlane w nawigacji kolowej na stronach wiary. Kolejnosc ma znaczenie.', 'kzmielec' ); ?></p>

			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<div id="belief-pages-list">
					<?php
					// Show saved pages first (in order).
					$displayed_ids = array();
					foreach ( $saved_pages as $page_id ) :
						$page = get_post( (int) $page_id );
						if ( ! $page ) {
							continue;
						}
						$displayed_ids[] = (int) $page_id;
						?>
						<p>
							<label>
								<input type="checkbox"
										name="belief_pages[]"
										value="<?php echo esc_attr( (string) $page_id ); ?>"
										checked="checked"
								/>
								<?php echo esc_html( $page->post_title ); ?>
							</label>
						</p>
					<?php endforeach; ?>

					<?php // Show remaining pages (not yet selected). ?>
					<?php if ( $all_pages ) : ?>
						<?php foreach ( $all_pages as $page ) : ?>
							<?php if ( in_array( $page->ID, $displayed_ids, true ) ) continue; ?>
							<p>
								<label>
									<input type="checkbox"
											name="belief_pages[]"
											value="<?php echo esc_attr( (string) $page->ID ); ?>"
									/>
									<?php echo esc_html( $page->post_title ); ?>
								</label>
							</p>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<p class="submit">
					<button type="submit"
							name="submit_belief_settings"
							class="button button-primary">
						<?php esc_html_e( 'Zapisz', 'kzmielec' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}
}
