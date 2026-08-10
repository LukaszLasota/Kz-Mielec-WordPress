<?php
/**
 * Plugin Name: Custom Posts
 * Description: Wtyczka do rejestracji niestandardowych postów i taksonomii.
 * Version: 1.0.0
 * Author: Łukasz Lasota
 * Text Domain: custom-posts
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package CustomPostsPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	function ( $class_name ) {
		if ( strpos( $class_name, 'CustomPostsPlugin\\' ) === 0 ) {
			$class_name = str_replace( 'CustomPostsPlugin\\', '', $class_name );
			$class_name = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name );
			$file       = plugin_dir_path( __FILE__ ) . 'src/' . $class_name . '.php';

			if ( file_exists( $file ) ) {
				include $file;
			}
		}
	}
);

// Load the translation catalogue before the post type labels are registered.
add_action(
	'init',
	function () {
		load_plugin_textdomain( 'custom-posts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	},
	0
);

add_action(
	'plugins_loaded',
	function () {
		if ( class_exists( '\CustomPostsPlugin\Posts\RegisterPosts' ) ) {
			new \CustomPostsPlugin\Posts\RegisterPosts();
		}

		if ( class_exists( '\CustomPostsPlugin\Posts\CustomColumns' ) ) {
			new \CustomPostsPlugin\Posts\CustomColumns();
		}

		if ( class_exists( '\CustomPostsPlugin\Posts\MeetingsArchiveSlugs' ) ) {
			new \CustomPostsPlugin\Posts\MeetingsArchiveSlugs();
		}
	}
);
