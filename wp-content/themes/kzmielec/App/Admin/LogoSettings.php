<?php
/**
 * LogoSettings class
 *
 * Handles custom logo settings page in WordPress admin.
 *
 * @package Kzmielec\Admin
 */

namespace Kzmielec\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class LogoSettings
 *
 * Provides admin interface for custom logo upload and management.
 */
class LogoSettings implements ActionHookInterface {

	/**
	 * Nonce action name for security verification.
	 */
	private const NONCE_ACTION = 'save_custom_logo';

	/**
	 * Nonce field name.
	 */
	private const NONCE_NAME = 'custom_logo_nonce';

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
		add_action( 'admin_menu', array( $this, 'add_logo_settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media_uploader' ) );
	}

	/**
	 * Add logo settings page as subpage of Theme Settings.
	 *
	 * @return void
	 */
	public function add_logo_settings_page(): void {
		add_submenu_page(
			ThemeSettingsPage::MENU_SLUG,
			'Ustawienia logo',
			'Logo',
			'manage_options',
			'kzmielec-logo',
			array( $this, 'display_logo_settings_page' )
		);
	}

	/**
	 * Display logo settings page with upload form.
	 *
	 * @return void
	 */
	public function display_logo_settings_page(): void {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Nie masz wystarczających uprawnień, aby uzyskać dostęp do tej strony.', 'kzmielec' ) );
		}

		// Handle form submission.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in handle_form_submission().
		if ( isset( $_POST['submit_image_selector'] ) ) {
			$this->handle_form_submission();
		}

		// Get current logo settings.
		$image_url = get_option( 'my_custom_logo_setting', '' );
		$image_id  = get_option( 'my_custom_logo_setting_id', '' );

		// Display form.
		$this->render_settings_form( $image_url, $image_id );
	}

	/**
	 * Handle form submission with security checks.
	 *
	 * @return void
	 */
	private function handle_form_submission(): void {
		// Verify nonce.
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Weryfikacja bezpieczeństwa nie powiodła się. Spróbuj ponownie.', 'kzmielec' ) );
		}

		// Sanitize and validate input.
		$image_url = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';
		$image_id  = isset( $_POST['image_id'] ) ? absint( $_POST['image_id'] ) : 0;

		// Update options.
		update_option( 'my_custom_logo_setting', $image_url );
		update_option( 'my_custom_logo_setting_id', $image_id );

		// Show success message.
		add_settings_error(
			'my_custom_logo',
			'logo_saved',
			__( 'Logo zostało zapisane.', 'kzmielec' ),
			'updated'
		);
	}

	/**
	 * Render settings form HTML.
	 *
	 * @param string $image_url Current logo URL.
	 * @param string $image_id  Current logo attachment ID.
	 * @return void
	 */
	private function render_settings_form( string $image_url, string $image_id ): void {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'my_custom_logo' ); ?>

			<img id="logo-preview"
				src="<?php echo esc_url( $image_url ); ?>"
				alt="<?php esc_attr_e( 'Podgląd logo', 'kzmielec' ); ?>"
				style="max-width: 300px; max-height: 300px; display: block; margin-bottom: 20px;"
			/>

			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<input type="hidden"
						id="image_url"
						name="image_url"
						value="<?php echo esc_attr( $image_url ); ?>"
				/>

				<input type="hidden"
						id="image_id"
						name="image_id"
						value="<?php echo esc_attr( $image_id ); ?>"
				/>

				<p>
					<button type="button"
							id="upload-btn"
							class="button button-secondary">
						<?php esc_html_e( 'Wybierz Logo', 'kzmielec' ); ?>
					</button>

					<button type="submit"
							name="submit_image_selector"
							class="button button-primary">
						<?php esc_html_e( 'Zapisz', 'kzmielec' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueue media uploader scripts for logo upload.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_media_uploader( string $hook ): void {
		// Only load on our settings page.
		if ( 'ustawienia-motywu_page_kzmielec-logo' !== $hook ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			'kzmielec-logo-uploader',
			get_template_directory_uri() . '/assets/js/logo.js',
			array(),
			(string) filemtime( get_template_directory() . '/assets/js/logo.js' ),
			true
		);
	}
}
