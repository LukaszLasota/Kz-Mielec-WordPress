<?php
/**
 * Translates the line of text above the Instagram feed.
 *
 * @package Kzmielec
 */

declare(strict_types=1);

namespace Kzmielec\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Serves the Smash Balloon header bio in the page's language.
 *
 * Smash Balloon has one feed configuration for the whole site, so the "custom bio"
 * typed into it (today "Instagram zboru") showed in Polish on the English,
 * Ukrainian and Spanish pages too. The plugin offers no filter for it:
 * `SB_Instagram_Parse::get_bio()` returns the setting straight into the template.
 *
 * So the rendered paragraph is swapped after the fact, on `render_block`, with the
 * translation entered in Languages → Translations. The bio is registered there
 * under this theme's group, read from the feed settings, so a new bio shows up as
 * a new row without a code change. No translation entered means no change: the
 * Polish text stays, exactly as before.
 *
 * The translated paragraph carries the page's `lang`, because SocialFeedLanguage
 * marks the whole feed `lang="pl"` and this one line is no longer Polish.
 */
class InstagramBio {

	/**
	 * Group the string appears under in Languages → Translations.
	 * Same as StringTranslations, so the editor finds it next to the theme's strings.
	 */
	private const GROUP = 'Motyw kzmielec';

	/**
	 * Label shown next to the string in the admin.
	 */
	private const LABEL = 'Instagram: tekst nad kanałem';

	/**
	 * The paragraph Smash Balloon prints in templates/header.php.
	 */
	private const PATTERN = '#(<p class="sbi_bio"[^>]*)>(.*?)</p>#s';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'render_block', array( $this, 'translate' ), 10, 2 );
	}

	/**
	 * Expose every configured bio in Languages → Translations.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! is_admin() || ! function_exists( 'pll_register_string' ) ) {
			return;
		}

		foreach ( $this->configured_bios() as $bio ) {
			pll_register_string( self::LABEL, $bio, self::GROUP );
		}
	}

	/**
	 * Replace the bio in a rendered Instagram feed on a non-Polish page.
	 *
	 * @param string               $content Rendered block HTML.
	 * @param array<string, mixed> $block   Parsed block.
	 * @return string
	 */
	public function translate( $content, $block ): string {
		$content = (string) $content;

		if ( 'sbi/sbi-feed-block' !== ( $block['blockName'] ?? '' ) || false === strpos( $content, 'sbi_bio' ) ) {
			return $content;
		}

		if ( ! function_exists( 'pll_current_language' ) || ! function_exists( 'pll__' ) ) {
			return $content;
		}

		/**
		 * Polylang returns false when no language is set, despite its docblock.
		 *
		 * @var mixed $lang
		 */
		$lang = pll_current_language( 'slug' );
		if ( ! is_string( $lang ) || '' === $lang || 'pl' === $lang ) {
			return $content;
		}

		$locale = pll_current_language( 'locale' );
		$tag    = is_string( $locale ) ? str_replace( '_', '-', $locale ) : $lang;

		$result = preg_replace_callback(
			self::PATTERN,
			static function ( array $m ) use ( $tag ): string {
				// The template prints the bio through esc_html( nl2br() ) and then
				// turns the escaped <br /> back into <br>. nl2br() keeps the newline
				// itself, so dropping the tag is enough to get the source back.
				$source = html_entity_decode( str_replace( '<br>', '', $m[2] ), ENT_QUOTES, 'UTF-8' );
				$panel  = (string) pll__( $source );

				if ( '' === $panel || $panel === $source ) {
					return $m[0];
				}

				return $m[1] . ' lang="' . esc_attr( $tag ) . '">' . nl2br( esc_html( $panel ), false ) . '</p>';
			},
			$content,
			1
		);

		return is_string( $result ) ? $result : $content;
	}

	/**
	 * Custom bios set on the Smash Balloon feeds.
	 *
	 * Read from the plugin's own table because it exposes no API for its feed
	 * settings. Only the custom bio is registered: without one, the bio comes from
	 * the Instagram account and changes whenever somebody edits it there.
	 *
	 * @return array<int, string>
	 */
	private function configured_bios(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'sbi_feeds';
		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return array();
		}

		$bios = array();
		foreach ( (array) $wpdb->get_col( "SELECT settings FROM {$table}" ) as $json ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$settings = json_decode( (string) $json, true );
			$bio      = is_array( $settings ) && isset( $settings['custombio'] ) ? trim( (string) $settings['custombio'] ) : '';
			if ( '' !== $bio ) {
				$bios[] = $bio;
			}
		}

		return array_values( array_unique( $bios ) );
	}
}
