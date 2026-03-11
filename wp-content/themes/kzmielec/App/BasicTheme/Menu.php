<?php

namespace Church\BasicTheme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Church\Interfaces\ActionHookInterface;
use Church\Interfaces\FilterHookInterface;

/**
 * Class Menu
 *
 * Handles WordPress menu registration and customization.
 */
class Menu implements ActionHookInterface, FilterHookInterface {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
		$this->register_add_filter();
	}

	/**
	 * Register WordPress action hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'init', array( $this, 'church_menu' ) );
	}

	/**
	 * Register WordPress filter hooks.
	 *
	 * @return void
	 */
	public function register_add_filter(): void {
		add_filter( 'walker_nav_menu_start_el', array( $this, 'logo_menu_item' ), 10, 4 );
	}

	/**
	 * Register navigation menus.
	 *
	 * @return void
	 */
	public function church_menu(): void {
		register_nav_menus(
			array(
				'primary'      => esc_html__( 'Główne menu', 'church' ),
				'footer-one'   => esc_html__( 'Menu stopka', 'church' ),
				'footer-two'   => esc_html__( 'Menu stopka na skróty', 'church' ),
				'footer-three' => esc_html__( 'Menu stopka kontakt', 'church' ),
			)
		);
	}

	/**
	 * Add icon to menu item.
	 *
	 * @param string $item_output The menu item output.
	 * @param mixed  $item        Menu item object.
	 * @param int    $depth       Depth of menu item.
	 * @param mixed  $args        Menu arguments.
	 * @return string Modified menu item output.
	 */
	public function logo_menu_item( string $item_output, mixed $item, int $depth, mixed $args ): string {
		$icon = get_field( 'icon_logo', $item );

		if ( $icon && is_array( $icon ) ) {
			$item_output = '<img src="' . esc_url( $icon['url'] ) . '" alt="" class="menu-icon" aria-hidden="true" />' . $item_output;
		}

		return $item_output;
	}
}
