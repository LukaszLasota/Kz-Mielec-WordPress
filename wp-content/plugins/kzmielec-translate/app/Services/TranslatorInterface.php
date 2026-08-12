<?php
/**
 * Contract shared by the real translator and the test stub.
 *
 * @package KzmielecTranslate
 */

declare(strict_types=1);

namespace KzmielecTranslate\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One interface so the whole pipeline can be exercised without an API key.
 *
 * Everything downstream depends on this and never on DeeplClient directly, which
 * is what makes a full dry run possible before the key exists — and what keeps a
 * test suite from spending quota.
 */
interface TranslatorInterface {

	/**
	 * Translate a batch of strings.
	 *
	 * The returned array MUST have the same length and order as the input:
	 * callers reassemble documents positionally, so a dropped element silently
	 * shifts text into the wrong slot, which is worse than an outright failure
	 * because the result still looks like a translated page.
	 *
	 * @param array<int, string> $texts       Strings to translate.
	 * @param string             $target_lang DeepL target code, e.g. EN-GB, UK, ES.
	 * @return array<int, string>
	 */
	public function translate( array $texts, string $target_lang ): array;

	/**
	 * Characters used and allowed this billing period.
	 *
	 * @return array{character_count: int, character_limit: int}
	 */
	public function usage(): array;
}
