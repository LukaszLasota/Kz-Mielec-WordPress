<?php
/**
 * Translates the text inside block markup without touching the markup.
 *
 * @package KzmielecTranslate
 */

declare(strict_types=1);

namespace KzmielecTranslate\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Walks the parsed block tree, translates what is text and leaves the rest alone.
 *
 * Two things make this harder than "send the HTML to DeepL".
 *
 * First, block boundaries are HTML comments carrying JSON. Handing them to a
 * translator rewrites or reorders them and the post stops opening in the editor.
 * Working on the parsed tree instead of the raw string avoids the problem
 * entirely: delimiters are reconstructed by serialize_block() and never
 * translated.
 *
 * Second, some attributes hold text a visitor reads and most hold configuration,
 * and the two are indistinguishable by shape — `ariaLabel` is prose, `targetId`
 * is an anchor name, and both are short alphabetic strings. Translating
 * `targetId` would break every scroll arrow on the site while leaving the page
 * looking perfectly fine. Hence an explicit whitelist, derived from a scan of all
 * 18 pages and 3 meetings, and never a heuristic.
 */
class BlockSafeText {

	/**
	 * Block name => attributes whose values are prose.
	 *
	 * Measured 2026-08-10 across every published page and meeting. Everything not
	 * listed here is configuration and must survive untouched: anchor, targetId,
	 * className, dataSource, tagName, layoutType, flexDirection, flexWrap,
	 * justifyContent, align, sizeSlug, direction, tileStyle and the four image and
	 * overlay URL attributes.
	 *
	 * @var array<string, array<int, string>>
	 */
	public const TRANSLATABLE_ATTRS = array(
		'custom-block-package/accordion-item' => array( 'title' ),
		'custom-block-package/dynamic-images' => array( 'heading' ),
		'custom-block-package/map-block'      => array( 'popupText' ),
		'custom-block-package/scroll-arrow'   => array( 'ariaLabel' ),
	);

	/**
	 * Blocks whose text is fetched at render time.
	 *
	 * These already come out per-language once the posts underneath them are
	 * translated, because Polylang filters the queries they run. Listed for the
	 * reader; the whitelist above already excludes them.
	 *
	 * @var array<int, string>
	 */
	public const SELF_TRANSLATING = array(
		'custom-block-package/navigable-tiles',
		'comparison-of-religions/comparison-accordion',
		'custom-block-package/facebook-feed',
		'sbi/sbi-feed-block',
	);

	/**
	 * Every translatable segment in a document, in document order.
	 *
	 * Used to price a run before it happens: the character count of this array is
	 * exactly what the run will send to DeepL.
	 *
	 * @param string $content Block markup.
	 * @return array<int, string>
	 */
	public static function segments( string $content ): array {
		$out = array();

		self::walk(
			parse_blocks( $content ),
			static function ( string $text ) use ( &$out ): string {
				$out[] = $text;

				return $text;
			}
		);

		return $out;
	}

	/**
	 * Translate a document's text, preserving its structure exactly.
	 *
	 * @param string              $content     Block markup.
	 * @param TranslatorInterface $translator  Translator to use.
	 * @param string              $target_lang DeepL target code.
	 * @return string
	 */
	public static function translate_content( string $content, TranslatorInterface $translator, string $target_lang ): string {
		if ( '' === trim( $content ) ) {
			return $content;
		}

		$blocks = parse_blocks( $content );

		/*
		 * Collect first, translate in one batch, then substitute positionally.
		 * Both traversals share one walk() so they cannot disagree about which
		 * segments exist or in what order — if they did, substitution would land
		 * text in the wrong slots and the page would look translated and be wrong.
		 */
		$segments = array();

		self::walk(
			$blocks,
			static function ( string $text ) use ( &$segments ): string {
				$segments[] = $text;

				return $text;
			}
		);

		if ( array() === $segments ) {
			return $content;
		}

		$translated = $translator->translate( $segments, $target_lang );
		$index      = 0;

		$blocks = self::walk(
			$blocks,
			static function ( string $text ) use ( &$index, $translated ): string {
				$new = $translated[ $index ] ?? $text;
				++$index;

				return (string) $new;
			}
		);

		$out = '';

		foreach ( $blocks as $block ) {
			$out .= serialize_block( $block );
		}

		return $out;
	}

	/**
	 * Visit every translatable string in a block tree.
	 *
	 * The callback receives a string and returns its replacement, which is what
	 * lets one traversal serve both counting and substitution.
	 *
	 * @param array<int, array<string, mixed>> $blocks   Parsed blocks.
	 * @param callable                         $callback fn( string $text ): string.
	 * @return array<int, array<string, mixed>>
	 */
	private static function walk( array $blocks, callable $callback ): array {
		foreach ( $blocks as $i => $block ) {
			$name = (string) ( $block['blockName'] ?? '' );

			// Whitelisted attributes.
			foreach ( self::TRANSLATABLE_ATTRS[ $name ] ?? array() as $attr ) {
				if (
					isset( $block['attrs'][ $attr ] )
					&& is_string( $block['attrs'][ $attr ] )
					&& '' !== trim( $block['attrs'][ $attr ] )
				) {
					$block['attrs'][ $attr ] = $callback( $block['attrs'][ $attr ] );
				}
			}

			// Literal HTML between this block's own delimiters.
			if ( isset( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
				foreach ( $block['innerContent'] as $k => $chunk ) {
					if ( is_string( $chunk ) && '' !== trim( wp_strip_all_tags( $chunk ) ) ) {
						$block['innerContent'][ $k ] = $callback( $chunk );
					}
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = self::walk( $block['innerBlocks'], $callback );
			}

			/*
			 * `innerHTML` is a flattened copy of `innerContent`. serialize_block()
			 * uses `innerContent` for blocks with children and `innerHTML` for
			 * leaves, so leaving the two out of step produces a post that shows old
			 * text in the editor after an apparently successful run.
			 */
			if ( isset( $block['innerHTML'] ) && is_array( $block['innerContent'] ) ) {
				$block['innerHTML'] = implode( '', array_filter( $block['innerContent'], 'is_string' ) );
			}

			$blocks[ $i ] = $block;
		}

		return $blocks;
	}
}
