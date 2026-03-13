<?php
/**
 * Theme bootstrap class
 *
 * Central initialization point for all theme components.
 *
 * @package Kzmielec
 */

namespace Kzmielec;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Theme
 *
 * Bootstraps and initializes all theme components with proper load order
 * and context-aware loading (admin vs frontend).
 */
class Theme {

	/**
	 * Theme components to initialize on both frontend and admin.
	 *
	 * Order matters: Setup must be first as it registers theme supports.
	 *
	 * @var array<string>
	 */
	private array $components = array(
		// Setup must be first - registers theme supports.
		BasicTheme\Setup::class,
		BasicTheme\Menu::class,
		BasicTheme\RegisterAssets::class,
		BasicTheme\Rewrite::class,
		Widgets\RegisterWidgets::class,
		Core\PatternAssets::class,
		Core\GroupLinkSupport::class,
		Core\PerformanceOptimizer::class,
	);

	/**
	 * Admin-only components.
	 *
	 * These are loaded only when is_admin() is true to optimize frontend performance.
	 * Order matters: ThemeSettingsPage must be before its subpages.
	 *
	 * @var array<string>
	 */
	private array $admin_components = array(
		Admin\ThemeSettingsPage::class,
		Admin\LogoSettings::class,
		Admin\SocialSettings::class,
		Admin\BeliefSettings::class,
		Admin\BeliefMetaBox::class,
		Core\SvgSupport::class,
	);

	/**
	 * Constructor.
	 *
	 * Initializes theme components based on context (frontend vs admin).
	 */
	public function __construct() {
		$this->load_components();

		if ( is_admin() ) {
			$this->load_admin_components();
		}
	}

	/**
	 * Load frontend and universal components.
	 *
	 * Instantiates each component in the order specified in $components array.
	 *
	 * @return void
	 */
	private function load_components(): void {
		foreach ( $this->components as $component ) {
			new $component();
		}
	}

	/**
	 * Load admin-only components.
	 *
	 * Instantiates admin components only when running in WordPress admin context.
	 *
	 * @return void
	 */
	private function load_admin_components(): void {
		foreach ( $this->admin_components as $component ) {
			new $component();
		}
	}
}
