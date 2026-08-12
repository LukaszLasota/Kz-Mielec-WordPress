<?php
/**
 * Translates taxonomy terms and links them across languages.
 *
 * @package KzmielecTranslate
 */

declare(strict_types=1);

namespace KzmielecTranslate\Translators;

use KzmielecTranslate\Services\TranslatorInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The piece the original design forgot entirely.
 *
 * Plan B covered posts and post meta and said nothing about taxonomies, which was
 * a hole rather than a simplification. The comparison accordion groups its 37
 * topics by `comparison_category`; with no categories in the target language there
 * is nothing to group by, so `/es/diferencias-religiosas/` rendered an empty page
 * while returning 200 and passing every functional check.
 *
 * Two things have to happen, and the second is the one easy to miss: the terms
 * need translating, AND every translated post needs assigning to the translated
 * term. A translated term nobody is filed under is just as invisible as no term.
 */
class TermTranslator {

	/**
	 * Taxonomies this project translates.
	 *
	 * @var array<int, string>
	 */
	public const TAXONOMIES = array( 'comparison_category' );

	/**
	 * Term meta copied verbatim to the translated term.
	 *
	 * This is not housekeeping — leaving it out silently blanks the comparison
	 * page. The accordion fetches its panels with
	 * `get_terms( [ 'meta_key' => 'sort_order', 'orderby' => 'meta_value_num' ] )`,
	 * and `meta_key` makes get_terms EXCLUDE terms that do not carry the key. With
	 * the meta missing the term list came back empty, the block hit its
	 * `if ( empty( $categories ) ) return;` and rendered nothing at all — on a page
	 * that still answered 200 and still passed the functional checks.
	 *
	 * @var array<int, string>
	 */
	private const COPY_TERM_META = array( 'sort_order' );

	/**
	 * Term meta marking a term as one of our translations.
	 */
	public const MARKER = '_kzt_translated';

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
	 * Translate every term of every handled taxonomy into one language.
	 *
	 * @param string $lang       Target language slug (en, uk, es).
	 * @param string $deepl_lang DeepL target code.
	 * @param bool   $execute    False counts only.
	 * @param bool   $force      Overwrite an existing term translation.
	 * @return array{terms: int, chars: int, created: int}
	 */
	public function translate_all( string $lang, string $deepl_lang, bool $execute, bool $force = false ): array {
		$wynik = array(
			'terms'   => 0,
			'chars'   => 0,
			'created' => 0,
		);

		foreach ( self::TAXONOMIES as $taxonomy ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'lang'       => 'pl',
				)
			);

			if ( is_wp_error( $terms ) ) {
				continue;
			}

			$do_tlumaczenia = array();
			$zrodla         = array();

			foreach ( $terms as $term ) {
				$istnieje = (int) ( function_exists( 'pll_get_term' ) ? pll_get_term( $term->term_id, $lang ) : 0 );

				if ( $istnieje > 0 && ! $force ) {
					continue;
				}

				$do_tlumaczenia[] = $term->name;
				$zrodla[]         = $term;
			}

			$wynik['terms'] += count( $do_tlumaczenia );
			$wynik['chars'] += array_sum( array_map( 'strlen', $do_tlumaczenia ) );

			if ( ! $execute || array() === $do_tlumaczenia ) {
				continue;
			}

			$nazwy = $this->translator->translate( $do_tlumaczenia, $deepl_lang );

			foreach ( $zrodla as $pos => $term ) {
				$nazwa = (string) ( $nazwy[ $pos ] ?? $term->name );
				$cel   = (int) ( function_exists( 'pll_get_term' ) ? pll_get_term( $term->term_id, $lang ) : 0 );

				if ( $cel > 0 ) {
					wp_update_term( $cel, $taxonomy, array( 'name' => $nazwa ) );
				} else {
					$nowy = wp_insert_term(
						$nazwa,
						$taxonomy,
						array( 'slug' => sanitize_title( $nazwa . '-' . $lang ) )
					);

					if ( is_wp_error( $nowy ) ) {
						continue;
					}

					$cel = (int) $nowy['term_id'];
					++$wynik['created'];
				}

				foreach ( self::COPY_TERM_META as $meta_key ) {
					$wartosc = get_term_meta( $term->term_id, $meta_key, true );

					if ( '' !== $wartosc && null !== $wartosc ) {
						update_term_meta( $cel, $meta_key, $wartosc );
					}
				}

				/*
				 * Marker read by the theme's `Kzmielec\Core\TranslationGuard`, which is
				 * where the Polylang-off guard lives: get_terms() is not a WP_Query, so the
				 * pre_get_posts guard cannot reach it. Without this the comparison
				 * accordion showed all four languages' categories at once whenever
				 * Polylang was switched off.
				 */
				update_term_meta( $cel, self::MARKER, $lang );

				if ( function_exists( 'pll_set_term_language' ) ) {
					pll_set_term_language( $cel, $lang );

					/*
					 * Same trap as with posts: pll_save_term_translations() REPLACES
					 * the group, so the existing one is read and merged. Without this
					 * each language would wipe the previous one's link.
					 */
					$grupa                = (array) pll_get_term_translations( $term->term_id );
					$grupa['pl']          = $term->term_id;
					$grupa[ $lang ]       = $cel;

					pll_save_term_translations( $grupa );
				}
			}
		}

		return $wynik;
	}

	/**
	 * File a translated post under the translated counterparts of its source's terms.
	 *
	 * @param int    $source_id Polish post id.
	 * @param int    $target_id Translated post id.
	 * @param string $lang      Target language slug.
	 * @return int How many taxonomies were set.
	 */
	public function assign( int $source_id, int $target_id, string $lang ): int {
		if ( $target_id <= 0 || ! function_exists( 'pll_get_term' ) ) {
			return 0;
		}

		$ustawione = 0;

		foreach ( self::TAXONOMIES as $taxonomy ) {
			if ( ! is_object_in_taxonomy( (string) get_post_type( $source_id ), $taxonomy ) ) {
				continue;
			}

			$zrodlowe = wp_get_post_terms( $source_id, $taxonomy, array( 'fields' => 'ids' ) );

			if ( is_wp_error( $zrodlowe ) || array() === $zrodlowe ) {
				continue;
			}

			$docelowe = array();

			foreach ( $zrodlowe as $tid ) {
				$przetlumaczony = (int) pll_get_term( (int) $tid, $lang );

				if ( $przetlumaczony > 0 ) {
					$docelowe[] = $przetlumaczony;
				}
			}

			if ( array() !== $docelowe ) {
				wp_set_post_terms( $target_id, $docelowe, $taxonomy );
				++$ustawione;
			}
		}

		return $ustawione;
	}
}
