<?php

namespace Church\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Church\Interfaces\ActionHookInterface;

/**
 * Class RegisterWidgets
 *
 * Handles WordPress widget area registration.
 */
class RegisterWidgets implements ActionHookInterface {

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
		add_action( 'widgets_init', array( $this, 'church_widgets_init' ) );
	}

	/**
	 * Register widget areas for footer.
	 *
	 * @return void
	 */
	public function church_widgets_init(): void {
		register_sidebar(
			array(
				'name'          => esc_html__( 'Menu stopka - Nagłówek 1', 'church' ),
				'id'            => 'footer-header-1',
				'description'   => esc_html__( 'Dodaj widgety tutaj.', 'church' ),
				'before_widget' => '<section id="%1$s" class="widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h4>',
				'after_title'   => '</h4>',
			)
		);

		register_sidebar(
			array(
				'name'          => esc_html__( 'Menu stopka - Nagłówek 2', 'church' ),
				'id'            => 'footer-header-2',
				'description'   => esc_html__( 'Dodaj widgety tutaj.', 'church' ),
				'before_widget' => '<section id="%1$s" class="widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h4>',
				'after_title'   => '</h4>',
			)
		);

		register_sidebar(
			array(
				'name'          => esc_html__( 'Menu stopka - Nagłówek 3', 'church' ),
				'id'            => 'footer-header-3',
				'description'   => esc_html__( 'Dodaj widgety tutaj.', 'church' ),
				'before_widget' => '<section id="%1$s" class="widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h4>',
				'after_title'   => '</h4>',
			)
		);
	}
}
