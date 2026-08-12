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
		add_action( 'deactivated_plugin', array( $this, 'invalidate_rules' ) );
	}

	/**
	 * Drop the cached rewrite rules when Polylang is switched off.
	 *
	 * Gating `add_rules()` on Polylang is not enough on its own: WordPress serves
	 * rewrite rules from the `rewrite_rules` option and only regenerates them when
	 * that option is empty. Measured — deactivate Polylang and
	 * `/en/plan-your-visit/` still answered 200 from the stale option, because
	 * Polylang's own deactivation does not flush it.
	 *
	 * The option is deleted rather than flushed. Calling `flush_rewrite_rules()`
	 * here would rebuild the rules inside the request that is still holding
	 * Polylang in memory — its functions are defined, the gate passes, and the
	 * language rules get written straight back in. Deleting defers the rebuild to
	 * the next request, by which time Polylang is genuinely gone.
	 *
	 * @param mixed $plugin Plugin file that was deactivated. Typed loosely on purpose:
	 *                      this is a hook callback, and the guard below is what makes it
	 *                      safe. Declaring `string` would make PHPStan call that guard
	 *                      dead code and refuse the file.
	 * @return void
	 */
	public function invalidate_rules( $plugin ): void {
		if ( ! is_string( $plugin ) || 0 !== strpos( $plugin, 'polylang/' ) ) {
			return;
		}

		delete_option( 'rewrite_rules' );
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

		/*
		 * Second layer, for the one case the deactivation hook cannot cover: the
		 * plugin folder deleted over FTP. No hook fires, the cached rules survive,
		 * and a translated address would keep serving the Polish archive. Cheap to
		 * check and it makes the guard independent of how Polylang went away.
		 */
		if ( ! function_exists( 'pll_current_language' ) ) {
			$this->redirect_orphaned_slug();
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

		$target = self::archive_url( $lang );

		// Keep the visitor on the page they asked for.
		if ( preg_match( '#/page/([0-9]+)$#', $path, $matches ) ) {
			$target .= 'page/' . (int) $matches[1] . '/';
		}

		wp_safe_redirect( $target, 301 );
		exit;
	}

	/**
	 * Send a translated archive address back to the Polish one when Polylang is gone.
	 *
	 * @return void
	 */
	private function redirect_orphaned_slug(): void {
		$request = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';
		$path    = trailingslashit( (string) wp_parse_url( $request, PHP_URL_PATH ) );

		foreach ( self::SLUGS as $lang => $slug ) {
			if ( 'pl' === $lang ) {
				continue;
			}

			if ( false === strpos( $path, '/' . $lang . '/' . $slug . '/' ) ) {
				continue;
			}

			wp_safe_redirect( self::archive_url( 'pl' ), 301 );
			exit;
		}
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
		/*
		 * Only while Polylang is there to give the prefixes meaning. Registered
		 * unconditionally, these rules survived Polylang being switched off and
		 * `/en/plan-your-visit/` answered 200 with the Polish archive — Polish
		 * content, Polish title, on an English address. Yoast's canonical pointed at
		 * `/zaplanuj-wizyte/`, so the damage was limited to a duplicate URL, but the
		 * address should not exist at all without the language it names.
		 */
		if ( ! function_exists( 'pll_current_language' ) ) {
			return;
		}

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

		return self::archive_url( $lang );
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

		return self::archive_url( $lang_slug );
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
	 * Public and static because `RegisterPosts` needs it too: a single meeting
	 * redirects to its archive, and before this it redirected to the hardcoded
	 * Polish one from every language.
	 *
	 * @param string $lang Language slug.
	 * @return string
	 */
	public static function archive_url( string $lang ): string {
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
