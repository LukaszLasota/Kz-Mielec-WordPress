<?php
/**
 * Carries page-level meta across to a translation.
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
 * Copies the two page fields that decide how a page renders and looks.
 *
 * Neither holds text, so nothing here is translated — but omitting them broke the
 * site in a way that no test caught and only looking at the page revealed:
 *
 * `_wp_page_template` is the assigned page template. `wp_insert_post()` does not
 * copy it, so every translated belief page fell back to the default template
 * instead of `page-belief.php` — and that template is what draws the belief
 * navigation tiles and hosts the comparison accordion. The pages rendered,
 * returned 200 and passed the functional checks; they were simply missing their
 * furniture.
 *
 * `_belief_hover_image` is the attachment id of the overlay that appears on a
 * tile on hover. Same picture in every language, so it is copied, not translated.
 */
class PageMetaTranslator {

	/**
	 * Meta keys copied verbatim.
	 *
	 * @var array<int, string>
	 */
	private const COPY_AS_IS = array( '_wp_page_template', '_belief_hover_image' );

	/**
	 * Translator to use.
	 *
	 * Unused — this class only copies — but kept so every translator shares one
	 * constructor signature and PostTranslator can treat them uniformly.
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
	 * Copy the fields.
	 *
	 * @param int    $source_id  Polish post id.
	 * @param int    $target_id  Translated post id, 0 when only counting.
	 * @param string $deepl_lang DeepL target code (unused, nothing is translated).
	 * @param bool   $execute    False counts only.
	 * @return array{segments: int, chars: int}
	 */
	public function translate( int $source_id, int $target_id, string $deepl_lang, bool $execute ): array {
		unset( $deepl_lang );

		if ( $execute && $target_id > 0 ) {
			foreach ( self::COPY_AS_IS as $key ) {
				$value = get_post_meta( $source_id, $key, true );

				if ( '' !== $value && null !== $value ) {
					update_post_meta( $target_id, $key, $value );
				}
			}
		}

		// Nothing is sent to the API, so nothing is charged.
		return array(
			'segments' => 0,
			'chars'    => 0,
		);
	}
}
