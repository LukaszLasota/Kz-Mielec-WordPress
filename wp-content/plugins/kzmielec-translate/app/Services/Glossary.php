<?php
/**
 * Term glossary, versioned in the repository and pushed to DeepL.
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
 * Forces the terms that must not vary between posts.
 *
 * Preventing an inconsistency is cheaper than finding it afterwards. Without a
 * glossary, "Kościół Zielonoświątkowy" comes back as three different English
 * phrases across the 37 comparison topics, and the only way to notice is to read
 * all of them.
 *
 * The pairs live in CSV files inside the plugin so they are reviewable in a diff
 * and travel with the code. DeepL keeps its own copy behind an id; we cache that
 * id per language and re-upload when the file changes, detected by hashing the
 * pairs rather than by remembering to bump a version.
 *
 * The glossary buys **consistency, not correctness**. Which English word is the
 * right one for a given doctrinal term depends on the register a particular
 * community uses, and that cannot be read off the source text — it belongs on the
 * list of open questions the user signs off in Plan C.
 */
class Glossary {

	/**
	 * Option prefix for cached glossary ids.
	 */
	private const OPTION_PREFIX = 'kzt_glossary_';

	/**
	 * DeepL target code => CSV basename.
	 *
	 * @var array<string, string>
	 */
	private const FILES = array(
		'EN-GB' => 'pl-en.csv',
		'UK'    => 'pl-uk.csv',
		'ES'    => 'pl-es.csv',
	);

	/**
	 * DeepL target code => glossary language code.
	 *
	 * DeepL glossaries take bare two-letter codes, so EN-GB becomes EN.
	 *
	 * @var array<string, string>
	 */
	private const GLOSSARY_LANG = array(
		'EN-GB' => 'EN',
		'UK'    => 'UK',
		'ES'    => 'ES',
	);

	/**
	 * Parsed pairs for one target language.
	 *
	 * @param string $target_lang DeepL target code.
	 * @return array<string, string>
	 */
	public static function pairs( string $target_lang ): array {
		$file = self::FILES[ $target_lang ] ?? '';

		if ( '' === $file ) {
			return array();
		}

		$path = ( defined( 'KZMIELEC_TRANSLATE_DIR' ) ? (string) constant( 'KZMIELEC_TRANSLATE_DIR' ) : '' ) . 'glossary/' . $file;

		if ( ! file_exists( $path ) ) {
			return array();
		}

		$lines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

		if ( false === $lines ) {
			return array();
		}

		$out = array();

		foreach ( $lines as $line ) {
			$parts = str_getcsv( (string) $line );

			if ( count( $parts ) < 2 ) {
				continue;
			}

			$src = trim( (string) $parts[0] );
			$dst = trim( (string) $parts[1] );

			if ( '' !== $src && '' !== $dst ) {
				$out[ $src ] = $dst;
			}
		}

		return $out;
	}

	/**
	 * Glossary id usable with the translate endpoint, creating it if needed.
	 *
	 * Returns an empty string when there is no key or no pairs, which callers
	 * treat as "translate without a glossary" rather than as an error — the point
	 * is that a missing glossary must never block a run.
	 *
	 * @param string $target_lang DeepL target code.
	 * @return string
	 */
	public static function ensure( string $target_lang ): string {
		$pairs = self::pairs( $target_lang );

		if ( array() === $pairs || ! isset( self::GLOSSARY_LANG[ $target_lang ] ) ) {
			return '';
		}

		$key = defined( 'KZMIELEC_DEEPL_API_KEY' )
			? (string) constant( 'KZMIELEC_DEEPL_API_KEY' )
			: (string) get_option( DeeplSettings::OPTION_KEY, '' );

		$key = trim( $key );

		if ( '' === $key ) {
			return '';
		}

		$hash   = md5( (string) wp_json_encode( $pairs ) );
		$option = self::OPTION_PREFIX . strtolower( str_replace( '-', '_', $target_lang ) );
		$stored = (array) get_option( $option, array() );
		$host   = str_ends_with( $key, ':fx' ) ? 'https://api-free.deepl.com/v2/' : 'https://api.deepl.com/v2/';

		if ( ( $stored['hash'] ?? '' ) === $hash && '' !== (string) ( $stored['id'] ?? '' ) && self::exists( $key, $host, (string) $stored['id'] ) ) {
			return (string) $stored['id'];
		}

		/*
		 * The free tier allows exactly ONE glossary per account — measured, not
		 * assumed: creating a second returns 456 "Too many glossaries" while the
		 * account holds a single one. Three languages therefore cannot each keep
		 * their own, so the slot is handed over instead: whatever we put there
		 * before is removed and the language about to run takes it.
		 *
		 * Nothing is lost by this. A glossary only matters while its language is
		 * being translated, and re-uploading eleven pairs costs one request.
		 */
		self::purge_ours( $key, $host );

		$tsv = array();

		foreach ( $pairs as $src => $dst ) {
			$tsv[] = $src . "\t" . $dst;
		}

		$response = wp_remote_post(
			$host . 'glossaries',
			array(
				'timeout' => 30,
				'headers' => array( 'Authorization' => 'DeepL-Auth-Key ' . $key ),
				'body'    => array(
					'name'           => 'kzmielec-' . strtolower( $target_lang ),
					'source_lang'    => 'PL',
					'target_lang'    => self::GLOSSARY_LANG[ $target_lang ],
					'entries'        => implode( "\n", $tsv ),
					'entries_format' => 'tsv',
				),
			)
		);

		if ( is_wp_error( $response ) || 201 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$id   = (string) ( is_array( $data ) ? ( $data['glossary_id'] ?? '' ) : '' );

		if ( '' !== $id ) {
			update_option(
				$option,
				array(
					'id'   => $id,
					'hash' => $hash,
				)
			);
		}

		return $id;
	}

	/**
	 * Whether a glossary id still exists on the account.
	 *
	 * A cached id survives in wp_options after the glossary itself has been
	 * removed — by us handing the slot to another language, or by hand in the
	 * DeepL dashboard. Sending a stale id makes the translate call fail, so the
	 * cache is confirmed rather than trusted.
	 *
	 * @param string $key  DeepL auth key.
	 * @param string $host API base URL with trailing slash.
	 * @param string $id   Glossary id to check.
	 * @return bool
	 */
	private static function exists( string $key, string $host, string $id ): bool {
		$response = wp_remote_get(
			$host . 'glossaries/' . rawurlencode( $id ),
			array(
				'timeout' => 15,
				'headers' => array( 'Authorization' => 'DeepL-Auth-Key ' . $key ),
			)
		);

		return ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Delete every glossary this plugin created, freeing the single free-tier slot.
	 *
	 * Only ours are touched — anything named outside the `kzmielec-` prefix
	 * belongs to somebody else's work on the same account and is left alone.
	 *
	 * @param string $key  DeepL auth key.
	 * @param string $host API base URL with trailing slash.
	 * @return int How many were removed.
	 */
	private static function purge_ours( string $key, string $host ): int {
		$response = wp_remote_get(
			$host . 'glossaries',
			array(
				'timeout' => 20,
				'headers' => array( 'Authorization' => 'DeepL-Auth-Key ' . $key ),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return 0;
		}

		$data       = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$usunietych = 0;

		foreach ( (array) ( is_array( $data ) ? ( $data['glossaries'] ?? array() ) : array() ) as $g ) {
			$name = (string) ( $g['name'] ?? '' );
			$id   = (string) ( $g['glossary_id'] ?? '' );

			if ( '' === $id || 0 !== strpos( $name, 'kzmielec-' ) ) {
				continue;
			}

			$del = wp_remote_request(
				$host . 'glossaries/' . rawurlencode( $id ),
				array(
					'method'  => 'DELETE',
					'timeout' => 15,
					'headers' => array( 'Authorization' => 'DeepL-Auth-Key ' . $key ),
				)
			);

			if ( ! is_wp_error( $del ) && in_array( (int) wp_remote_retrieve_response_code( $del ), array( 200, 204 ), true ) ) {
				++$usunietych;
			}
		}

		// Cached ids now point at nothing, so drop them all.
		foreach ( array_keys( self::FILES ) as $lang ) {
			delete_option( self::OPTION_PREFIX . strtolower( str_replace( '-', '_', $lang ) ) );
		}

		return $usunietych;
	}
}
