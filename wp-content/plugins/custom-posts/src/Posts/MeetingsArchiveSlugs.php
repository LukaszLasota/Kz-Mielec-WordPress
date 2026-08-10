<?php
/**
 * Per-language archive slugs for the Meetings CPT.
 *
 * @package CustomPostsPlugin
 */

declare(strict_types=1);

namespace CustomPostsPlugin\Posts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gives the meetings archive a translated address in every language.
 *
 * Polylang gets the language prefix right on its own — `/en/zaplanuj-wizyte/`
 * resolves and returns English content — but translating the slug itself is a
 * paid feature. Since this plugin registers the post type, the rules can be
 * written by hand instead: one rewrite per language, so a slug cannot be reached
 * under the wrong prefix, plus a filter so generated links come out in the
 * visitor's language.
 */
class MeetingsArchiveSlugs {

	/**
	 * Post type this class serves.
	 */
	private const POST_TYPE = 'meetings';

	/**
	 * Language slug => archive slug.
	 *
	 * Polish is the value passed to CptBuilder and remains the canonical
	 * archive; the other three are additions. Ukrainian is transliterated to
	 * Latin on purpose — the address has to survive being pasted and read aloud.
	 *
	 * @var array<string, string>
	 */
	public const SLUGS = array(
		'pl' => 'zaplanuj-wizyte',
		'en' => 'plan-your-visit',
		'uk' => 'zaplanuyte-vizyt',
		'es' => 'planifica-tu-visita',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_rules' ), 20 );

		/*
		 * Priority 30, deliberately: `PLL_Filters_Links::post_type_archive_link`
		 * runs at 20 and rebuilds the URL from the untranslated archive slug, so
		 * anything registered earlier is silently overwritten. Ours has to be the
		 * last word.
		 */
		add_filter( 'post_type_archive_link', array( $this, 'filter_archive_link' ), 30, 2 );

		/*
		 * Polylang builds every switcher URL — and every hreflang alternate — by
		 * swapping the language prefix while keeping the path. On this archive the
		 * path is now a translated slug, so the Polish alternate came out as
		 * `/plan-your-visit/`, which does not exist and returned 404. Correcting
		 * `pll_translation_url` rather than the switcher filter fixes the language
		 * bar and the hreflang tags in one place; a hreflang pointing at a 404 is
		 * the more damaging of the two.
		 */
		add_filter( 'pll_translation_url', array( $this, 'filter_translation_url' ), 10, 2 );

		add_action( 'template_redirect', array( $this, 'redirect_untranslated_slug' ) );
	}

	/**
	 * Send the untranslated slug under a foreign prefix to the translated one.
	 *
	 * Polylang's generic `(en|uk|es)/(.+)` rule still resolves
	 * `/en/zaplanuj-wizyte/`, so the archive answered on two addresses at once.
	 * Yoast writes a canonical either way, but two live URLs for one page split
	 * inbound links and show up in Search Console as duplicates, so the Polish
	 * slug under a foreign prefix is retired with a permanent redirect.
	 *
	 * @return void
	 */
	public function redirect_untranslated_slug(): void {
		if ( ! is_post_type_archive( self::POST_TYPE ) ) {
			return;
		}

		$lang = $this->current_language();

		if ( 'pl' === $lang || ! isset( self::SLUGS[ $lang ] ) ) {
			return;
		}

		$request = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';
		$path    = untrailingslashit( (string) wp_parse_url( $request, PHP_URL_PATH ) );
		$wrong   = '/' . $lang . '/' . self::SLUGS['pl'];

		if ( 0 !== strpos( $path, $wrong ) ) {
			return;
		}

		$target = $this->archive_url( $lang );

		// Keep the visitor on the page they asked for.
		if ( preg_match( '#/page/([0-9]+)$#', $path, $matches ) ) {
			$target .= 'page/' . (int) $matches[1] . '/';
		}

		wp_safe_redirect( $target, 301 );
		exit;
	}

	/**
	 * Register one rewrite rule per non-default language.
	 *
	 * Added at `top` so they win over Polylang's generic `(en|uk|es)/(.+)`
	 * catch-alls, which would otherwise treat `plan-your-visit` as a page slug
	 * and 404.
	 *
	 * @return void
	 */
	public function add_rules(): void {
		foreach ( self::SLUGS as $lang => $slug ) {
			if ( 'pl' === $lang ) {
				continue;
			}

			add_rewrite_rule(
				'^' . $lang . '/' . $slug . '/?$',
				'index.php?post_type=' . self::POST_TYPE . '&lang=' . $lang,
				'top'
			);

			// Paged archive, e.g. /en/plan-your-visit/page/2/.
			add_rewrite_rule(
				'^' . $lang . '/' . $slug . '/page/([0-9]{1,})/?$',
				'index.php?post_type=' . self::POST_TYPE . '&lang=' . $lang . '&paged=$matches[1]',
				'top'
			);
		}
	}

	/**
	 * Rewrite the generated archive link into the current language.
	 *
	 * @param string $link      Archive URL built by WordPress.
	 * @param string $post_type Post type the URL belongs to.
	 * @return string
	 */
	public function filter_archive_link( string $link, string $post_type ): string {
		if ( self::POST_TYPE !== $post_type ) {
			return $link;
		}

		$lang = $this->current_language();

		if ( 'pl' === $lang || ! isset( self::SLUGS[ $lang ] ) ) {
			return $link;
		}

		return $this->archive_url( $lang );
	}

	/**
	 * Correct the translation URL Polylang hands to the switcher and to hreflang.
	 *
	 * @param string $url       URL Polylang computed for the other language.
	 * @param string $lang_slug Language slug that URL is for.
	 * @return string
	 */
	public function filter_translation_url( $url, $lang_slug ): string {
		$url       = (string) $url;
		$lang_slug = (string) $lang_slug;

		if ( ! isset( self::SLUGS[ $lang_slug ] ) || ! is_post_type_archive( self::POST_TYPE ) ) {
			return $url;
		}

		return $this->archive_url( $lang_slug );
	}

	/**
	 * Absolute archive URL for one language.
	 *
	 * Built from the raw `home` option, not from `home_url()` and not from
	 * `pll_home_url()`. Both are unreliable here: Polylang filters `home_url` and
	 * drops its own filters while it computes links, so inside a link filter
	 * `pll_home_url( 'en' )` returns the bare site root — while returning the
	 * prefixed URL correctly everywhere else, which makes the bug look like
	 * working code. The raw option is language-blind and therefore the only
	 * stable base to prefix by hand.
	 *
	 * @param string $lang Language slug.
	 * @return string
	 */
	private function archive_url( string $lang ): string {
		$base = trailingslashit( (string) get_option( 'home' ) );

		return 'pl' === $lang
			? $base . self::SLUGS['pl'] . '/'
			: $base . $lang . '/' . self::SLUGS[ $lang ] . '/';
	}

	/**
	 * Current language slug, falling back to Polish.
	 *
	 * The `kzt_test_lang` global lets the test suite drive this without booting
	 * one request per language; it is never set in production.
	 *
	 * @return string
	 */
	private function current_language(): string {
		if ( isset( $GLOBALS['kzt_test_lang'] ) ) {
			return (string) $GLOBALS['kzt_test_lang'];
		}

		if ( function_exists( 'pll_current_language' ) ) {
			$lang = pll_current_language( 'slug' );

			if ( is_string( $lang ) && '' !== $lang ) {
				return $lang;
			}
		}

		return 'pl';
	}
}
