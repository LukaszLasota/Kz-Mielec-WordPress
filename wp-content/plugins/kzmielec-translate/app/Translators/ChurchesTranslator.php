<?php
/**
 * Translates the serialised `churches` array.
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
 * The one field on this site that holds editorial prose in post meta.
 *
 * All 37 comparison topics keep their entire text here and their post_content is
 * empty, so without this class the comparison of denominations — the most
 * substantial reading on the site, 19 652 characters of it — would stay Polish in
 * every language while everything around it looked translated.
 *
 * `church_name` goes through the translator too, but its correctness rests on the
 * DeepL glossary rather than on the model: denomination names must read
 * identically in all 37 topics, and consistency is what a glossary guarantees.
 */
class ChurchesTranslator {

	/**
	 * Meta key holding the array.
	 */
	private const META_KEY = 'churches';

	/**
	 * Meta keys copied across untouched.
	 *
	 * @var array<int, string>
	 */
	private const COPY_AS_IS = array( 'sort_order' );

	/**
	 * Sub-fields of each row that carry text.
	 *
	 * @var array<int, string>
	 */
	private const TEXT_FIELDS = array( 'church_name', 'description' );

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
	 * Translate the array from source post to target post.
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

		$rows = get_post_meta( $source_id, self::META_KEY, true );

		if ( ! is_array( $rows ) || array() === $rows ) {
			return array(
				'segments' => 0,
				'chars'    => 0,
			);
		}

		/*
		 * Flattened to a positional list so the whole array costs one API call
		 * instead of one per row, and so the map back is explicit rather than
		 * relying on iteration order twice.
		 */
		$texts = array();
		$map   = array();

		foreach ( $rows as $i => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			foreach ( self::TEXT_FIELDS as $field ) {
				if ( isset( $row[ $field ] ) && is_string( $row[ $field ] ) && '' !== trim( $row[ $field ] ) ) {
					$texts[] = $row[ $field ];
					$map[]   = array( $i, $field );
				}
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

		foreach ( $map as $pos => $where ) {
			list( $i, $field ) = $where;

			$source = (string) $rows[ $i ][ $field ];
			$new    = (string) ( $translated[ $pos ] ?? $source );

			SegmentStore::save( $target_id, self::META_KEY . '[' . $i . '].' . $field, $source, $new );

			$rows[ $i ][ $field ] = $new;
		}

		update_post_meta( $target_id, self::META_KEY, $rows );

		return array(
			'segments' => count( $texts ),
			'chars'    => $chars,
		);
	}
}
