<?php
/**
 * kzmielec Theme - Main functions file
 *
 * @package Kzmielec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load Composer autoloader.
require_once get_template_directory() . '/vendor/autoload.php';

// Bootstrap theme.
new Kzmielec\Theme();
