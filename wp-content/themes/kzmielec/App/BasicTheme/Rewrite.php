<?php

namespace Kzmielec\BasicTheme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class Rewrite
 *
 * Handles WordPress rewrite rules customization.
 */
class Rewrite implements ActionHookInterface {

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
		add_action( 'init', array( $this, 'custom_pagination_base' ) );
	}

	/**
	 * Set custom pagination base for Polish language.
	 *
	 * Changes pagination URL from 'page' to 'strona'.
	 *
	 * @return void
	 */
	public function custom_pagination_base(): void {
		global $wp_rewrite;
		$wp_rewrite->pagination_base = 'strona';
	}
}
