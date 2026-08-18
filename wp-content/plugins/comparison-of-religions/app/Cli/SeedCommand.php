<?php
/**
 * WP-CLI access to the seed files.
 *
 * Deliberately not an activation hook. At activation Polylang may be installed
 * but not yet configured, and an import that runs then writes every topic with
 * no language - a mess that is tedious to undo and easy to miss. A command is
 * run when somebody means it, and `status` says what would happen first.
 *
 * @package ComparisonOfReligions
 */

declare(strict_types=1);

namespace ComparisonOfReligions\Cli;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ComparisonOfReligions\Seed\Languages;
use ComparisonOfReligions\Seed\SeedExporter;
use ComparisonOfReligions\Seed\SeedFile;
use ComparisonOfReligions\Seed\SeedImporter;
use RuntimeException;
use WP_CLI;

/**
 * Reads and writes the shipped comparison data.
 */
class SeedCommand {

	/**
	 * Report what the seed can do here, before anything is written.
	 *
	 * ## EXAMPLES
	 *
	 *     wp comparison-of-religions seed status
	 *
	 * @return void
	 */
	public function status(): void {
		$site    = Languages::site();
		$on_disk = SeedFile::languages();

		WP_CLI::line( 'Polylang:            ' . ( Languages::available() ? 'active' : 'not active' ) );
		WP_CLI::line( 'Site languages:      ' . implode( ', ', $site ) );
		WP_CLI::line( 'Seed files on disk:  ' . ( [] === $on_disk ? '(none)' : implode( ', ', $on_disk ) ) );
		$served    = [];
		$unmatched = [];

		foreach ( $site as $lang ) {
			$file = SeedFile::for_language( $lang );

			if ( null === $file ) {
				$unmatched[] = $lang;
				continue;
			}

			$served[ $lang ] = $lang === $file ? $lang : $lang . ' (from ' . $file . '.json)';
		}

		WP_CLI::line( 'Would import:        ' . $this->format_list( $served ) );
		WP_CLI::line( 'Files without a language on this site: ' . $this->format_list( array_diff( $on_disk, array_keys( $served ) ) ) );
		WP_CLI::line( 'Languages without a file:             ' . $this->format_list( $unmatched ) );

		if ( Languages::stale_translations() ) {
			WP_CLI::warning(
				'This database holds several languages but Polylang is not active, so they cannot be told apart. '
				. 'Export is refused in this state; import still works for the source language.'
			);
		}
	}

	/**
	 * Write the current database contents into the seed files.
	 *
	 * ## OPTIONS
	 *
	 * [--lang=<slugs>]
	 * : Comma-separated language slugs. Default: every language the site has.
	 *
	 * ## EXAMPLES
	 *
	 *     wp comparison-of-religions seed export
	 *     wp comparison-of-religions seed export --lang=pl,uk
	 *
	 * @param array<int, string>    $args       Positional arguments, unused.
	 * @param array<string, string> $assoc_args Flags.
	 * @return void
	 */
	public function export( array $args, array $assoc_args ): void {
		unset( $args );

		try {
			$report = ( new SeedExporter() )->export( $this->languages( $assoc_args ) );
		} catch ( RuntimeException $e ) {
			WP_CLI::error( $e->getMessage() );
			return;
		}

		if ( [] === $report ) {
			WP_CLI::warning( 'Nothing to export: no requested language exists on this site.' );
			return;
		}

		foreach ( $report as $lang => $counts ) {
			WP_CLI::line(
				sprintf(
					'%-4s %2d categories, %2d topics -> %s',
					$lang,
					$counts['categories'],
					$counts['topics'],
					$counts['written'] ? SeedFile::path( $lang ) : 'FAILED TO WRITE'
				)
			);
		}

		WP_CLI::success( sprintf( 'Exported %d language(s).', count( $report ) ) );
	}

	/**
	 * Create categories and topics from the seed files.
	 *
	 * Existing records are left alone. With --overwrite, records this import
	 * created are refreshed, but any that a person has edited since are still
	 * skipped and counted separately.
	 *
	 * ## OPTIONS
	 *
	 * [--lang=<slugs>]
	 * : Comma-separated language slugs. Default: every language the site has a file for.
	 *
	 * [--overwrite]
	 * : Refresh untouched records from the files.
	 *
	 * [--dry-run]
	 * : Report what would happen and change nothing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp comparison-of-religions seed import --dry-run
	 *     wp comparison-of-religions seed import
	 *     wp comparison-of-religions seed import --lang=uk --overwrite
	 *
	 * @param array<int, string>    $args       Positional arguments, unused.
	 * @param array<string, string> $assoc_args Flags.
	 * @return void
	 */
	public function import( array $args, array $assoc_args ): void {
		unset( $args );

		$importer = new SeedImporter(
			isset( $assoc_args['dry-run'] ),
			isset( $assoc_args['overwrite'] )
		);

		try {
			$report = $importer->import( $this->languages( $assoc_args ) );
		} catch ( RuntimeException $e ) {
			WP_CLI::error( $e->getMessage() );
			return;
		}

		WP_CLI::line( 'Polylang: ' . ( true === $report['polylang'] ? 'active' : 'not active - source language only' ) );

		$languages = is_array( $report['languages'] ) ? $report['languages'] : [];

		foreach ( $languages as $lang => $counts ) {
			if ( isset( $counts['error'] ) ) {
				WP_CLI::warning( sprintf( '%s: %s', (string) $lang, (string) $counts['error'] ) );
				continue;
			}

			WP_CLI::line(
				sprintf(
					'%-4s categories +%d ~%d =%d !%d   topics +%d ~%d =%d, hand-edited %d',
					(string) $lang,
					(int) ( $counts['categories_created'] ?? 0 ),
					(int) ( $counts['categories_updated'] ?? 0 ),
					(int) ( $counts['categories_existing'] ?? 0 ),
					(int) ( $counts['categories_edited'] ?? 0 ),
					(int) ( $counts['topics_created'] ?? 0 ),
					(int) ( $counts['topics_updated'] ?? 0 ),
					(int) ( $counts['topics_existing'] ?? 0 ),
					(int) ( $counts['topics_edited'] ?? 0 )
				)
			);
		}

		$notes = [
			'missing_files' => 'no seed file for',
			'unused_files'  => 'file unused, site has no such language:',
		];

		foreach ( $notes as $field => $label ) {
			$list = is_array( $report[ $field ] ) ? $report[ $field ] : [];

			if ( [] !== $list ) {
				WP_CLI::line( sprintf( '%s %s', $label, implode( ', ', array_map( 'strval', $list ) ) ) );
			}
		}

		if ( true === $report['polylang'] ) {
			WP_CLI::line( sprintf( 'Linked %d topic group(s) and %d category group(s).', (int) $report['linked_posts'], (int) $report['linked_terms'] ) );
		}

		if ( true === $report['dry_run'] ) {
			WP_CLI::success( 'Dry run: nothing was written.' );
			return;
		}

		WP_CLI::success( 'Import finished.' );
	}

	/**
	 * The --lang flag as a list of slugs.
	 *
	 * @param array<string, string> $assoc_args Flags.
	 * @return array<int, string>
	 */
	private function languages( array $assoc_args ): array {
		if ( ! isset( $assoc_args['lang'] ) || '' === $assoc_args['lang'] ) {
			return [];
		}

		$slugs = array_map( 'trim', explode( ',', $assoc_args['lang'] ) );

		return array_values( array_filter( $slugs, static fn( string $slug ): bool => '' !== $slug ) );
	}

	/**
	 * A list for reading, or a word saying there is none.
	 *
	 * @param array<int|string, string> $items Slugs.
	 */
	private function format_list( array $items ): string {
		$values = array_values( array_map( 'strval', $items ) );

		return [] === $values ? '(none)' : implode( ', ', $values );
	}
}
