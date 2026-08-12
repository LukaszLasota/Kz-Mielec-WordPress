<?php
/**
 * WP-CLI entry point.
 *
 * @package KzmielecTranslate
 */

declare(strict_types=1);

namespace KzmielecTranslate\Cli;

use KzmielecTranslate\Services\DeeplClient;
use KzmielecTranslate\Services\Glossary;
use KzmielecTranslate\Services\StubTranslator;
use KzmielecTranslate\Services\TranslatorInterface;
use KzmielecTranslate\Translators\PostTranslator;
use KzmielecTranslate\Translators\TermTranslator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fills translations for the site's content.
 *
 * Reporting is the default and writing needs `--execute`, deliberately: a
 * mistyped command should cost nothing, and 58 posts across three languages is
 * not something to create by accident.
 */
class TranslateCommand {

	/**
	 * Translate content into one language.
	 *
	 * ## OPTIONS
	 *
	 * --lang=<lang>
	 * : Target language slug: en, uk or es.
	 *
	 * [--post-type=<types>]
	 * : Comma-separated list. Default: page,meetings,comparison_topic
	 *
	 * [--execute]
	 * : Write to the database. Without it the command only reports.
	 *
	 * [--force]
	 * : Overwrite translations that already exist. Requires --execute.
	 *
	 * [--stub]
	 * : Use the deterministic stub instead of DeepL. For testing without a key.
	 *
	 * ## EXAMPLES
	 *
	 *     wp kzmielec-translate run --lang=en
	 *     wp kzmielec-translate run --lang=en --stub --execute
	 *     wp kzmielec-translate run --lang=uk --execute
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Flags.
	 * @return void
	 */
	public function run( array $args, array $assoc_args ): void {
		$lang = (string) ( $assoc_args['lang'] ?? '' );

		if ( ! isset( PostTranslator::DEEPL_LANG[ $lang ] ) ) {
			\WP_CLI::error( 'Nieznany język: "' . $lang . '". Dozwolone: en, uk, es.' );
		}

		$execute = isset( $assoc_args['execute'] );
		$force   = isset( $assoc_args['force'] );
		$stub    = isset( $assoc_args['stub'] );

		if ( $force && ! $execute ) {
			\WP_CLI::error( '--force wymaga --execute.' );
		}

		$translator = $this->translator( $lang, $stub );
		$types      = array_map( 'trim', explode( ',', (string) ( $assoc_args['post-type'] ?? 'page,meetings,comparison_topic' ) ) );

		$posts = get_posts(
			array(
				'post_type'   => $types,
				'post_status' => array( 'publish', 'draft' ),
				'numberposts' => -1,
				'lang'        => 'pl',
				'fields'      => 'ids',
			)
		);

		\WP_CLI::log(
			sprintf(
				'%s → %s, wpisów: %d, tłumacz: %s',
				$execute ? 'ZAPIS' : 'RAPORT (bez --execute nic nie zostanie zapisane)',
				$lang,
				count( $posts ),
				$stub ? 'zaślepka' : 'DeepL'
			)
		);

		/*
		 * Terms first, and not as a detail of ordering: every post that follows is
		 * filed under the translated term, so a term created afterwards would leave
		 * the whole run uncategorised.
		 */
		$terminy = ( new TermTranslator( $translator ) )->translate_all(
			$lang,
			PostTranslator::DEEPL_LANG[ $lang ],
			$execute,
			$force
		);

		\WP_CLI::log(
			sprintf(
				'  taksonomie: terminów %d, znaków %d%s',
				$terminy['terms'],
				$terminy['chars'],
				$execute ? ', utworzono ' . $terminy['created'] : ''
			)
		);

		$translator_obj = new PostTranslator( $translator );
		$suma           = array(
			'created'  => 0,
			'segments' => 0,
			'chars'    => 0,
			'skipped'  => 0,
		);

		foreach ( $posts as $id ) {
			$r = $translator_obj->translate( (int) $id, $lang, $execute, $force );

			$suma['created']  += $r['created'];
			$suma['segments'] += $r['segments'];
			$suma['chars']    += $r['chars'];

			if ( 0 === $r['created'] && $r['target_id'] > 0 && $execute && ! $force ) {
				++$suma['skipped'];
			}

			$stan = '';

			if ( $r['created'] > 0 ) {
				$stan = '→ utworzono #' . $r['target_id'];
			} elseif ( $r['target_id'] > 0 ) {
				$stan = '→ istnieje #' . $r['target_id'];
			}

			\WP_CLI::log(
				sprintf(
					'  #%-5d %-44s segm=%-4d znaków=%-6d %s',
					$id,
					mb_substr( (string) get_the_title( (int) $id ), 0, 42 ),
					$r['segments'],
					$r['chars'],
					$stan
				)
			);
		}

		\WP_CLI::log( '' );
		\WP_CLI::log( sprintf( 'Segmentów: %d, znaków do wysłania: %d', $suma['segments'], $suma['chars'] ) );

		if ( ! $stub ) {
			$this->report_usage( $translator, $suma['chars'] );
		}

		if ( $execute ) {
			\WP_CLI::success( sprintf( 'Utworzono %d, pominięto istniejących %d.', $suma['created'], $suma['skipped'] ) );
		} else {
			\WP_CLI::success( 'Raport zakończony. Dodaj --execute, żeby zapisać.' );
		}
	}

	/**
	 * Print the quota position and warn when a run would exceed it.
	 *
	 * @param TranslatorInterface $translator Translator in use.
	 * @param int                 $chars      Characters this run would send.
	 * @return void
	 */
	private function report_usage( TranslatorInterface $translator, int $chars ): void {
		try {
			$u = $translator->usage();

			\WP_CLI::log(
				sprintf(
					'Limit DeepL: %d / %d znaków wykorzystane; po tym uruchomieniu byłoby %d.',
					$u['character_count'],
					$u['character_limit'],
					$u['character_count'] + $chars
				)
			);

			if ( $u['character_limit'] > 0 && $u['character_count'] + $chars > $u['character_limit'] ) {
				\WP_CLI::warning( 'To uruchomienie przekroczyłoby limit. DeepL odrzuci żądania kodem 456.' );
			}
		} catch ( \RuntimeException $e ) {
			\WP_CLI::warning( $e->getMessage() );
		}
	}

	/**
	 * Pick a translator implementation.
	 *
	 * @param string $lang Target language slug.
	 * @param bool   $stub Whether the stub was requested.
	 * @return TranslatorInterface
	 */
	private function translator( string $lang, bool $stub ): TranslatorInterface {
		if ( $stub ) {
			return new StubTranslator();
		}

		$deepl  = PostTranslator::DEEPL_LANG[ $lang ];
		$client = DeeplClient::from_settings( Glossary::ensure( $deepl ) );

		if ( null === $client ) {
			\WP_CLI::error(
				'Brak klucza DeepL. Ustaw go w Narzędzia → Tłumaczenie DeepL albo stałą KZMIELEC_DEEPL_API_KEY w wp-config.php. Do testów bez klucza użyj --stub.'
			);
		}

		return $client;
	}
}
