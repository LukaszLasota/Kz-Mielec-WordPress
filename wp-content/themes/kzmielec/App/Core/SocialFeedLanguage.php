<?php
/**
 * Marks the social feeds as Polish content on the translated pages.
 *
 * @package Kzmielec
 */

declare(strict_types=1);

namespace Kzmielec\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declares `lang="pl"` on the Facebook and Instagram feeds outside the Polish site.
 *
 * WCAG 3.1.2 Language of Parts (Level AA) asks that a passage in a language other
 * than the page's own be marked as such. The English, Ukrainian and Spanish pages
 * carry `<html lang="en-GB">` and so on, and then embed two feeds whose content is
 * the congregation's own Polish posts — 27 Polish words on the English home page
 * alone. Without a `lang` attribute a screen reader pronounces that Polish with
 * English phonetics, which is not merely odd but genuinely unintelligible.
 *
 * axe does not report this. It checks that a declared `lang` is valid
 * (`valid-lang`) and that the document has one (`html-has-lang`), but it cannot know
 * what language a passage is actually written in. The 304-scan sweep of this site
 * came back with zero violations while this was still wrong — a good reminder that a
 * clean automated report is a floor, not a ceiling.
 *
 * The attribute is injected into each feed's existing outer element rather than a
 * wrapper being added around it. A new block-level element in the flow can change a
 * grid or flex layout; an extra attribute cannot.
 *
 * Nothing happens on the Polish pages, where the feed language and the page language
 * already agree, and nothing happens if Polylang is switched off — then the whole
 * site is Polish again.
 */
class SocialFeedLanguage {

	/**
	 * Blocks whose output is Polish regardless of the page's language.
	 *
	 * The value is the literal that opens the element the attribute belongs on. For
	 * our own block that is the block wrapper; for Smash Balloon's it is the container
	 * it has used for years and which its own CSS and JavaScript depend on, so it is
	 * as stable a handle as anything in that plugin.
	 *
	 * @var array<string, string>
	 */
	private const FEEDS = array(
		'custom-block-package/facebook-feed' => '<div ',
		'sbi/sbi-feed-block'                 => '<div id="sb_instagram"',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'render_block', array( $this, 'declare_polish' ), 10, 2 );
	}

	/**
	 * Add `lang="pl"` to a social feed rendered on a non-Polish page.
	 *
	 * @param string               $content Rendered block HTML.
	 * @param array<string, mixed> $block   Parsed block.
	 * @return string
	 */
	public function declare_polish( $content, $block ): string {
		$content = (string) $content;
		$name    = (string) ( $block['blockName'] ?? '' );

		if ( ! isset( self::FEEDS[ $name ] ) || '' === trim( $content ) ) {
			return $content;
		}

		if ( ! function_exists( 'pll_current_language' ) ) {
			return $content;
		}

		/**
		 * Current language slug, as Polylang really hands it back.
		 *
		 * Polylang documents a non-empty string, and PHPStan believes it, which would
		 * make the guard below look dead. The function does return `false` when no
		 * language is set, so the declared type is the optimistic one.
		 *
		 * @var mixed $lang
		 */
		$lang = pll_current_language( 'slug' );

		if ( ! is_string( $lang ) || '' === $lang || 'pl' === $lang ) {
			return $content;
		}

		$needle = self::FEEDS[ $name ];
		$at     = strpos( $content, $needle );

		if ( false === $at ) {
			return $content;
		}

		// Already marked — the filter can run twice on cached or nested output.
		if ( false !== strpos( substr( $content, $at, 200 ), 'lang="pl"' ) ) {
			return $content;
		}

		return substr_replace( $content, '<div lang="pl" ' . substr( $needle, 5 ), $at, strlen( $needle ) );
	}
}
