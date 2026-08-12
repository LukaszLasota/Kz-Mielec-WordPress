<?php
/**
 * Moves the Spanish content off the peninsular "vosotros" onto neutral "ustedes".
 *
 * Run with:
 *   ddev wp eval-file scripts/neutralize-spanish-register.php --path=/var/www/html          (dry run)
 *   ddev wp eval-file scripts/neutralize-spanish-register.php --path=/var/www/html -- go    (writes)
 *
 * WHY "USTEDES". Three reasons, in order of weight:
 *
 * 1. Spanish speakers in Poland are overwhelmingly Latin American, and "vosotros"
 *    is used in Spain and Equatorial Guinea only. It is not merely unusual elsewhere
 *    — to a Colombian or Venezuelan reader it marks the text as written for someone
 *    else.
 * 2. "Ustedes" carries in both directions. Spaniards read it without friction, as
 *    formal address; Latin Americans read "vosotros" as foreign. There is no
 *    symmetrical cost.
 * 3. The Scripture quotations already use "ustedes", because the NVI does. Leaving
 *    the prose on "vosotros" would have the page switch register mid-paragraph.
 *
 * SCALE. Measured before deciding: ten occurrences across 58 Spanish posts, and six
 * of those sat inside the Bible quotations that `substitute-bible-quotes.php` has
 * already replaced with NVI text. Four remain, in three posts, and they are handled
 * here by name. DeepL had produced almost entirely neutral Spanish on its own; this
 * is a finishing pass, not a rewrite.
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
 * Exact replacements. Applied to every Spanish post, so a second copy of the same
 * sentence on the aggregating "derecho" page is caught too — the lesson from the
 * Scripture substitution, which first missed exactly that.
 *
 * @var array<int, array{0: string, 1: string}>
 */
$kz_pairs = array(
	// "Os animamos" -> "Los animamos" (statement on phenomena of revival).
	array( 'Os animamos a evitar', 'Los animamos a evitar' ),

	// "os invitamos" -> "los invitamos" (Coffee and Chat).
	array( 'os invitamos a «Café y conversación»', 'los invitamos a «Café y conversación»' ),

	// "mientras tomáis" -> "mientras toman" (same sentence, verb has to follow).
	array( 'mientras tomáis un café', 'mientras toman un café' ),

	// "Os esperamos" -> "Los esperamos" (Coffee and Chat closing line).
	array( 'Os esperamos', 'Los esperamos' ),
);

$kz_ids = get_posts(
	array(
		'post_type'      => array( 'page', 'meetings', 'comparison_topic' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'post_status'    => 'publish',
		'lang'           => 'es',
	)
);

$kz_done  = 0;
$kz_posts = 0;
$kz_hits  = array();

foreach ( $kz_ids as $kz_id ) {
	if ( function_exists( 'pll_get_post_language' ) && pll_get_post_language( $kz_id ) !== 'es' ) {
		continue;
	}

	$kz_content = get_post_field( 'post_content', $kz_id );
	$kz_before  = $kz_content;

	foreach ( $kz_pairs as $kz_i => $kz_pair ) {
		list( $kz_old, $kz_new ) = $kz_pair;

		$kz_count = substr_count( $kz_content, $kz_old );

		if ( 0 === $kz_count ) {
			continue;
		}

		$kz_content       = str_replace( $kz_old, $kz_new, $kz_content );
		$kz_done         += $kz_count;
		$kz_hits[ $kz_i ] = ( $kz_hits[ $kz_i ] ?? 0 ) + $kz_count;

		WP_CLI::log( sprintf( '  #%-4d x%d  %s -> %s', $kz_id, $kz_count, $kz_old, $kz_new ) );
	}

	if ( $kz_content === $kz_before ) {
		continue;
	}

	++$kz_posts;

	if ( $kz_go ) {
		wp_update_post(
			array(
				'ID'           => $kz_id,
				'post_content' => $kz_content,
			)
		);
	}
}

$kz_already = 0;
$kz_absent  = array();

foreach ( $kz_pairs as $kz_i => $kz_pair ) {
	if ( isset( $kz_hits[ $kz_i ] ) ) {
		continue;
	}

	$kz_found_new = false;

	foreach ( $kz_ids as $kz_id ) {
		if ( false !== strpos( (string) get_post_field( 'post_content', $kz_id ), $kz_pair[1] ) ) {
			$kz_found_new = true;
			break;
		}
	}

	if ( $kz_found_new ) {
		++$kz_already;
		continue;
	}

	$kz_absent[] = mb_substr( $kz_pair[0], 0, 60 );
}

WP_CLI::log( sprintf( 'podmienionych: %d w %d wpisach, juz wczesniej: %d', $kz_done, $kz_posts, $kz_already ) );

if ( $kz_absent ) {
	WP_CLI::warning( sprintf( 'NIEZNALEZIONE (ani stare, ani nowe): %d', count( $kz_absent ) ) );
	foreach ( $kz_absent as $kz_a ) {
		WP_CLI::log( '   ' . $kz_a );
	}
}

if ( $kz_go ) {
	WP_CLI::success( 'Zapisane.' );
} else {
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
}
