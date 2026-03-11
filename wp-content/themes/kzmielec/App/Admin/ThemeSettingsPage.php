<?php
/**
 * ThemeSettingsPage class
 *
 * Registers the top-level "Ustawienia motywu" admin menu page.
 *
 * @package Church\Admin
 */

namespace Church\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Church\Interfaces\ActionHookInterface;

/**
 * Class ThemeSettingsPage
 *
 * Creates the parent admin menu page that holds Logo and Masonry subpages.
 */
class ThemeSettingsPage implements ActionHookInterface {

	/**
	 * Menu slug used by subpages as parent_slug.
	 */
	public const MENU_SLUG = 'church-settings';

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
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
	}

	/**
	 * Add top-level menu page.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		add_menu_page(
			'Ustawienia motywu',
			'Ustawienia motywu',
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' ),
			'dashicons-admin-generic',
			61
		);
	}

	/**
	 * Render the main settings page (redirects to first subpage).
	 *
	 * @return void
	 */
	public function render_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ustawienia motywu', 'church' ); ?></h1>
			<p><?php esc_html_e( 'Wybierz podstronę z menu po lewej stronie.', 'church' ); ?></p>
		</div>
		<?php
	}
}