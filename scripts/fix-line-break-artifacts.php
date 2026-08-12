<?php
/**
 * Repairs the line-break damage DeepL left in the translated lists.
 *
 * Run with:
 *   ddev wp eval-file scripts/fix-line-break-artifacts.php --path=/var/www/html          (dry run)
 *   ddev wp eval-file scripts/fix-line-break-artifacts.php --path=/var/www/html -- go    (writes)
 *
 * WHAT WENT WRONG. Several cells of the comparison table are lists written as one
 * paragraph with `<br>` between the items:
 *
 *     <p>Pismo Święte<br>Tradycja<br>Magisterium Kościoła<br>(Nauczycielski Urząd…)</p>
 *
 * The Polish carries no punctuation between the items — the break is the separator.
 * DeepL translates the HTML and is free to move the tags, because the target language
 * orders words differently, so it did two things:
 *
 * 1. It inserted a separator where it thought a sentence ended, AFTER the break:
 *    `New Testament: 27 books<br>; Old Testament: 39 books`. On screen the line ends
 *    with nothing and the next one opens with a stray semicolon — which is what the
 *    reader noticed and reported.
 * 2. Worse, it moved the break itself into the middle of a phrase:
 *    `Святе<br>Письмо, Традиція<br>` splits "Святе Письмо" (Holy Scripture) across
 *    two lines, and `La<br>Escritura, la Tradición<br>` does the same in Spanish.
 *    The list stopped being a list.
 *
 * MEASURED: 7 Polish cells contain `<br>`; 18 of their 21 translations were damaged.
 * The number of breaks always survived — only their positions and the punctuation
 * around them moved.
 *
 * TWO FIXES, because the two defects are not the same kind. Stripping punctuation
 * after a break is mechanical and safe. Putting a break back where it belongs is not
 * — the words have to be reordered — so those three cells are repaired by hand and
 * listed explicitly.
 *
 * Polish is untouched: it is the source and has none of this.
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
 * Hand repairs, as post id => column index => [old, new].
 *
 * The one cell where the break moved rather than the punctuation. Rebuilt to the
 * Polish four-line structure: Scripture / Tradition / Magisterium / (teaching office).
 *
 * The Spanish parenthetical is also corrected on the way: DeepL rendered
 * "Nauczycielski Urząd Kościoła" as "la Autoridad Doctoral", which back-translates to
 * "doctoral authority" — a different word entirely. The Church's teaching office is
 * "la autoridad docente".
 */
function kz_hand_repairs(): array {
	return array(
		// (kiedys wpis #447, kol. 1 — identyfikator juz nie identyfikuje)
		array(
			'<p>Scripture<br>, Tradition<br>, the Magisterium of the Church<br>(the Church’s Teaching Authority)</p>',
			'<p>Scripture<br>Tradition<br>the Magisterium of the Church<br>(the Church’s Teaching Authority)</p>',
		),
		// (kiedys wpis #611, kol. 1 — identyfikator juz nie identyfikuje)
		array(
			'<p>Святе<br>Письмо, Традиція<br>, Магістеріум Церкви<br>(Вчительська влада Церкви)</p>',
			'<p>Святе Письмо<br>Традиція<br>Магістеріум Церкви<br>(Вчительська влада Церкви)</p>',
		),
		// (kiedys wpis #663, kol. 1 — identyfikator juz nie identyfikuje)
		array(
			'<p>La<br>Escritura, la Tradición<br>y el Magisterio de la Iglesia<br>(la Autoridad Doctoral de la Iglesia)</p>',
			'<p>La Escritura<br>La Tradición<br>El Magisterio de la Iglesia<br>(la autoridad docente de la Iglesia)</p>',
		),

		/*
		 * "Pismo Święte", canon column. The break landed between the adjective and its
		 * noun — "+ 7 deuterocanonical / books" — so the third line ends mid-phrase and
		 * the fourth opens with a stray "books". The Polish puts the break after the
		 * whole phrase, before the parenthetical, and the Spanish already does; only
		 * English and Ukrainian need moving.
		 */
		// (kiedys wpis #444, kol. 1 — identyfikator juz nie identyfikuje)
		array(
			'<p>New Testament: 27 books<br>Old Testament: 39 books<br>+ 7 deuterocanonical<br>books (Alexandrian canon)</p>',
			'<p>New Testament: 27 books<br>Old Testament: 39 books<br>+ 7 deuterocanonical books<br>(Alexandrian canon)</p>',
		),
		// (kiedys wpis #608, kol. 1 — identyfikator juz nie identyfikuje)
		array(
			'<p>Новий Заповіт: 27 книг<br>Старий Заповіт: 39 книг<br>+ 7 девтероканонічних<br>книг (александрійський канон)</p>',
			'<p>Новий Заповіт: 27 книг<br>Старий Заповіт: 39 книг<br>+ 7 девтероканонічних книг<br>(александрійський канон)</p>',
		),

		/*
		 * "Małżeństwo", Roman Catholic column. The Polish is two independent nominal
		 * statements — "Związek mężczyzny i kobiety" then "Sakrament nierozerwalny." —
		 * and every translation welded them into one sentence broken across the two
		 * lines, so the second line opens with a lowercase verb ("is an indissoluble
		 * sacrament"). Each line becomes a standalone phrase again.
		 *
		 * The Ukrainian also had a gender error: "таїнство" is neuter, so the adjective
		 * is "нерозривне", not "нерозривний". DeepL agreed it with the wrong noun.
		 */
		// (kiedys wpis #470, kol. 1 — identyfikator juz nie identyfikuje)
		array(
			'<p>The union of a man and a woman<br>is an indissoluble sacrament. There is a legal procedure for declaring a marriage to be invalid, that is, a declaration of the nullity of the sacrament of marriage.</p>',
			'<p>The union of a man and a woman<br>An indissoluble sacrament. There is a legal procedure for declaring a marriage to be invalid, that is, a declaration of the nullity of the sacrament of marriage.</p>',
		),
		// (kiedys wpis #597, kol. 1 — identyfikator juz nie identyfikuje)
		array(
			'<p>Союз чоловіка та жінки<br>— нерозривний таїнство. Існує юридичний шлях до визнання шлюбу недійсним, тобто винесення рішення про недійсність укладення таїнства шлюбу</p>',
			'<p>Союз чоловіка та жінки<br>Нерозривне таїнство. Існує юридичний шлях до визнання шлюбу недійсним, тобто винесення рішення про недійсність укладення таїнства шлюбу</p>',
		),
		// (kiedys wpis #649, kol. 1 — identyfikator juz nie identyfikuje)
		array(
			'<p>La unión entre un hombre y una mujer<br>es un sacramento indisoluble. Existe una vía jurídica para declarar nulo un matrimonio, es decir, la sentencia de nulidad del sacramento del matrimonio.</p>',
			'<p>La unión entre un hombre y una mujer<br>Sacramento indisoluble. Existe una vía jurídica para declarar nulo un matrimonio, es decir, la sentencia de nulidad del sacramento del matrimonio.</p>',
		),
	);
}

$kz_stripped = 0;
$kz_hand     = 0;
$kz_posts    = 0;
$kz_missing  = array();

// --- Pass 1: punctuation immediately after a line break ---------------------------

foreach ( array( 'en', 'uk', 'es' ) as $kz_lang ) {
	$kz_ids = get_posts(
		array(
			'post_type'      => array( 'page', 'meetings', 'comparison_topic' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post_status'    => 'publish',
			'lang'           => $kz_lang,
		)
	);

	foreach ( $kz_ids as $kz_id ) {
		if ( function_exists( 'pll_get_post_language' ) && pll_get_post_language( $kz_id ) !== $kz_lang ) {
			continue;
		}

		$kz_touched = false;

		// Post content.
		$kz_content = (string) get_post_field( 'post_content', $kz_id );
		$kz_fixed   = preg_replace( '/(<br\s*\/?>)\s*[;:.,!?]\s*/i', '$1', $kz_content );

		if ( null !== $kz_fixed && $kz_fixed !== $kz_content ) {
			$kz_stripped += preg_match_all( '/(<br\s*\/?>)\s*[;:.,!?]/i', $kz_content );
			$kz_touched   = true;

			if ( $kz_go ) {
				wp_update_post(
					array(
						'ID'           => $kz_id,
						'post_content' => $kz_fixed,
					)
				);
			}

			WP_CLI::log( sprintf( '  [%s] #%-4d tresc — usuniete znaki po <br>', $kz_lang, $kz_id ) );
		}

		// Comparison table columns.
		$kz_churches = get_post_meta( $kz_id, 'churches', true );

		if ( is_array( $kz_churches ) ) {
			$kz_changed_meta = false;

			foreach ( $kz_churches as $kz_i => $kz_church ) {
				$kz_desc = (string) ( $kz_church['description'] ?? '' );
				$kz_new  = preg_replace( '/(<br\s*\/?>)\s*[;:.,!?]\s*/i', '$1', $kz_desc );

				if ( null === $kz_new || $kz_new === $kz_desc ) {
					continue;
				}

				$kz_stripped                              += preg_match_all( '/(<br\s*\/?>)\s*[;:.,!?]/i', $kz_desc );
				$kz_churches[ $kz_i ]['description']       = $kz_new;
				$kz_changed_meta                           = true;

				WP_CLI::log( sprintf( '  [%s] #%-4d kol%d — usuniete znaki po <br>', $kz_lang, $kz_id, $kz_i + 1 ) );
			}

			if ( $kz_changed_meta ) {
				$kz_touched = true;

				if ( $kz_go ) {
					update_post_meta( $kz_id, 'churches', $kz_churches );
				}
			}
		}

		if ( $kz_touched ) {
			++$kz_posts;
		}
	}
}

// --- Pass 2: the cells where the break itself moved -------------------------------

/*
 * The cells are found by their CONTENT, not by post id.
 *
 * They used to be keyed by id, and the dress rehearsal on a copy of the production
 * database showed what that is worth: reproducing the site gives the translations new ids,
 * so six of the eight keys pointed at a nav menu item, an unrelated page, a revision and a
 * post that no longer exists. The script then reported eight "mismatches" that were
 * nothing of the kind, and silently repaired nothing.
 *
 * Each `old` string is a whole column of a comparison table, four lines of it — unique
 * enough to search for. The ids stay in the comments above as a record of where these
 * cells were when the strings were collected.
 */
$kz_topics = get_posts(
	array(
		'post_type'        => array( 'comparison_topic', 'page', 'meetings' ),
		'post_status'      => 'publish',
		'posts_per_page'   => -1,
		'fields'           => 'ids',
		'suppress_filters' => true,
	)
);

foreach ( kz_hand_repairs() as $kz_pair ) {
	list( $kz_old, $kz_new ) = $kz_pair;

	/*
	 * Pass 1 has already stripped the punctuation, so the string on disk no longer
	 * matches `old` exactly. Both forms are accepted: the original and the post-strip one.
	 */
	$kz_after_pass1 = (string) preg_replace( '/(<br\s*\/?>)\s*[;:.,!?]\s*/i', '$1', $kz_old );
	$kz_found       = false;

	foreach ( $kz_topics as $kz_id ) {
		$kz_churches = get_post_meta( $kz_id, 'churches', true );

		if ( ! is_array( $kz_churches ) ) {
			continue;
		}

		$kz_post_changed = false;

		foreach ( $kz_churches as $kz_i => $kz_church ) {
			$kz_current = (string) ( $kz_church['description'] ?? '' );

			if ( $kz_current === $kz_new ) {
				WP_CLI::log( sprintf( '  #%-4d kol%d juz naprawiony recznie', $kz_id, $kz_i + 1 ) );
				$kz_found = true;
				continue;
			}

			if ( $kz_current !== $kz_old && $kz_current !== $kz_after_pass1 ) {
				continue;
			}

			$kz_churches[ $kz_i ]['description'] = $kz_new;
			$kz_post_changed                     = true;
			$kz_found                            = true;
			++$kz_hand;

			WP_CLI::log( sprintf( "  #%-4d kol%d naprawa reczna\n     - %s\n     + %s", $kz_id, $kz_i + 1, $kz_current, $kz_new ) );
		}

		// Per post, not on the running total — otherwise every later post is rewritten
		// with its unchanged value merely because an earlier one changed.
		if ( $kz_go && $kz_post_changed ) {
			update_post_meta( $kz_id, 'churches', $kz_churches );
		}
	}

	if ( ! $kz_found ) {
		$kz_missing[] = sprintf( "nie znaleziono zadnej kolumny o tresci:\n     %s", mb_substr( $kz_old, 0, 90 ) );
	}
}

if ( $kz_go ) {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cor_accordion_%' OR option_name LIKE '_transient_timeout_cor_accordion_%'" );
}

WP_CLI::log( sprintf( 'usunietych znakow po <br>: %d w %d wpisach; napraw recznych: %d', $kz_stripped, $kz_posts, $kz_hand ) );

if ( $kz_missing ) {
	WP_CLI::warning( sprintf( 'NIEZGODNOSCI: %d', count( $kz_missing ) ) );
	foreach ( $kz_missing as $kz_m ) {
		WP_CLI::log( '   ' . $kz_m );
	}
}

if ( $kz_go ) {
	WP_CLI::success( 'Zapisane, cache akordeonu wyczyszczony.' );
} else {
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
}
