<?php
/**
 * MasonrySettings class
 *
 * Handles masonry layout settings for the news archive page.
 *
 * @package Church\Admin
 */

namespace Church\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Church\Interfaces\ActionHookInterface;

/**
 * Class MasonrySettings
 *
 * Provides admin interface for masonry column count per breakpoint.
 */
class MasonrySettings implements ActionHookInterface {

	/**
	 * Option name for columns from 600px.
	 */
	public const OPTION_COLUMNS_600 = 'church_masonry_columns_600';

	/**
	 * Option name for columns from 1024px.
	 */
	public const OPTION_COLUMNS_1024 = 'church_masonry_columns_1024';

	/**
	 * Default columns from 600px.
	 */
	private const DEFAULT_COLUMNS_600 = 2;

	/**
	 * Default columns from 1024px.
	 */
	private const DEFAULT_COLUMNS_1024 = 3;

	/**
	 * Maximum number of columns.
	 */
	private const MAX_COLUMNS = 4;

	/**
	 * Nonce action name.
	 */
	private const NONCE_ACTION = 'save_masonry_settings';

	/**
	 * Nonce field name.
	 */
	private const NONCE_NAME = 'masonry_settings_nonce';

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
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 20 );
		add_action( 'wp_head', array( $this, 'output_masonry_css_vars' ) );
	}

	/**
	 * Add settings page as subpage of Theme Settings.
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		add_submenu_page(
			ThemeSettingsPage::MENU_SLUG,
			'Masonry (Aktualności)',
			'Masonry',
			'manage_options',
			'church-masonry',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Get column count for 600px breakpoint.
	 *
	 * @return int
	 */
	public static function get_columns_600(): int {
		$columns = (int) get_option( self::OPTION_COLUMNS_600, self::DEFAULT_COLUMNS_600 );
		return max( 1, min( self::MAX_COLUMNS, $columns ) );
	}

	/**
	 * Get column count for 1024px breakpoint.
	 *
	 * @return int
	 */
	public static function get_columns_1024(): int {
		$columns = (int) get_option( self::OPTION_COLUMNS_1024, self::DEFAULT_COLUMNS_1024 );
		return max( 1, min( self::MAX_COLUMNS, $columns ) );
	}

	/**
	 * Display settings page.
	 *
	 * @return void
	 */
	public function display_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'church' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in handle_form_submission().
		if ( isset( $_POST['submit_masonry_settings'] ) ) {
			$this->handle_form_submission();
		}

		$this->render_settings_form( self::get_columns_600(), self::get_columns_1024() );
	}

	/**
	 * Handle form submission.
	 *
	 * @return void
	 */
	private function handle_form_submission(): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'church' ) );
		}

		$columns_600 = isset( $_POST['masonry_columns_600'] ) ? absint( $_POST['masonry_columns_600'] ) : self::DEFAULT_COLUMNS_600;
		$columns_600 = max( 1, min( self::MAX_COLUMNS, $columns_600 ) );

		$columns_1024 = isset( $_POST['masonry_columns_1024'] ) ? absint( $_POST['masonry_columns_1024'] ) : self::DEFAULT_COLUMNS_1024;
		$columns_1024 = max( 1, min( self::MAX_COLUMNS, $columns_1024 ) );

		update_option( self::OPTION_COLUMNS_600, $columns_600 );
		update_option( self::OPTION_COLUMNS_1024, $columns_1024 );

		add_settings_error(
			'church_masonry',
			'masonry_saved',
			__( 'Ustawienia masonry zostały zapisane.', 'church' ),
			'updated'
		);
	}

	/**
	 * Render settings form.
	 *
	 * @param int $columns_600  Columns from 600px.
	 * @param int $columns_1024 Columns from 1024px.
	 * @return void
	 */
	private function render_settings_form( int $columns_600, int $columns_1024 ): void {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'church_masonry' ); ?>

			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="masonry_columns_600"><?php esc_html_e( 'Kolumny od 600px', 'church' ); ?></label>
						</th>
						<td>
							<select id="masonry_columns_600" name="masonry_columns_600">
								<?php for ( $i = 1; $i <= self::MAX_COLUMNS; $i++ ) : ?>
									<option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( $columns_600, $i ); ?>>
										<?php echo esc_html( (string) $i ); ?>
									</option>
								<?php endfor; ?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Tablety i małe ekrany (600px–1023px). Poniżej 600px zawsze 1 kolumna.', 'church' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="masonry_columns_1024"><?php esc_html_e( 'Kolumny od 1024px', 'church' ); ?></label>
						</th>
						<td>
							<select id="masonry_columns_1024" name="masonry_columns_1024">
								<?php for ( $i = 1; $i <= self::MAX_COLUMNS; $i++ ) : ?>
									<option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( $columns_1024, $i ); ?>>
										<?php echo esc_html( (string) $i ); ?>
									</option>
								<?php endfor; ?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Desktop i duże ekrany (od 1024px).', 'church' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit"
							name="submit_masonry_settings"
							class="button button-primary">
						<?php esc_html_e( 'Zapisz', 'church' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Output CSS custom properties for masonry layout.
	 *
	 * @return void
	 */
	public function output_masonry_css_vars(): void {
		if ( ! is_home() && ! is_archive() && ! is_search() ) {
			return;
		}

		$columns_600  = self::get_columns_600();
		$columns_1024 = self::get_columns_1024();
		$gap          = 25;

		printf(
			'<style>:root{--masonry-columns-600:%s;--masonry-columns-1024:%s;--masonry-gap:%spx}</style>' . "\n",
			esc_attr( (string) $columns_600 ),
			esc_attr( (string) $columns_1024 ),
			esc_attr( (string) $gap )
		);
	}
}