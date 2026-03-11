<?php

namespace Church\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ActionHookWithArgsInterface {

	/**
	 * Register action with arguments.
	 *
	 * @param array<string, mixed> $args Arguments for the action.
	 */
	public function register_add_action_with_arguments( array $args ): void;
}
