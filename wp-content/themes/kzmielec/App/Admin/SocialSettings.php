<?php
/**
 * SocialSettings class
 *
 * Handles social media links settings page in WordPress admin.
 *
 * @package Kzmielec\Admin
 */

namespace Kzmielec\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class SocialSettings
 *
 * Provides admin interface for managing social media links displayed in footer.
 */
class SocialSettings implements ActionHookInterface {

	/**
	 * Nonce action name for security verification.
	 */
	private const NONCE_ACTION = 'save_social_settings';

	/**
	 * Nonce field name.
	 */
	private const NONCE_NAME = 'social_settings_nonce';

	/**
	 * Social media platforms configuration.
	 *
	 * @var array<int, array{option: string, label: string}>
	 */
	private const PLATFORMS = array(
		array(
			'option' => 'kzmielec_social_facebook',
			'label'  => 'Facebook',
		),
		array(
			'option' => 'kzmielec_social_instagram',
			'label'  => 'Instagram',
		),
		array(
			'option' => 'kzmielec_social_youtube',
			'label'  => 'YouTube',
		),
		array(
			'option' => 'kzmielec_social_flickr',
			'label'  => 'Flickr',
		),
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
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
	}

	/**
	 * Add social settings page as subpage of Theme Settings.
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		add_submenu_page(
			ThemeSettingsPage::MENU_SLUG,
			'Media spolecznosciowe',
			'Social Media',
			'manage_options',
			'kzmielec-social',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Display social settings page.
	 *
	 * @return void
	 */
	public function display_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Nie masz wystarczajacych uprawnien, aby uzyskac dostep do tej strony.', 'kzmielec' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in handle_form_submission().
		if ( isset( $_POST['submit_social_settings'] ) ) {
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

		foreach ( self::PLATFORMS as $platform ) {
			$value = isset( $_POST[ $platform['option'] ] ) ? esc_url_raw( wp_unslash( $_POST[ $platform['option'] ] ) ) : '';
			update_option( $platform['option'], $value );
		}

		add_settings_error(
			'kzmielec_social',
			'social_saved',
			__( 'Linki do mediow spolecznosciowych zostaly zapisane.', 'kzmielec' ),
			'updated'
		);
	}

	/**
	 * Render settings form HTML.
	 *
	 * @return void
	 */
	private function render_settings_form(): void {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'kzmielec_social' ); ?>

			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<table class="form-table">
					<?php foreach ( self::PLATFORMS as $platform ) : ?>
						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( $platform['option'] ); ?>">
									<?php echo esc_html( $platform['label'] ); ?>
								</label>
							</th>
							<td>
								<input type="url"
										id="<?php echo esc_attr( $platform['option'] ); ?>"
										name="<?php echo esc_attr( $platform['option'] ); ?>"
										value="<?php echo esc_attr( get_option( $platform['option'], '' ) ); ?>"
										class="regular-text"
										placeholder="https://"
								/>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<p class="submit">
					<button type="submit"
							name="submit_social_settings"
							class="button button-primary">
						<?php esc_html_e( 'Zapisz', 'kzmielec' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}
}
