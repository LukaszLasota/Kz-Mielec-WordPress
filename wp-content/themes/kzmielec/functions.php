<?php
/**
 * Church Theme - Main functions file
 *
 * @package Church
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load Composer autoloader.
require_once get_template_directory() . '/vendor/autoload.php';

// Bootstrap theme.
new Church\Theme();
