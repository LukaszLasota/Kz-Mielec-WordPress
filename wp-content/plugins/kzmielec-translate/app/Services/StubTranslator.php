<?php
/**
 * Deterministic stand-in for DeepL, used before the API key exists.
 *
 * @package KzmielecTranslate
 */

declare(strict_types=1);

namespace KzmielecTranslate\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Marks text instead of translating it.
 *
 * The point is to exercise the whole pipeline — block protection, attribute
 * whitelist, serialised meta, post creation, language linking — without spending
 * a character of quota or waiting on the network. Output is deterministic so
 * tests can assert on it, and it varies by target language so a test cannot pass
 * by confusing two languages.
 *
 * It deliberately does NOT produce plausible translations. Anything it writes has
 * to be obviously fake in the editor, so a stub run can never be mistaken for a
 * real one and left on a live site.
 */
class StubTranslator implements TranslatorInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @param array<int, string> $texts       Strings to translate.
	 * @param string             $target_lang DeepL target code.
	 * @return array<int, string>
	 */
	public function translate( array $texts, string $target_lang ): array {
		$prefix = '[' . strtoupper( $target_lang ) . '] ';

		return array_map(
			static fn( $t ): string => '' === trim( (string) $t ) ? (string) $t : $prefix . (string) $t,
			array_values( $texts )
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array{character_count: int, character_limit: int}
	 */
	public function usage(): array {
		return array(
			'character_count' => 0,
			'character_limit' => 500000,
		);
	}
}
