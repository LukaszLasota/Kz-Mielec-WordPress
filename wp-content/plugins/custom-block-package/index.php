<?php
/**
 * Plugin Name:       Custom Block Package
 * Description:       A plugin for adding custom gutenberg blocks to a theme.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.2
 * Author:            Łukasz Lasota
 * Author URI:        https://github.com/LukaszLasota
 * Text Domain:       custom-block-package
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package CustomBlockPackage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Setup.
define( 'UP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UP_PLUGIN_FILE', __FILE__ );
define( 'UP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load autoloader.
require_once UP_PLUGIN_DIR . 'app/Autoloader.php';

// Initialize plugin classes.
use CustomBlockPackage\Blocks\RegisterBlocks;
use CustomBlockPackage\Assets\AssetsManager;
use CustomBlockPackage\Cache\BlockCache;

new RegisterBlocks();
new AssetsManager();

// Invalidate block caches on post save.
add_action(
	'save_post_post',
	function (): void {
		BlockCache::flush( BlockCache::NEWS_SLIDER_PREFIX );
	}
);
add_action(
	'save_post_meetings',
	function (): void {
		BlockCache::flush( BlockCache::MEETING_LIST_PREFIX );
	}
);
