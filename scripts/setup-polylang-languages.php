<?php
/**
 * Creates the four languages and sets Polylang's options.
 *
 * Run with:
 *   ddev wp eval-file scripts/setup-polylang-languages.php --path=/var/www/html          (dry run)
 *   ddev wp eval-file scripts/setup-polylang-languages.php --path=/var/www/html -- go    (writes)
 *
 * WHY THIS EXISTS. Deployment reproduces the content on the server by running the
 * scripts in this repository rather than by pushing a local database dump — the
 * production database is the source of truth and a dump would erase everything
 * written there since it was pulled. That only works if EVERY local change has a
 * script, and this was the last one missing: the languages themselves and the
 * Polylang options were configured by hand in the admin panel, which is exactly the
 * kind of step that gets forgotten and then takes an afternoon to rediscover.
 *
 * IDEMPOTENT on purpose. It can be run again on a site that is already configured:
 * languages that exist are left alone, options are written to the values below
 * whatever they were, and nothing is deleted. That matters because the deployment
 * plan runs it on production, where a half-finished run must be safe to repeat.
 *
 * WHAT IT DOES NOT DO. It does not assign a language to existing content — that is
 * Polylang's own job through its admin, and doing it blind from a script would set
 * the wrong language on anything already translated. It does not create menus
 * (`scripts/setup-language-menus.php`) and does not translate anything
 * (`wp kzmielec-translate`).
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs this through eval(), where a
// declare would have to be the first statement of the script and so cannot appear.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$kz_go = in_array( 'go', (array) $args, true );

if ( ! function_exists( 'PLL' ) || ! PLL() ) {
	WP_CLI::error( 'Polylang nie jest aktywny — najpierw go wlacz.' );
}

/**
 * The four languages, in display order.
 *
 * `locale` is what WordPress loads translation files for, and it is the one field
 * that cannot be guessed: the theme's .mo files are named `en_GB.mo`, `uk.mo` and
 * `es_ES.mo`, so those exact locales have to be used or the interface silently
 * stays Polish. Ukrainian is plain `uk`, not `uk_UA` — that is WordPress's own
 * locale code for it.
 *
 * `flag` is a country code, not a language code, which is why English is `gb`
 * and Ukrainian `ua`. It only matters in the admin: the front end draws its own
 * inline SVG flags through `Kzmielec\Core\LanguageFlags`, because Polylang serves
 * its flag images over plain http and that is mixed content on an https site.
 */
$kz_languages = array(
	array(
		'name'       => 'Polski',
		'slug'       => 'pl',
		'locale'     => 'pl_PL',
		'rtl'        => false,
		'term_group' => 0,
		'flag'       => 'pl',
	),
	array(
		'name'       => 'English',
		'slug'       => 'en',
		'locale'     => 'en_GB',
		'rtl'        => false,
		'term_group' => 1,
		'flag'       => 'gb',
	),
	array(
		'name'       => 'Українська',
		'slug'       => 'uk',
		'locale'     => 'uk',
		'rtl'        => false,
		'term_group' => 2,
		'flag'       => 'ua',
	),
	array(
		'name'       => 'Español',
		'slug'       => 'es',
		'locale'     => 'es_ES',
		'rtl'        => false,
		'term_group' => 3,
		'flag'       => 'es',
	),
);

/**
 * Polylang options, with the reasoning for the ones that are not obvious.
 *
 * `force_lang => 1` puts the language in the URL as a directory (`/en/…`), which is
 * what every other part of this project assumes — the archive rewrites, the guard
 * that retires foreign prefixes, the switcher.
 *
 * `hide_default => true` keeps Polish at the site root with no `/pl/` prefix, so no
 * existing Polish URL changes and nothing that is already indexed moves.
 *
 * `redirect_lang => true` sends `/` to the front page of the current language.
 *
 * `browser => false` — deliberately OFF, and these two options are easy to confuse.
 * `browser` is the guess-from-Accept-Language feature; with it on, a visitor from
 * abroad lands on a translated page without asking, which also makes every cached
 * page ambiguous. The switcher is explicit instead.
 *
 * `post_types` and `taxonomies` are not cosmetic: leave `comparison_category` out
 * and the comparison accordion has nothing to group by, so the page renders empty
 * while still answering 200.
 */
$kz_options = array(
	'default_lang'  => 'pl',
	'force_lang'    => 1,
	'hide_default'  => true,
	'redirect_lang' => true,
	'browser'       => false,
	'rewrite'       => true,
	'media_support' => false,
	'post_types'    => array( 'meetings', 'comparison_topic' ),
	'taxonomies'    => array( 'comparison_category' ),
);

$kz_existing = array();

foreach ( PLL()->model->get_languages_list() as $kz_lang ) {
	$kz_existing[ $kz_lang->slug ] = $kz_lang->locale;
}

$kz_added = 0;

foreach ( $kz_languages as $kz_def ) {
	if ( isset( $kz_existing[ $kz_def['slug'] ] ) ) {
		$kz_note = ( $kz_existing[ $kz_def['slug'] ] === $kz_def['locale'] )
			? 'jest'
			: sprintf( 'UWAGA: jest, ale z lokalizacja %s zamiast %s', $kz_existing[ $kz_def['slug'] ], $kz_def['locale'] );

		WP_CLI::log( sprintf( '  %-3s %s', $kz_def['slug'], $kz_note ) );
		continue;
	}

	if ( ! $kz_go ) {
		WP_CLI::log( sprintf( '  %-3s do utworzenia (%s)', $kz_def['slug'], $kz_def['locale'] ) );
		++$kz_added;
		continue;
	}

	$kz_result = PLL()->model->languages->add( $kz_def );

	if ( is_wp_error( $kz_result ) ) {
		WP_CLI::warning( sprintf( '  %-3s BLAD: %s', $kz_def['slug'], $kz_result->get_error_message() ) );
		continue;
	}

	WP_CLI::log( sprintf( '  %-3s utworzony', $kz_def['slug'] ) );
	++$kz_added;
}

if ( $kz_go ) {
	/*
	 * Written through Polylang's own options object, NOT with `update_option()`.
	 *
	 * A raw write looks like it works and does not. Polylang 3.8 keeps the option behind
	 * a typed registry that validates on write, and three of these values were silently
	 * dropped: `post_types` and `taxonomies` came back as empty arrays and
	 * `redirect_lang` as `false`. The site then answers 200 everywhere while the meetings
	 * and the comparison topics are not translatable at all — which is how a deployment
	 * would have failed. The local database looked healthy only because those two
	 * settings had once been ticked by hand in the admin.
	 *
	 * `set()` is no help in telling whether it worked: it returns a `WP_Error` with zero
	 * codes on success, and swallows a wrong type without reporting a code either. The
	 * only trustworthy confirmation is reading the value back, which is what the report
	 * at the end of this script now does.
	 */
	if ( isset( PLL()->options ) && is_object( PLL()->options ) && method_exists( PLL()->options, 'set' ) ) {
		foreach ( $kz_options as $kz_key => $kz_value ) {
			PLL()->options->set( $kz_key, $kz_value );
		}

		PLL()->options->save();
	} else {
		$kz_current = (array) get_option( 'polylang', array() );

		// Merged, not replaced: `nav_menus`, `sync` and Polylang's own bookkeeping
		// (`version`, `first_activation`) live in the same option and are not ours.
		update_option( 'polylang', array_merge( $kz_current, $kz_options ) );
	}

	/*
	 * The language objects are cached, and the cache does not notice a new language
	 * or a changed option. Skipping this is how `/en/` ends up serving the Polish
	 * front page: `page_on_front` is a property of the cached language object.
	 */
	PLL()->model->clean_languages_cache();

	/*
	 * Deleted rather than flushed. `flush_rewrite_rules()` here would rebuild the
	 * rules inside this request, before the new languages are visible to every
	 * plugin that registers rules; deleting defers the rebuild to the next request,
	 * when the state is settled.
	 */
	delete_option( 'rewrite_rules' );

	wp_cache_flush();
}

WP_CLI::log( '' );

/*
 * The report reads the option back instead of echoing what was asked for. The previous
 * version printed the intended values and so announced `post_types
 * ["meetings","comparison_topic"]` on a run where nothing of the sort had been stored. A
 * report that cannot disagree with the code above it is decoration.
 */
$kz_stored  = (array) get_option( 'polylang', array() );
$kz_mismatch = array();

$kz_show = static function ( $value ) {
	return is_scalar( $value ) || is_null( $value ) ? var_export( $value, true ) : wp_json_encode( $value );
};

foreach ( $kz_options as $kz_key => $kz_value ) {
	$kz_actual = $kz_stored[ $kz_key ] ?? null;
	$kz_same   = ( $kz_actual == $kz_value ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- 1 and '1' are the same setting here.

	WP_CLI::log(
		sprintf(
			'  %-14s %-34s %s',
			$kz_key,
			$kz_show( $kz_actual ),
			$kz_same ? '' : '<- CHCIALEM: ' . $kz_show( $kz_value )
		)
	);

	if ( ! $kz_same ) {
		$kz_mismatch[] = $kz_key;
	}
}

WP_CLI::log( '' );
WP_CLI::log( sprintf( 'jezykow do utworzenia/utworzonych: %d, juz obecnych: %d', $kz_added, count( $kz_existing ) ) );

if ( ! $kz_go ) {
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
} elseif ( $kz_mismatch ) {
	WP_CLI::error( 'Te opcje NIE zapisaly sie: ' . implode( ', ', $kz_mismatch ) . '. Bez nich typy tresci nie sa tlumaczalne.' );
} else {
	WP_CLI::success( 'Gotowe, wszystkie opcje potwierdzone odczytem. Reguly przepisywania odbuduja sie przy nastepnym zadaniu.' );
}
