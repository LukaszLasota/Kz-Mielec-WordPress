<?php
/**
 * Creates the translated counterpart of a post.
 *
 * @package KzmielecTranslate
 */

declare(strict_types=1);

namespace KzmielecTranslate\Translators;

use KzmielecTranslate\Services\BlockSafeText;
use KzmielecTranslate\Services\LinkRemapper;
use KzmielecTranslate\Services\SegmentStore;
use KzmielecTranslate\Services\TranslatorInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Title, content and slug, plus the language link that makes it a translation.
 *
 * Nothing here overwrites an existing translation unless explicitly asked to. The
 * plugin exists to fill the first draft; from that moment the editor owns the
 * post, and a careless re-run must not undo somebody's corrections.
 */
class PostTranslator {

	/**
	 * WordPress language slug => DeepL target code.
	 *
	 * @var array<string, string>
	 */
	public const DEEPL_LANG = array(
		'en' => 'EN-GB',
		'uk' => 'UK',
		'es' => 'ES',
	);

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
	 * Translate one post into one language.
	 *
	 * @param int    $source_id Polish post id.
	 * @param string $lang      Target language slug (en, uk, es).
	 * @param bool   $execute   False reports only and writes nothing.
	 * @param bool   $force     True overwrites an existing translation.
	 * @return array{created: int, segments: int, chars: int, target_id: int}
	 */
	public function translate( int $source_id, string $lang, bool $execute, bool $force = false ): array {
		$deepl = self::DEEPL_LANG[ $lang ] ?? '';
		$post  = get_post( $source_id );

		if ( '' === $deepl || ! $post instanceof \WP_Post ) {
			return $this->nothing();
		}

		$existing = (int) ( function_exists( 'pll_get_post' ) ? pll_get_post( $source_id, $lang ) : 0 );
		$segments = BlockSafeText::segments( (string) $post->post_content );
		$chars    = array_sum( array_map( 'strlen', $segments ) ) + strlen( (string) $post->post_title );

		if ( ! $execute || ( $existing > 0 && ! $force ) ) {
			/*
			 * Counted through the same helper the writing branch uses, so the
			 * report cannot understate what a real run would send. An estimate
			 * that misses the 19 652 characters of `churches` would be worse than
			 * no estimate, because it would look precise.
			 */
			$meta = $this->meta( $source_id, 0, (string) $post->post_type, $deepl, false );

			return array(
				'created'   => 0,
				'segments'  => count( $segments ) + 1 + $meta['segments'],
				'chars'     => $chars + $meta['chars'],
				'target_id' => $existing,
			);
		}

		$title   = (string) ( $this->translator->translate( array( (string) $post->post_title ), $deepl )[0] ?? $post->post_title );
		$content = BlockSafeText::translate_content( (string) $post->post_content, $this->translator, $deepl );

		/*
		 * Internal links have to be pointed at this language. They are not
		 * translated — a slug is not prose — which leaves them aimed wherever the
		 * Polish version aimed, so every "read more" button walked the reader out
		 * of their language.
		 */
		$content = LinkRemapper::remap( $content, $lang )['content'];

		$data = array(
			'post_type'    => $post->post_type,
			'post_status'  => $post->post_status,
			'post_title'   => $title,
			'post_content' => $content,
			'post_name'    => sanitize_title( $title ),
			'post_parent'  => $post->post_parent,
			'menu_order'   => $post->menu_order,
		);

		if ( $existing > 0 ) {
			$data['ID'] = $existing;
			$target_id  = (int) wp_update_post( $data );
			$created    = 0;
		} else {
			$target_id = (int) wp_insert_post( $data );
			$created   = $target_id > 0 ? 1 : 0;
		}

		if ( $target_id <= 0 ) {
			return $this->nothing();
		}

		if ( function_exists( 'pll_set_post_language' ) ) {
			pll_set_post_language( $target_id, $lang );

			$source_lang = (string) pll_get_post_language( $source_id );

			if ( '' === $source_lang ) {
				$source_lang = 'pl';
			}

			/*
			 * `pll_save_post_translations()` REPLACES the whole translation group,
			 * it does not add to it. Passing only the source and the new target
			 * therefore deletes every other language already linked — so running
			 * en, then uk, then es left only es connected and silently orphaned 116
			 * posts. Measured, not theorised.
			 *
			 * The existing group has to be read and merged.
			 */
			$group                 = (array) pll_get_post_translations( $source_id );
			$group[ $source_lang ] = $source_id;
			$group[ $lang ]        = $target_id;

			pll_save_post_translations( $group );
		}

		// Featured image is the same picture in every language.
		$thumb = (int) get_post_thumbnail_id( $source_id );

		if ( $thumb > 0 ) {
			set_post_thumbnail( $target_id, $thumb );
		}

		// Fresh record, so a re-run does not stack duplicates.
		SegmentStore::replace( $target_id, array() );
		SegmentStore::save( $target_id, 'post_title', (string) $post->post_title, $title );
		SegmentStore::save( $target_id, 'post_content', (string) $post->post_content, $content );

		$meta = $this->meta( $source_id, $target_id, (string) $post->post_type, $deepl, true );

		/*
		 * File the translation under the translated terms. A translated term that
		 * nothing is assigned to is as invisible as no term at all — the comparison
		 * accordion groups by category, so without this the page renders empty
		 * while still returning 200.
		 */
		( new TermTranslator( $this->translator ) )->assign( $source_id, $target_id, $lang );

		return array(
			'created'   => $created,
			'segments'  => count( $segments ) + 1 + $meta['segments'],
			'chars'     => $chars + $meta['chars'],
			'target_id' => $target_id,
		);
	}

	/**
	 * Run the meta translators that apply to this post type.
	 *
	 * Dispatched per post type rather than run unconditionally: each translator
	 * owns a different set of keys, so running the wrong one either does nothing
	 * or copies a field that does not belong on that type. Yoast applies to
	 * everything, because every post type can carry a search-result snippet.
	 *
	 * @param int    $source_id  Polish post id.
	 * @param int    $target_id  Translated post id, 0 when only counting.
	 * @param string $post_type  Post type being translated.
	 * @param string $deepl_lang DeepL target code.
	 * @param bool   $execute    False counts only.
	 * @return array{segments: int, chars: int}
	 */
	private function meta( int $source_id, int $target_id, string $post_type, string $deepl_lang, bool $execute ): array {
		$total = array(
			'segments' => 0,
			'chars'    => 0,
		);

		$translators = array( new YoastTranslator( $this->translator ) );

		if ( 'comparison_topic' === $post_type ) {
			$translators[] = new ChurchesTranslator( $this->translator );
		}

		if ( 'meetings' === $post_type ) {
			$translators[] = new MeetingMetaTranslator( $this->translator );
		}

		if ( 'page' === $post_type ) {
			$translators[] = new PageMetaTranslator( $this->translator );
		}

		foreach ( $translators as $translator ) {
			$r = $translator->translate( $source_id, $target_id, $deepl_lang, $execute );

			$total['segments'] += $r['segments'];
			$total['chars']    += $r['chars'];
		}

		return $total;
	}

	/**
	 * The "did nothing" result, so callers never see a partial array.
	 *
	 * @return array{created: int, segments: int, chars: int, target_id: int}
	 */
	private function nothing(): array {
		return array(
			'created'   => 0,
			'segments'  => 0,
			'chars'     => 0,
			'target_id' => 0,
		);
	}
}
