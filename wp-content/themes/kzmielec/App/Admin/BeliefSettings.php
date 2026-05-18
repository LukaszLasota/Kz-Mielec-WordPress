<?php
/**
 * Belief Settings admin page.
 *
 * Stores ordered list of belief subpage IDs in wp_options.
 *
 * @package Kzmielec
 */

declare(strict_types=1);

namespace Kzmielec\Admin;

use Kzmielec\Interfaces\ActionHookInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BeliefSettings
 *
 * Subpage of ThemeSettingsPage with drag-and-drop multi-select for belief pages.
 */
class BeliefSettings implements ActionHookInterface {

	/**
	 * Option key for belief page IDs.
	 */
	public const OPTION_BELIEF_PAGES = 'kzmielec_belief_pages';

	/**
	 * Menu slug.
	 */
	public const MENU_SLUG = 'kzmielec-belief-settings';

	/**
	 * Parent menu slug (ThemeSettingsPage).
	 */
	private const PARENT_SLUG = 'kzmielec-theme-settings';

	/**
	 * Nonce action.
	 */
	private const NONCE_ACTION = 'kzmielec_belief_settings_save';

	/**
	 * Nonce field.
	 */
	private const NONCE_FIELD = 'kzmielec_belief_settings_nonce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'admin_menu', array( $this, 'add_submenu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add submenu under ThemeSettingsPage.
	 *
	 * @return void
	 */
	public function add_submenu(): void {
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Wiara', 'kzmielec' ),
			__( 'Wiara', 'kzmielec' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue Sortable.js on this admin page.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, self::MENU_SLUG ) ) {
			return;
		}

		$asset_path = get_template_directory() . '/assets/js/admin/belief-settings.js';
		$asset_uri  = get_template_directory_uri() . '/assets/js/admin/belief-settings.js';

		if ( file_exists( $asset_path ) ) {
			wp_enqueue_script(
				'kzmielec-belief-settings',
				$asset_uri,
				array( 'jquery' ),
				(string) filemtime( $asset_path ),
				true
			);
		}
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'kzmielec' ) );
		}

		$this->handle_form_submission();

		$selected_ids = (array) get_option( self::OPTION_BELIEF_PAGES, array() );
		$selected_ids = array_filter( array_map( 'intval', $selected_ids ) );

		$all_pages = get_pages(
			array(
				'sort_column' => 'post_title',
				'sort_order'  => 'ASC',
			)
		);
		if ( ! is_array( $all_pages ) ) {
			$all_pages = array();
		}

		$selected_pages   = array();
		$unselected_pages = array();
		foreach ( $all_pages as $page ) {
			if ( in_array( $page->ID, $selected_ids, true ) ) {
				$selected_pages[ $page->ID ] = $page;
			} else {
				$unselected_pages[ $page->ID ] = $page;
			}
		}

		// Order selected pages by saved order.
		$ordered_selected = array();
		foreach ( $selected_ids as $id ) {
			if ( isset( $selected_pages[ $id ] ) ) {
				$ordered_selected[] = $selected_pages[ $id ];
			}
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Wiara — Ustawienia', 'kzmielec' ); ?></h1>

			<?php settings_errors( 'kzmielec_belief' ); ?>

			<p><?php esc_html_e( 'Wybierz strony do wyświetlenia w sekcji "W co i jak wierzymy" (na stronie głównej i jako nawigacja na podstronach wiary).', 'kzmielec' ); ?></p>

			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>

				<h2><?php esc_html_e( 'Wybrane strony (przeciągnij, aby zmienić kolejność)', 'kzmielec' ); ?></h2>

				<ul id="kzmielec-belief-selected" class="kzmielec-belief-list">
					<?php foreach ( $ordered_selected as $page ) : ?>
						<li class="kzmielec-belief-item" data-page-id="<?php echo esc_attr( (string) $page->ID ); ?>">
							<span class="kzmielec-belief-handle" aria-hidden="true">☰</span>
							<span class="kzmielec-belief-title"><?php echo esc_html( $page->post_title ); ?></span>
							<button type="button" class="button button-small kzmielec-belief-remove" aria-label="<?php esc_attr_e( 'Usuń', 'kzmielec' ); ?>">✕</button>
							<input type="hidden" name="kzmielec_belief_pages[]" value="<?php echo esc_attr( (string) $page->ID ); ?>">
						</li>
					<?php endforeach; ?>
				</ul>

				<h2><?php esc_html_e( 'Dodaj stronę', 'kzmielec' ); ?></h2>
				<select id="kzmielec-belief-add" class="regular-text">
					<option value=""><?php esc_html_e( '— wybierz —', 'kzmielec' ); ?></option>
					<?php foreach ( $unselected_pages as $page ) : ?>
						<option value="<?php echo esc_attr( (string) $page->ID ); ?>" data-title="<?php echo esc_attr( $page->post_title ); ?>">
							<?php echo esc_html( $page->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button" id="kzmielec-belief-add-button"><?php esc_html_e( 'Dodaj', 'kzmielec' ); ?></button>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Zapisz zmiany', 'kzmielec' ); ?></button>
				</p>
			</form>

			<style>
				.kzmielec-belief-list { list-style: none; padding: 0; margin: 0 0 24px; max-width: 600px; }
				.kzmielec-belief-item { display: flex; align-items: center; gap: 12px; padding: 10px 16px; background: #fff; border: 1px solid #ddd; margin-bottom: 4px; border-radius: 4px; }
				.kzmielec-belief-handle { cursor: grab; color: #888; }
				.kzmielec-belief-title { flex: 1; }
				.kzmielec-belief-item.sortable-ghost { opacity: 0.4; }
			</style>
		</div>
		<?php
	}

	/**
	 * Handle form submission.
	 *
	 * @return void
	 */
	private function handle_form_submission(): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kzmielec' ) );
		}

		$ids = isset( $_POST['kzmielec_belief_pages'] ) && is_array( $_POST['kzmielec_belief_pages'] )
			? array_map( 'absint', wp_unslash( $_POST['kzmielec_belief_pages'] ) )
			: array();

		$ids = array_values( array_filter( $ids ) );

		update_option( self::OPTION_BELIEF_PAGES, $ids );

		add_settings_error(
			'kzmielec_belief',
			'saved',
			__( 'Ustawienia zapisane.', 'kzmielec' ),
			'updated'
		);
	}
}
