<?php
/**
 * Replaces the machine-paraphrased Scripture quotations with published translations.
 *
 * Run with:
 *   ddev wp eval-file scripts/substitute-bible-quotes.php --path=/var/www/html          (dry run)
 *   ddev wp eval-file scripts/substitute-bible-quotes.php --path=/var/www/html -- go    (writes)
 *
 * WHY. DeepL rendered every Scripture quotation by paraphrasing the Polish. The
 * result reads fine and means the right thing, but a reader cannot recognise the
 * verse or look it up, which is the one thing a Scripture quotation has to allow.
 * The texts come from `scripts/data/bible-quotes.php`, each fetched verbatim from
 * the published translation — see that file for the four translations chosen and for
 * the attribution the site now owes.
 *
 * APPLIED PER LANGUAGE, NOT PER POST — and that correction matters. The first version
 * of this script keyed its table by source post, which quietly missed a second copy of
 * every quotation: the "prawo / law" page is an accordion that REPRODUCES the full text
 * of all the Council statements, so each quotation exists twice on the site. The
 * substitution reported "46 replacements, every pattern matched exactly once" and was
 * still incomplete, because it never looked at that page. Applying each pair to every
 * post of its language fixes both copies and any future one — the strings are 100-250
 * characters of verbatim Scripture, so a match cannot be a coincidence.
 *
 * WHY AN EXPLICIT TABLE rather than a pattern. Two reasons, both learned the hard
 * way in this project. A pattern that matched "quotation followed by a reference"
 * would have to guess where a quotation ends, and English gets that wrong on its
 * own: `‘We are therefore Christ’s ambassadors …’` closes, to any parser and to any
 * reader, at the apostrophe in "Christ’s". And a table of exact strings fails
 * loudly — a dry run reports every entry that did not match — where a pattern fails
 * silently by rewriting the wrong span.
 *
 * The same pass switches English Scripture quotations from `‘…’` to `“…”`. That is
 * not cosmetic: with single quotes, the apostrophe in "Christ’s" is typographically
 * identical to the closing mark, so the reader genuinely cannot see where the
 * quotation ends. Ukrainian and Spanish already use `«…»` and Polish `„…”`.
 *
 * WHAT IS DELIBERATELY LEFT ALONE:
 *   - Polish inside the Supreme Church Council's statements. Those are verbatim
 *     reproductions of another body's official documents and they quote Biblia
 *     Warszawska. Only the congregation's own pages (wizja, misja) change.
 *   - The Ukrainian phrases «навчання учнів» and «доростати до повноти Христа», and
 *     the Spanish «hacer discípulos». They are short nominalised fragments woven
 *     into a Polish-derived sentence; forcing the published wording into them would
 *     break the grammar for no gain, since nobody looks up a three-word phrase.
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
 * Every substitution, as source post id => language => list of [old, new].
 *
 * `old` is the exact string now in the database, taken from a dump of the content
 * rather than typed from the rendered page. `new` carries its own quote marks so
 * the mark change and the text change happen together.
 */
function kz_quote_substitutions(): array {
	/*
	 * Czytane z pliku danych, wspolnego z testem `kzt-bible-quotes.php`. Test sprawdza
	 * obecnosc wstawionych tekstow, a nie potrafil tego robic, dopoki zestaw byl zamkniety
	 * w tym pliku.
	 */
	$data = ABSPATH . 'scripts/data/bible-substitutions.php';

	if ( ! file_exists( $data ) ) {
		WP_CLI::error( 'Brak scripts/data/bible-substitutions.php' );
	}

	$pairs = include $data;

	return is_array( $pairs ) ? $pairs : array();
}

$kz_done     = 0;
$kz_posts    = 0;
$kz_already  = 0;
$kz_notfound = array();

/*
 * Flattened: language => list of [old, new]. The post ids in the table above stay as
 * documentation of where each quotation came from, but the substitution itself no
 * longer depends on them.
 */
$kz_pairs = array();

foreach ( kz_quote_substitutions() as $kz_langs ) {
	foreach ( $kz_langs as $kz_lang => $kz_list ) {
		foreach ( $kz_list as $kz_pair ) {
			$kz_pairs[ $kz_lang ][] = $kz_pair;
		}
	}
}

foreach ( $kz_pairs as $kz_lang => $kz_list ) {
	$kz_ids = get_posts(
		array(
			'post_type'      => array( 'page', 'meetings', 'comparison_topic' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post_status'    => 'publish',
			'lang'           => $kz_lang,
		)
	);

	$kz_hits = array();

	foreach ( $kz_ids as $kz_id ) {
		if ( function_exists( 'pll_get_post_language' ) && pll_get_post_language( $kz_id ) !== $kz_lang ) {
			continue;
		}

		$kz_content = get_post_field( 'post_content', $kz_id );
		$kz_before  = $kz_content;

		foreach ( $kz_list as $kz_i => $kz_pair ) {
			list( $kz_old, $kz_new ) = $kz_pair;

			$kz_count = substr_count( $kz_content, $kz_old );

			if ( 0 === $kz_count ) {
				continue;
			}

			$kz_content              = str_replace( $kz_old, $kz_new, $kz_content );
			$kz_done                += $kz_count;
			$kz_hits[ $kz_i ]        = ( $kz_hits[ $kz_i ] ?? 0 ) + $kz_count;

			WP_CLI::log( sprintf( '  [%s] #%-4d x%d  %s', $kz_lang, $kz_id, $kz_count, mb_substr( $kz_old, 0, 58 ) ) );
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

	/*
	 * A pair with no hit is either already substituted by an earlier run or genuinely
	 * absent. The two are told apart by looking for the replacement text.
	 */
	foreach ( $kz_list as $kz_i => $kz_pair ) {
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

		$kz_notfound[] = sprintf( '[%s] nie znaleziono ani starego, ani nowego: %s', $kz_lang, mb_substr( $kz_pair[0], 0, 60 ) );
	}
}

WP_CLI::log( sprintf( 'juz podmienionych wczesniej: %d', $kz_already ) );

/*
 * Oznaczenie wpisow, w ktorych stoi tekst chronionego przekladu.
 *
 * NIV i NVI naleza do Biblica, ukrainski UTT do Ukrainskiego Towarzystwa Biblijnego —
 * kazdy z nich wymaga noty o zrodle tam, gdzie cytuje sie jego tekst. Nota stala kiedys
 * w glownej stopce, czyli na kazdej podstronie, takze tam, gdzie zadnego cytatu nie ma.
 * Teraz motyw doklada ja tylko do oznaczonych wpisow (`Kzmielec\Seo\ScriptureNotice`).
 *
 * Znacznik ustawia sie tutaj, a nie recznie w panelu, bo inaczej po odtworzeniu tresci na
 * produkcji cytaty byłyby, a noty nie — i nikt by tego nie zauwazyl. Sprawdzana jest
 * OBECNOSC tekstu docelowego, wiec przebieg jest idempotentny i dziala takze wtedy, gdy
 * podmiana nastapila w poprzednim uruchomieniu.
 */
$kz_marked   = 0;
$kz_unmarked = 0;

foreach ( $kz_pairs as $kz_lang => $kz_list ) {
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
		$kz_content = (string) get_post_field( 'post_content', $kz_id );
		$kz_has     = false;

		foreach ( $kz_list as $kz_pair ) {
			if ( '' !== $kz_pair[1] && false !== strpos( $kz_content, $kz_pair[1] ) ) {
				$kz_has = true;
				break;
			}
		}

		/*
		 * Odsylacz biblijny w nawiasie liczy sie tak samo jak podmieniony tekst.
		 *
		 * Bez tego osiem polskich stron z oswiadczeniami Naczelnej Rady zostawaloby bez
		 * noty: po polsku podmieniono cytaty tylko na wlasnych stronach zboru (EIB),
		 * a oswiadczenia przytaczaja Biblie Warszawska — rowniez chroniona, Towarzystwo
		 * Biblijne w Polsce. Znaczek wypada wiec po stronie ostrozniejszej: strona, ktora
		 * odsyla do wersetu, dostaje note o zrodlach.
		 */
		/*
		 * Wielka litera lacinska ALBO cyrylicka: ukrainskie skroty ksiag to «Бут.»,
		 * «1 Кор.», «Мт.». Pierwsza wersja tego wzorca dopuszczala tylko lacinske i przez
		 * to pominela trzy ukrainskie strony — 7 oznaczonych wobec 10 w pozostalych
		 * jezykach. Roznica w liczbach byla jedynym sygnalem, ze wzorzec jest za waski.
		 */
		if ( ! $kz_has && preg_match( '/\((?:por\.\s*)?[1-3]?\s?[A-ZŁŚŻА-ЯЄІЇҐ][\p{L}]{1,14}\.?\s+\d+[,:]\s?\d+/u', $kz_content ) ) {
			$kz_has = true;
		}

		$kz_had = '' !== (string) get_post_meta( $kz_id, '_kzt_scripture', true );

		if ( $kz_has === $kz_had ) {
			continue;
		}

		if ( $kz_has ) {
			++$kz_marked;

			if ( $kz_go ) {
				update_post_meta( $kz_id, '_kzt_scripture', '1' );
			}
		} else {
			++$kz_unmarked;

			if ( $kz_go ) {
				delete_post_meta( $kz_id, '_kzt_scripture' );
			}
		}
	}
}

WP_CLI::log( sprintf( 'znacznik noty o zrodlach: do oznaczenia %d, do zdjecia %d', $kz_marked, $kz_unmarked ) );

if ( $kz_notfound ) {
	WP_CLI::warning( sprintf( 'NIEZNALEZIONE: %d', count( $kz_notfound ) ) );
	foreach ( $kz_notfound as $kz_n ) {
		WP_CLI::log( '   ' . $kz_n );
	}
}

WP_CLI::log( sprintf( 'podmienionych cytatow: %d, wpisow: %d', $kz_done, $kz_posts ) );

if ( $kz_go ) {
	WP_CLI::success( 'Zapisane.' );
} else {
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
}
