<?php

namespace Church\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface ActionHookInterface
 *
 * Defines contract for classes that register WordPress action hooks.
 */
interface ActionHookInterface {

	/**
	 * Register WordPress action hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void;
}
