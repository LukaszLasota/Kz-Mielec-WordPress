<?php
/**
 * Settings screen for the DeepL key.
 *
 * @package KzmielecTranslate
 */

declare(strict_types=1);

namespace KzmielecTranslate\Admin;

use KzmielecTranslate\Services\DeeplClient;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One field and a usage readout.
 *
 * Follows the shape of custom-block-package's FacebookSettings: the secret lives
 * in wp_options and is entered in the admin, which is the convention already
 * established in this project. A wp-config.php constant overrides it for CLI use
 * and keeps the key out of database backups entirely.
 */
class DeeplSettings {

	/**
	 * Option holding the key.
	 */
	public const OPTION_KEY = 'kzt_deepl_api_key';

	/**
	 * Menu slug.
	 */
	private const MENU_SLUG = 'kzt-deepl';

	/**
	 * Nonce action.
	 */
	private const NONCE_ACTION = 'kzt_save_deepl';

	/**
	 * Nonce field name.
	 */
	private const NONCE_FIELD = 'kzt_deepl_nonce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_post_kzt_save_deepl', array( $this, 'save' ) );
	}

	/**
	 * Register the screen under Tools.
	 *
	 * @return void
	 */
	public function add_page(): void {
		add_management_page(
			__( 'Tłumaczenie DeepL', 'kzmielec-translate' ),
			__( 'Tłumaczenie DeepL', 'kzmielec-translate' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the form and the quota readout.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Brak uprawnień.', 'kzmielec-translate' ) );
		}

		$stala = defined( 'KZMIELEC_DEEPL_API_KEY' );
		$klucz = (string) get_option( self::OPTION_KEY, '' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Tłumaczenie DeepL', 'kzmielec-translate' ); ?></h1>

			<?php if ( $stala ) : ?>
				<div class="notice notice-info inline">
					<p><?php esc_html_e( 'Klucz jest ustawiony stałą KZMIELEC_DEEPL_API_KEY w wp-config.php i ma pierwszeństwo nad polem poniżej.', 'kzmielec-translate' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="kzt_save_deepl">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="kzt_key"><?php esc_html_e( 'Klucz API', 'kzmielec-translate' ); ?></label>
						</th>
						<td>
							<input type="password" id="kzt_key" name="kzt_key" class="regular-text" value="<?php echo esc_attr( $klucz ); ?>" autocomplete="off">
							<p class="description">
								<?php esc_html_e( 'Format: 8-4-4-4-12 znaków. Klucz darmowego progu ma na końcu „:fx” — endpoint dobiera się z tego sufiksu automatycznie.', 'kzmielec-translate' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Zapisz klucz', 'kzmielec-translate' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Zużycie limitu', 'kzmielec-translate' ); ?></h2>
			<?php
			$klient = DeeplClient::from_settings();

			if ( null === $klient ) {
				echo '<p>' . esc_html__( 'Brak klucza — nie ma czego sprawdzić.', 'kzmielec-translate' ) . '</p>';
			} else {
				try {
					$u       = $klient->usage();
					$procent = $u['character_limit'] > 0
						? number_format_i18n( round( $u['character_count'] / $u['character_limit'] * 100, 1 ), 1 )
						: '0';

					printf(
						'<p>%s</p>',
						esc_html(
							sprintf(
								/* translators: 1: used characters, 2: limit, 3: percentage. */
								__( 'Wykorzystano %1$s z %2$s znaków (%3$s%%).', 'kzmielec-translate' ),
								number_format_i18n( $u['character_count'] ),
								number_format_i18n( $u['character_limit'] ),
								$procent
							)
						)
					);
				} catch ( \RuntimeException $e ) {
					printf(
						'<div class="notice notice-error inline"><p>%s</p></div>',
						esc_html( $e->getMessage() )
					);
				}
			}
			?>
		</div>
		<?php
	}

	/**
	 * Persist the key.
	 *
	 * @return void
	 */
	public function save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Brak uprawnień.', 'kzmielec-translate' ) );
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

		$klucz = isset( $_POST['kzt_key'] )
			? sanitize_text_field( wp_unslash( $_POST['kzt_key'] ) )
			: '';

		update_option( self::OPTION_KEY, $klucz );

		wp_safe_redirect( add_query_arg( 'page', self::MENU_SLUG, admin_url( 'tools.php' ) ) );
		exit;
	}
}
