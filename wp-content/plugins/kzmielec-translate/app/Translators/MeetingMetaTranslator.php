<?php
/**
 * Translates the meetings CPT's own fields.
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
 * Two short strings translated, two identifiers copied.
 *
 * The split matters more than the size suggests. `_meeting_day_hour` is
 * "Niedziela 10:30" and belongs in the visitor's language; `_meeting_anchor` is
 * the number 10 and `_meeting_hover_image` is attachment id 208. Translating
 * either of the latter would break the anchors or blank the images while the page
 * still rendered — a failure nobody would notice from a screenshot.
 */
class MeetingMetaTranslator {

	/**
	 * Fields holding prose.
	 *
	 * @var array<int, string>
	 */
	private const TRANSLATE = array( '_meeting_day_hour', '_meeting_place' );

	/**
	 * Fields carrying identifiers or numbers.
	 *
	 * @var array<int, string>
	 */
	private const COPY_AS_IS = array( '_meeting_hover_image', '_meeting_anchor' );

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
	 * Translate the prose fields and copy the rest.
	 *
	 * @param int    $source_id  Polish post id.
	 * @param int    $target_id  Translated post id, 0 when only counting.
	 * @param string $deepl_lang DeepL target code.
	 * @param bool   $execute    False counts only.
	 * @return array{segments: int, chars: int}
	 */
	public function translate( int $source_id, int $target_id, string $deepl_lang, bool $execute ): array {
		if ( $execute && $target_id > 0 ) {
			foreach ( self::COPY_AS_IS as $key ) {
				$value = get_post_meta( $source_id, $key, true );

				if ( '' !== $value && null !== $value ) {
					update_post_meta( $target_id, $key, $value );
				}
			}
		}

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
