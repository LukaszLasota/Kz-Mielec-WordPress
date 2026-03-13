<?php

namespace Kzmielec\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;

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
		add_action( 'widgets_init', array( $this, 'kzmielec_widgets_init' ) );
	}

	/**
	 * Register widget areas for footer.
	 *
	 * @return void
	 */
	public function kzmielec_widgets_init(): void {
		register_sidebar(
			array(
				'name'          => esc_html__( 'Stopka - tekst', 'kzmielec' ),
				'id'            => 'footer-text',
				'description'   => esc_html__( 'Tekst w stopce (np. copyright).', 'kzmielec' ),
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h4>',
				'after_title'   => '</h4>',
			)
		);

		register_sidebar(
			array(
				'name'          => esc_html__( 'Stopka - social media', 'kzmielec' ),
				'id'            => 'footer-social',
				'description'   => esc_html__( 'Ikony social media w stopce.', 'kzmielec' ),
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h4>',
				'after_title'   => '</h4>',
			)
		);
	}
}
