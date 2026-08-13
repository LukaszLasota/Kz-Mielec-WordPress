<?php
/**
 * Replaces Scripture quotations that DeepL worded differently than it did during
 * the rehearsal, so `substitute-bible-quotes.php` could not match them.
 *
 * Run with:
 *   ddev wp eval-file scripts/repair-quote-variants.php --path=/var/www/html          (dry run)
 *   ddev wp eval-file scripts/repair-quote-variants.php --path=/var/www/html -- go    (writes)
 *
 * WHY THIS EXISTS. `substitute-bible-quotes.php` matches whole quotations as exact
 * strings, and it fails loudly when a pattern is absent rather than guessing —
 * positional reassembly means a near-miss would put the wrong verse in the slot.
 * That strictness is right, and it also means the script cannot cope with DeepL
 * returning a different wording on a second run. DeepL is not deterministic.
 *
 * On the production deployment of 13 August 2026 it reported four such misses out of
 * fifty-two patterns. Three were near-identical to the rehearsal:
 *
 *   - a full stop moved outside the closing quote (`hindered.’` vs `hindered’`),
 *   - a full stop moved inside a closing guillemet (`Jesucristo.»`),
 *   - one capital letter (`Él` for `él`).
 *
 * The fourth was a real difference of wording, and one verse turned out to be quoted
 * in two pages in two different renderings — hence five entries below for four
 * patterns. Every `old` string here was read back out of the production database, so
 * each match is exact; nothing is normalised or guessed.
 *
 * The `new` strings are the same official translations the main script uses: NIV for
 * English, NVI for Spanish.
 *
 * Posts are found by scanning each language, NOT by post id: ids are not portable
 * between environments, and keying a repair table by them is the very mistake that
 * `fix-seo-titles.php` used to make.
 *
 * Running this twice is safe. A post that already carries the official text is
 * counted as satisfied and left alone.
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs this through eval(), where a
// declare would have to be the first statement of the script and so cannot appear.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$kz_go = in_array( 'go', (array) $args, true );

/**
 * The observed variants: language, the exact text found, the official text to use.
 */
$kz_variants = array(
	// 1 Peter 3:7 — same wording as the rehearsal, minus the full stop before the closing quote.
	array(
		'lang' => 'en',
		'old'  => '‘In the same way, you husbands, live with your wives in an understanding way, showing them honour, as the weaker vessel, since they are heirs of the grace of life, so that your prayers may not be hindered’',
		'new'  => '“Husbands, in the same way be considerate as you live with your wives, and treat them with respect as the weaker partner and as heirs with you of the gracious gift of life, so that nothing will hinder your prayers.”',
	),
	// 1 Peter 3:7 — a genuinely different rendering, in the second page that quotes the verse.
	array(
		'lang' => 'en',
		'old'  => '‘In the same way, you husbands, live with your wives in an understanding way, showing them respect as the weaker partner, and honour them, since they too are heirs of the grace of life, so that your prayers may not be hindered.’',
		'new'  => '“Husbands, in the same way be considerate as you live with your wives, and treat them with respect as the weaker partner and as heirs with you of the gracious gift of life, so that nothing will hinder your prayers.”',
	),
	// 1 Pedro 2:5 — differs from the rehearsal only by the full stop sitting inside the closing guillemet.
	array(
		'lang' => 'es',
		'old'  => '«Y vosotros mismos, como piedras vivas, edificaos como casa espiritual, como sacerdocio santo, para ofrecer sacrificios espirituales agradables a Dios por medio de Jesucristo.»',
		'new'  => '«También ustedes son como piedras vivas, con las cuales se está edificando una casa espiritual. De este modo llegan a ser un sacerdocio santo, para ofrecer sacrificios espirituales que Dios acepta por medio de Jesucristo»',
	),
	// Efesios 5:23 — differs only in the capital in «Él».
	array(
		'lang' => 'es',
		'old'  => '«(...) porque el marido es cabeza de la mujer, así como Cristo es cabeza de la Iglesia, cuerpo del cual Él es el Salvador»',
		'new'  => '«(...) porque el esposo es cabeza de su esposa, así como Cristo es cabeza de la iglesia, la cual es su cuerpo, y él su Salvador»',
	),
	// 2 Corintios 5:20 — a different verb and a different imperative than the rehearsal produced.
	array(
		'lang' => 'es',
		'old'  => '«Por eso, actuamos como embajadores de Cristo, como si Dios exhortara a través de nosotros; en nombre de Cristo os rogamos: reconciliaos con Dios»',
		'new'  => '«Así que somos embajadores de Cristo, como si Dios los exhortara a ustedes por medio de nosotros: “En nombre de Cristo les rogamos que se reconcilien con Dios”»',
	),
);

$kz_types = array( 'page', 'meetings', 'comparison_topic' );

$kz_replaced = 0;
$kz_posts    = 0;
$kz_already  = 0;
$kz_missing  = array();

foreach ( $kz_variants as $kz_variant ) {
	$kz_lang = $kz_variant['lang'];
	$kz_old  = $kz_variant['old'];
	$kz_new  = $kz_variant['new'];

	$kz_ids = get_posts(
		array(
			'post_type'      => $kz_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'lang'           => $kz_lang,
		)
	);

	$kz_found = false;

	foreach ( $kz_ids as $kz_id ) {
		/*
		 * Polylang's `lang` argument is honoured by WP_Query, but a post type that is
		 * not translated would slip through it. Checking the language of each post
		 * keeps a Polish page from being edited by a Spanish rule.
		 */
		if ( function_exists( 'pll_get_post_language' ) && pll_get_post_language( $kz_id ) !== $kz_lang ) {
			continue;
		}

		$kz_content = (string) get_post_field( 'post_content', $kz_id );

		if ( '' === $kz_content ) {
			continue;
		}

		if ( false !== mb_strpos( $kz_content, $kz_new ) ) {
			$kz_found = true;
			++$kz_already;
			continue;
		}

		$kz_count = substr_count( $kz_content, $kz_old );

		if ( 0 === $kz_count ) {
			continue;
		}

		$kz_found = true;

		WP_CLI::log( sprintf( '  [%s] #%-4d x%d  %s', $kz_lang, $kz_id, $kz_count, mb_substr( $kz_old, 0, 54 ) ) );

		if ( $kz_go ) {
			wp_update_post(
				array(
					'ID'           => $kz_id,
					'post_content' => str_replace( $kz_old, $kz_new, $kz_content ),
				)
			);

			/*
			 * The main script marks every post that cites a verse, so the page shows
			 * which translation it quotes. A post repaired here needs the same mark.
			 */
			update_post_meta( $kz_id, '_kzt_scripture', '1' );
		}

		$kz_replaced += $kz_count;
		++$kz_posts;
	}

	if ( ! $kz_found ) {
		$kz_missing[] = sprintf( '[%s] ani wariantu, ani tekstu docelowego: %s', $kz_lang, mb_substr( $kz_old, 0, 70 ) );
	}
}

WP_CLI::log(
	sprintf(
		'wariantow podmienionych: %d w %d wpisach; juz z oficjalnym tekstem: %d',
		$kz_replaced,
		$kz_posts,
		$kz_already
	)
);

if ( $kz_missing ) {
	/*
	 * Not an error on its own: a wording seen once on production need not appear in
	 * every database, and it will be absent locally. It IS worth reading, because a
	 * variant that vanishes everywhere means the table has gone stale.
	 */
	WP_CLI::warning( sprintf( 'wariantow nieobecnych w tej bazie: %d', count( $kz_missing ) ) );

	foreach ( $kz_missing as $kz_m ) {
		WP_CLI::log( '   ' . $kz_m );
	}
}

if ( $kz_go ) {
	WP_CLI::success( 'Zapisane.' );
} else {
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
}
