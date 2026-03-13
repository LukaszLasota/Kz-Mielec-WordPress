<?php

namespace Kzmielec\BasicTheme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

/**
 * Class Menu
 *
 * Handles WordPress menu registration.
 */
class Menu implements ActionHookInterface {

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
		add_action( 'init', array( $this, 'kzmielec_menu' ) );
	}

	/**
	 * Register navigation menus.
	 *
	 * @return void
	 */
	public function kzmielec_menu(): void {
		register_nav_menus(
			array(
				'primary'      => esc_html__( 'Główne menu', 'kzmielec' ),
				'footer-one'   => esc_html__( 'Menu stopka', 'kzmielec' ),
				'footer-two'   => esc_html__( 'Menu stopka na skróty', 'kzmielec' ),
				'footer-three' => esc_html__( 'Menu stopka kontakt', 'kzmielec' ),
			)
		);
	}
}
