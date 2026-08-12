<?php
/**
 * Plugin Name: Kzmielec Translate
 * Description: Narzędzie migracyjne: wypełnia tłumaczenia treści przez DeepL. Po wypełnieniu można dezaktywować.
 * Version: 1.0.0
 * Author: Łukasz Lasota
 * Text Domain: kzmielec-translate
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package KzmielecTranslate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin root, so classes can locate the glossary files without guessing at
 * relative paths from inside app/.
 */
define( 'KZMIELEC_TRANSLATE_DIR', plugin_dir_path( __FILE__ ) );

/*
 * Same shape as custom-posts: a small autoloader over app/, because the
 * project's other plugins do it this way and a Composer dependency for four
 * namespaces would be ceremony.
 */
spl_autoload_register(
	static function ( string $class_name ): void {
		if ( 0 !== strpos( $class_name, 'KzmielecTranslate\\' ) ) {
			return;
		}

		$relative = str_replace(
			array( 'KzmielecTranslate\\', '\\' ),
			array( '', DIRECTORY_SEPARATOR ),
			$class_name
		);

		$file = KZMIELEC_TRANSLATE_DIR . 'app/' . $relative . '.php';

		if ( file_exists( $file ) ) {
			include $file;
		}
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		if ( class_exists( '\KzmielecTranslate\Admin\DeeplSettings' ) ) {
			new \KzmielecTranslate\Admin\DeeplSettings();
		}

		/*
		 * The guard against Polylang being switched off used to live here, as
		 * `Core\OrphanGuard`. It moved to the theme (`Kzmielec\Core\TranslationGuard`)
		 * and this plugin is a migration tool again: translate the content, then
		 * switch it off if you like. While the guard was here, a finished tool could
		 * never be disabled — a requirement nobody inheriting the site would guess.
		 *
		 * The two classes must never both run: each would treat the other's internal
		 * `get_terms()` lookup as an outside query and they would recurse forever.
		 */
		if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( '\KzmielecTranslate\Cli\TranslateCommand' ) ) {
			\WP_CLI::add_command( 'kzmielec-translate', '\KzmielecTranslate\Cli\TranslateCommand' );
		}
	}
);
