<?php

namespace Church\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface FilterHookInterface
 *
 * Defines contract for classes that register WordPress filter hooks.
 */
interface FilterHookInterface {

	/**
	 * Register WordPress filter hooks.
	 *
	 * @return void
	 */
	public function register_add_filter(): void;
}
