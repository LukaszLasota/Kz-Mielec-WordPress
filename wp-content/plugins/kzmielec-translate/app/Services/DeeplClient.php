<?php
/**
 * DeepL API client.
 *
 * @package KzmielecTranslate
 */

declare(strict_types=1);

namespace KzmielecTranslate\Services;

use KzmielecTranslate\Admin\DeeplSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Talks to DeepL over the REST API.
 *
 * `tag_handling=html` is mandatory for this project: the text handed over is
 * block markup, and without it DeepL rewrites tags and the posts stop opening in
 * the editor.
 */
class DeeplClient implements TranslatorInterface {

	/**
	 * Free-tier keys carry this suffix and must use a different host.
	 */
	private const FREE_SUFFIX = ':fx';

	/**
	 * Largest batch DeepL accepts in one request.
	 */
	private const MAX_TEXTS = 50;

	/**
	 * Authentication key.
	 *
	 * @var string
	 */
	private string $key;

	/**
	 * Glossary id to apply, empty when none.
	 *
	 * @var string
	 */
	private string $glossary_id;

	/**
	 * Constructor.
	 *
	 * @param string $key         DeepL auth key.
	 * @param string $glossary_id Optional glossary id.
	 */
	public function __construct( string $key, string $glossary_id = '' ) {
		$this->key         = trim( $key );
		$this->glossary_id = $glossary_id;
	}

	/**
	 * Build a client from stored settings, or nothing if no key is configured.
	 *
	 * A constant in wp-config.php wins over the option, so a machine can carry the
	 * key without it ever entering the database or a database backup.
	 *
	 * @param string $glossary_id Optional glossary id.
	 * @return self|null
	 */
	public static function from_settings( string $glossary_id = '' ): ?self {
		$key = defined( 'KZMIELEC_DEEPL_API_KEY' )
			? (string) constant( 'KZMIELEC_DEEPL_API_KEY' )
			: (string) get_option( DeeplSettings::OPTION_KEY, '' );

		$key = trim( $key );

		return '' === $key ? null : new self( $key, $glossary_id );
	}

	/**
	 * Endpoint host for a given key.
	 *
	 * Derived from the key itself rather than configured, because a mismatch
	 * between key and host returns 403 with no useful message and is a genuinely
	 * confusing failure to debug.
	 *
	 * @param string $key DeepL auth key.
	 * @return string
	 */
	private static function endpoint_for( string $key ): string {
		return str_ends_with( trim( $key ), self::FREE_SUFFIX )
			? 'https://api-free.deepl.com/v2/'
			: 'https://api.deepl.com/v2/';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array<int, string> $texts       Strings to translate.
	 * @param string             $target_lang DeepL target code.
	 * @return array<int, string>
	 * @throws \RuntimeException When the API refuses or answers unexpectedly.
	 */
	public function translate( array $texts, string $target_lang ): array {
		$texts = array_values( $texts );

		if ( array() === $texts ) {
			return array();
		}

		$out = array();

		foreach ( array_chunk( $texts, self::MAX_TEXTS ) as $chunk ) {
			$body = array(
				'text'                => $chunk,
				'source_lang'         => 'PL',
				'target_lang'         => $target_lang,
				'tag_handling'        => 'html',
				'preserve_formatting' => '1',
			);

			if ( '' !== $this->glossary_id ) {
				$body['glossary_id'] = $this->glossary_id;
			}

			$response = wp_remote_post(
				self::endpoint_for( $this->key ) . 'translate',
				array(
					'timeout' => 60,
					'headers' => array( 'Authorization' => 'DeepL-Auth-Key ' . $this->key ),
					'body'    => $body,
				)
			);

			$out = array_merge( $out, $this->parse_translations( $response, count( $chunk ) ) );
		}

		return $out;
	}

	/**
	 * Turn a response into a list of translations.
	 *
	 * @param array|\WP_Error $response HTTP response.
	 * @param int             $expected How many translations the request asked for.
	 * @return array<int, string>
	 * @throws \RuntimeException When the response is an error or the wrong shape.
	 */
	private function parse_translations( $response, int $expected ): array {
		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException( esc_html( 'DeepL: ' . $response->get_error_message() ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = (string) wp_remote_retrieve_body( $response );

		if ( 456 === $code ) {
			throw new \RuntimeException( 'DeepL: wyczerpany limit znaków na ten okres rozliczeniowy (456).' );
		}

		if ( 403 === $code ) {
			throw new \RuntimeException( 'DeepL: klucz odrzucony (403). Sprawdź, czy klucz z sufiksem :fx nie trafił na endpoint płatny lub odwrotnie.' );
		}

		if ( 200 !== $code ) {
			throw new \RuntimeException( esc_html( "DeepL: HTTP $code — " . substr( $raw, 0, 200 ) ) );
		}

		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) || ! isset( $data['translations'] ) || ! is_array( $data['translations'] ) ) {
			throw new \RuntimeException( esc_html( 'DeepL: nieoczekiwana odpowiedź — ' . substr( $raw, 0, 200 ) ) );
		}

		$texts = array_map(
			static fn( $t ): string => (string) ( $t['text'] ?? '' ),
			$data['translations']
		);

		/*
		 * Positional reassembly downstream means a short answer would shift text
		 * into the wrong slot, producing a page that looks translated and is
		 * wrong. Failing loudly is the lesser harm.
		 */
		if ( count( $texts ) !== $expected ) {
			throw new \RuntimeException(
				esc_html(
					sprintf(
						'DeepL: zwrócono %d tłumaczeń, oczekiwano %d — przerwane, żeby nie pomieszać segmentów.',
						count( $texts ),
						$expected
					)
				)
			);
		}

		return $texts;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array{character_count: int, character_limit: int}
	 * @throws \RuntimeException When the API cannot be reached.
	 */
	public function usage(): array {
		$response = wp_remote_get(
			self::endpoint_for( $this->key ) . 'usage',
			array(
				'timeout' => 20,
				'headers' => array( 'Authorization' => 'DeepL-Auth-Key ' . $this->key ),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException( esc_html( 'DeepL usage: ' . $response->get_error_message() ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 403 === $code ) {
			throw new \RuntimeException( 'DeepL usage: klucz odrzucony (403).' );
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		return array(
			'character_count' => (int) ( is_array( $data ) ? ( $data['character_count'] ?? 0 ) : 0 ),
			'character_limit' => (int) ( is_array( $data ) ? ( $data['character_limit'] ?? 0 ) : 0 ),
		);
	}
}
