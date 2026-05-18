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
use CustomBlockPackage\Admin\FacebookSettings;
use CustomBlockPackage\Admin\MeetingMeta;
use CustomBlockPackage\Assets\AssetsManager;
use CustomBlockPackage\Blocks\RegisterBlocks;
use CustomBlockPackage\Cache\BlockCache;
use CustomBlockPackage\Cron\FacebookFeedCron;
use CustomBlockPackage\Rest\FacebookFeedController;

new RegisterBlocks();
new AssetsManager();
new FacebookFeedCron();
new FacebookFeedController();

if ( is_admin() ) {
	new FacebookSettings();
	new MeetingMeta();
}

// Facebook feed cron activation/deactivation.
register_activation_hook( __FILE__, array( FacebookFeedCron::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( FacebookFeedCron::class, 'deactivate' ) );

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

// Invalidate navigable-tiles cache on relevant changes.
add_action(
	'save_post_meetings',
	static function (): void {
		BlockCache::flush( BlockCache::NAVIGABLE_TILES_PREFIX );
	}
);
add_action(
	'save_post_page',
	static function (): void {
		BlockCache::flush( BlockCache::NAVIGABLE_TILES_PREFIX );
	}
);
add_action(
	'update_option_kzmielec_belief_pages',
	static function (): void {
		BlockCache::flush( BlockCache::NAVIGABLE_TILES_PREFIX );
	}
);
add_action(
	'add_option_kzmielec_belief_pages',
	static function (): void {
		BlockCache::flush( BlockCache::NAVIGABLE_TILES_PREFIX );
	}
);
