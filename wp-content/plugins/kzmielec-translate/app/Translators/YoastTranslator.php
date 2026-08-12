<?php
/**
 * Translates the two Yoast fields that reach a search result.
 *
 * @package KzmielecTranslate
 */

declare(strict_types=1);

namespace KzmielecTranslate\Translators;

use KzmielecTranslate\Services\SegmentStore;
use KzmielecTranslate\Services\TranslatorInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SEO title and meta description, and deliberately nothing else.
 *
 * These two are what a person reads in Google before deciding whether to click,
 * which is why Plan C treats them as the layer that has to be reviewed rather
 * than trusted. The other `_yoast_*` keys — content score, reading time — are
 * Yoast's own derived numbers and are left absent on purpose, so Yoast
 * recalculates them for the translated text instead of inheriting a score
 * computed from Polish.
 */
class YoastTranslator {

	/**
	 * Fields to translate.
	 *
	 * @var array<int, string>
	 */
	private const TRANSLATE = array( '_yoast_wpseo_title', '_yoast_wpseo_metadesc' );

	/**
	 * Translator to use.
	 *
	 * @var TranslatorInterface
	 */
	private TranslatorInterface $translator;

	/**
	 * Constructor.
	 *
	 * @param TranslatorInterface $translator Translator implementation.
	 */
	public function __construct( TranslatorInterface $translator ) {
		$this->translator = $translator;
	}

	/**
	 * Translate the two fields.
	 *
	 * @param int    $source_id  Polish post id.
	 * @param int    $target_id  Translated post id, 0 when only counting.
	 * @param string $deepl_lang DeepL target code.
	 * @param bool   $execute    False counts only.
	 * @return array{segments: int, chars: int}
	 */
	public function translate( int $source_id, int $target_id, string $deepl_lang, bool $execute ): array {
		$texts = array();
		$keys  = array();

		foreach ( self::TRANSLATE as $key ) {
			$value = (string) get_post_meta( $source_id, $key, true );

			if ( '' !== trim( $value ) ) {
				$texts[] = $value;
				$keys[]  = $key;
			}
		}

		$chars = array_sum( array_map( 'strlen', $texts ) );

		if ( ! $execute || $target_id <= 0 || array() === $texts ) {
			return array(
				'segments' => count( $texts ),
				'chars'    => $chars,
			);
		}

		$translated = $this->translator->translate( $texts, $deepl_lang );

		foreach ( $keys as $pos => $key ) {
			$new = (string) ( $translated[ $pos ] ?? $texts[ $pos ] );

			SegmentStore::save( $target_id, $key, $texts[ $pos ], $new );
			update_post_meta( $target_id, $key, $new );
		}

		return array(
			'segments' => count( $texts ),
			'chars'    => $chars,
		);
	}
}
