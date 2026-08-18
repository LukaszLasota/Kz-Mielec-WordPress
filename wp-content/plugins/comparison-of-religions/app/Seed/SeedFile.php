<?php
/**
 * Reading and writing one language's seed file.
 *
 * The seed is plain JSON, one file per language, in `data/seed/`. It is meant to
 * be read and corrected by a person and reviewed in a diff, which is why it is
 * written pretty-printed, with Unicode left alone - an escaped `Є` tells a
 * reviewer nothing.
 *
 * There is deliberately no timestamp in the payload. A generated date would make
 * every export a change even when no data moved, and a diff that is always dirty
 * is a diff nobody reads.
 *
 * @package ComparisonOfReligions
 */

declare(strict_types=1);

namespace ComparisonOfReligions\Seed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Locates, reads and writes the seed files.
 */
class SeedFile {

	/**
	 * Directory holding the files, relative to the plugin root.
	 */
	public const DIRECTORY = 'data/seed';

	/**
	 * Payload format version.
	 *
	 * Raised only when the shape changes in a way an older importer could not
	 * read. The importer refuses a version it does not know rather than guessing.
	 */
	public const FORMAT = 1;

	/**
	 * Absolute path of one language's file.
	 *
	 * @param string $lang Language slug.
	 */
	public static function path( string $lang ): string {
		return self::directory() . '/' . $lang . '.json';
	}

	/**
	 * Absolute path of the seed directory.
	 */
	public static function directory(): string {
		return rtrim( COR_PLUGIN_DIR, '/' ) . '/' . self::DIRECTORY;
	}

	/**
	 * Language slugs that have a file on disk.
	 *
	 * @return array<int, string>
	 */
	public static function languages(): array {
		$found = glob( self::directory() . '/*.json' );

		if ( false === $found ) {
			return [];
		}

		$slugs = array_map(
			static fn( string $file ): string => basename( $file, '.json' ),
			$found
		);

		sort( $slugs );

		return $slugs;
	}

	/**
	 * Which file serves this site language, if any.
	 *
	 * Language slugs are the site's choice, not ours. Polylang defaults to two
	 * letters, so `en.json` usually matches `en` outright, but a site is free to
	 * use `en-gb`, `pt-br` or `es-mx`, and then an exact comparison finds nothing
	 * and the language silently imports as empty. The base subtag is tried second:
	 * `en-gb` is served by `en.json`, which is the right answer for content that
	 * differs by nation rather than by language.
	 *
	 * Returns null when nothing fits, and the caller reports that rather than
	 * guessing - `de` has no business being served Spanish.
	 *
	 * @param string $lang Site language slug.
	 * @return string|null File slug, or null when there is no sensible match.
	 */
	public static function for_language( string $lang ): ?string {
		$available = self::languages();

		if ( in_array( $lang, $available, true ) ) {
			return $lang;
		}

		$base = strtolower( (string) preg_replace( '/[_-].*$/', '', $lang ) );

		if ( '' !== $base && in_array( $base, $available, true ) ) {
			return $base;
		}

		return null;
	}

	/**
	 * Read and validate one language's file.
	 *
	 * @param string $lang Language slug.
	 * @return array{version:int,language:string,categories:array<int,array<string,mixed>>,topics:array<int,array<string,mixed>>}|null
	 *         Null when the file is missing, unreadable, not JSON, or not the shape we wrote.
	 */
	public static function read( string $lang ): ?array {
		$path = self::path( $lang );

		if ( ! is_readable( $path ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Plugin's own file on local disk; WP_Filesystem is for user-managed content.
		$raw = file_get_contents( $path );

		if ( false === $raw ) {
			return null;
		}

		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			return null;
		}

		$version    = isset( $data['version'] ) ? (int) $data['version'] : 0;
		$categories = isset( $data['categories'] ) && is_array( $data['categories'] ) ? $data['categories'] : null;
		$topics     = isset( $data['topics'] ) && is_array( $data['topics'] ) ? $data['topics'] : null;

		if ( self::FORMAT !== $version || null === $categories || null === $topics ) {
			return null;
		}

		return [
			'version'    => $version,
			'language'   => isset( $data['language'] ) ? (string) $data['language'] : $lang,
			'categories' => array_values( array_filter( $categories, 'is_array' ) ),
			'topics'     => array_values( array_filter( $topics, 'is_array' ) ),
		];
	}

	/**
	 * Write one language's file, creating the directory if needed.
	 *
	 * @param string                                                                                 $lang    Language slug.
	 * @param array{categories:array<int,array<string,mixed>>,topics:array<int,array<string,mixed>>} $payload Data to store.
	 * @return bool True when the bytes reached the disk.
	 */
	public static function write( string $lang, array $payload ): bool {
		$dir = self::directory();

		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		$json = wp_json_encode(
			[
				'version'    => self::FORMAT,
				'language'   => $lang,
				'categories' => $payload['categories'],
				'topics'     => $payload['topics'],
			],
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);

		if ( false === $json ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- See read(): the plugin writes into its own directory, from WP-CLI, as a developer action. WP_Filesystem exists for content managed through the admin and would demand credentials here.
		return false !== file_put_contents( self::path( $lang ), $json . "\n" );
	}
}
