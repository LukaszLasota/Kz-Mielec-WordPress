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
		Core\BlockStyles::class,
		Core\GroupLinkSupport::class,
		Core\PerformanceOptimizer::class,
		Core\FeedCachePurge::class,
		Core\FeedRefreshButton::class,
		Core\ModernImages::class,
		// Both contexts on purpose: the gettext filter has to run on the front
		// end, while registering the strings for the panel only makes sense in
		// admin. The class guards that part itself.
		Core\StringTranslations::class,
		// The safety net for Polylang being switched off. It lives in the theme
		// rather than in the `kzmielec-translate` plugin that created the translated
		// content, because the theme cannot be deactivated and that plugin is a
		// migration tool which should be free to go. Does nothing while Polylang is
		// active. See the class for the full reasoning.
		Core\TranslationGuard::class,
		// The social feeds carry the congregation's Polish posts on every language
		// version, so they have to declare `lang="pl"` — WCAG 3.1.2.
		Core\SocialFeedLanguage::class,
		// One source for the congregation's contact details, fed into block content
		// through the core Block Bindings API. Before this, the address, phone number,
		// tax number, e-mail and bank account existed in four independent copies — one
		// per language version of the front page — plus a fifth in the theme's own
		// structured data, and they had already drifted apart unnoticed.
		Contact\ContactBindings::class,
		Seo\YoastFallbacks::class,
		// Adds the `x-default` hreflang, which Polylang skips whenever the default
		// language is hidden at the site root — as it is here.
		Seo\Hreflang::class,
		// The Scripture attribution travels with the quotations instead of sitting in
		// the footer of every page. See the class for the licence reasoning.
		Seo\ScriptureNotice::class,
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
		Admin\ContactSettings::class,
		Admin\BeliefSettings::class,
		Admin\BeliefPageMeta::class,
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
