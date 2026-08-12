<?php
/**
 * Keeps source and translation side by side for later review.
 *
 * @package KzmielecTranslate
 */

declare(strict_types=1);

namespace KzmielecTranslate\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores every translated segment against its source.
 *
 * Plan C has to check 100% of segments, and a translation on its own cannot be
 * checked — once a run has finished there is nothing left to compare it to. Write
 * time is the only cheap moment to capture the pair.
 *
 * Post meta rather than a custom table: the volume is small, it travels with the
 * post through export and duplication, and it disappears with the post instead of
 * leaving orphan rows behind.
 */
class SegmentStore {

	/**
	 * Meta key holding the pairs.
	 */
	public const META_KEY = '_kzt_segments';

	/**
	 * Append one pair to a post's record.
	 *
	 * @param int    $post_id     Translated post.
	 * @param string $field       Where it came from, e.g. post_content, churches[0].description.
	 * @param string $source      Polish source text.
	 * @param string $translation Translated text.
	 * @return void
	 */
	public static function save( int $post_id, string $field, string $source, string $translation ): void {
		if ( $post_id <= 0 ) {
			return;
		}

		$rows = get_post_meta( $post_id, self::META_KEY, true );

		if ( ! is_array( $rows ) ) {
			$rows = array();
		}

		$rows[] = array(
			'field'       => $field,
			'source'      => $source,
			'translation' => $translation,
		);

		update_post_meta( $post_id, self::META_KEY, $rows );
	}

	/**
	 * Replace a post's whole record.
	 *
	 * Used at the start of a re-run so a second pass does not stack duplicates on
	 * top of the first.
	 *
	 * @param int                                                                   $post_id Translated post.
	 * @param array<int, array{field: string, source: string, translation: string}> $rows    Rows to store.
	 * @return void
	 */
	public static function replace( int $post_id, array $rows ): void {
		if ( $post_id <= 0 ) {
			return;
		}

		update_post_meta( $post_id, self::META_KEY, $rows );
	}

	/**
	 * Every pair recorded for a post.
	 *
	 * @param int $post_id Translated post.
	 * @return array<int, array{field: string, source: string, translation: string}>
	 */
	public static function all( int $post_id ): array {
		$rows = get_post_meta( $post_id, self::META_KEY, true );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$out = array();

		foreach ( $rows as $row ) {
			if ( is_array( $row ) && isset( $row['field'], $row['source'], $row['translation'] ) ) {
				$out[] = array(
					'field'       => (string) $row['field'],
					'source'      => (string) $row['source'],
					'translation' => (string) $row['translation'],
				);
			}
		}

		return $out;
	}
}
