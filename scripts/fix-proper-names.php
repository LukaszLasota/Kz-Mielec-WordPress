<?php
/**
 * Corrects proper names that a machine translator had no way of getting right.
 *
 * Run with:
 *   ddev wp eval-file scripts/fix-proper-names.php --path=/var/www/html          (dry run)
 *   ddev wp eval-file scripts/fix-proper-names.php --path=/var/www/html -- go    (writes)
 *
 * Found by reviewing the eighteen pages and their translations name by name. English
 * and Spanish came out clean: they keep `Mielec` (19 occurrences each), `Przemysłowa`,
 * `Reja`, `Hapoń`, `Brandys` and `Mocha` in the original Latin, and translate only what
 * genuinely has an exonym — Warsaw / Varsovia — consistently, 14 times each. Ukrainian
 * needed corrections in two families, 27 places in total.
 *
 * 1. ALL THREE POSTAL ADDRESSES WERE RENDERED IN CYRILLIC, WHICH MAKES THEM UNUSABLE.
 *    The congregation's own address, the registered office on ul. Reja, and the
 *    denomination's national headquarters in Warsaw on ul. Sienna — which came out as
 *    "вул. Сьєна", not even a faithful transliteration.
 *    `вул. Промислова, 2, 39-300 Мілець` — the street name was translated literally
 *    ("Промислова" is what "Przemysłowa" MEANS) and the town transliterated. Neither
 *    string exists on a Polish street sign, in the postal system, or in any map
 *    service. A visitor from Ukraine reading that page and typing it into a phone
 *    finds nothing. The postal form has to survive translation.
 *
 *    "вул." stays Cyrillic — that is the word "street", not part of the name, and it
 *    tells the reader what they are looking at. Everywhere ELSE on the site the town
 *    is correctly Cyrillic in prose (Мелець / Мельці / Мельця, 14 occurrences), which
 *    is right: an address is not prose. Note also that this one place used the wrong
 *    stem — `Мілець` against `Мелець` everywhere else.
 *
 *    The structured data was already correct in all four languages
 *    (`streetAddress: "ul. Przemysłowa 2"`), so search engines and maps were never
 *    misled — only human readers of the Ukrainian page were.
 *
 * 2. A FOURTH UKRAINIAN NAME FOR THE SAME CHURCH, in 23 places.
 *    The content named the body «Церква Християн Віри Євангельської» — the name of a
 *    specific Ukrainian denomination, the same substitution already corrected in the
 *    comparison table and the SEO titles, and it survived there in four grammatical
 *    cases. Unified on «Пентекостальна церква», the name the site gives itself.
 *
 * 3. THE POLISH LEGAL GAZETTE WAS RENAMED AFTER THE READER'S OWN COUNTRY.
 *    "Dziennik Ustaw" — where every Polish act is published — came out as
 *    «Boletín Oficial» in Spanish, which is the name of SPAIN's gazette (the BOE), and
 *    in Ukrainian as «Збірник законів» five times but «Офіційний вісник» once, which is
 *    the name of UKRAINE's. A reader following the citation would look in the wrong
 *    country's publication. Legal citations of a foreign act keep the original name, so
 *    all of them become "Dziennik Ustaw".
 *
 *    Spanish also rendered "poz." (pozycja — the item number within an issue) as
 *    "art.", an article. The law reproduced on that same page has real articles
 *    ("Art. 1."), so the two are not interchangeable. Corrected to "pos.", and only
 *    inside the gazette citations.
 *
 * A FOURTH CORRECTION, TO MY OWN WORK, is in `scripts/data/meta-descriptions.php`
 * rather than here: eight Ukrainian descriptions called the body «Верховна Рада»,
 * which is the name of Ukraine's parliament. A church council under that name reads
 * as an organ of the state. The content's own «Головна Рада» is the better choice and
 * the descriptions now follow it.
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
 * Ukrainian replacements, applied to post content and to comparison columns.
 *
 * Ordered longest first: the Council phrase has to be rewritten before any shorter
 * fragment of it could match.
 *
 * @var array<int, array{0: string, 1: string}>
 */
$kz_pairs = array(
	/*
	 * The denomination name, in every case the Ukrainian text inflects it into. The
	 * head noun carries the case, so matching "Церкв*" and replacing the whole phrase
	 * keeps the grammar of the sentence around it intact.
	 */
	array( 'Церкви Християн Віри Євангельської', 'Пентекостальної церкви' ),
	array( 'Церкві Християн Віри Євангельської', 'Пентекостальній церкві' ),
	array( 'Церква Християн Віри Євангельської', 'Пентекостальна церква' ),
	array( 'Церквою Християн Віри Євангельської', 'Пентекостальною церквою' ),
	array( 'Церкву Християн Віри Євангельської', 'Пентекостальну церкву' ),
	array( 'Церквами Християн Віри Євангельської', 'Пентекостальними церквами' ),

	/*
	 * Three postal addresses. All of them had the street name rendered in Cyrillic —
	 * translated in one case ("Промислова" is what "Przemysłowa" means) and
	 * transliterated in the other two, and "Сьєна" is not even a faithful
	 * transliteration of "Sienna". None of the three exists on a street sign, in the
	 * postal system or in a map service.
	 *
	 * "вул." stays Cyrillic: that is the word "street", not part of the name.
	 */
	array( 'вул. Промислова, 2, 39-300 Мілець', 'вул. Przemysłowa 2, 39-300 Mielec' ),
	array( '39-300 Мелець, вул. Рея, 1', '39-300 Mielec, вул. Reja 1' ),
	array( 'вул. Рея, 1', 'вул. Reja 1' ),
	array( 'вул. Сьєна, 68/70', 'вул. Sienna 68/70' ),

	// The Polish legal gazette keeps its own name — see point 3 above.
	array( 'Збірник законів', 'Dziennik Ustaw' ),
	array( 'Офіційний вісник', 'Dziennik Ustaw' ),
);

/**
 * Spanish replacements. Same reasoning, different language.
 *
 * @var array<int, array{0: string, 1: string}>
 */
$kz_pairs_es = array(
	array( 'Boletín Oficial', 'Dziennik Ustaw' ),

	/*
	 * "poz." is the item number inside an issue of the gazette; Spanish rendered it as
	 * "art.", an article. Each citation is listed in full rather than replacing "art."
	 * globally, because the act reproduced on the same page has real articles.
	 *
	 * "n.º" stays where it renders Polish "nr" — the issue number — and becomes "pos."
	 * only in the two citations from 2016 and 2017, where Dziennik Ustaw no longer uses
	 * issue numbers and the figure is the item.
	 */
	array( 'n.º 128, art. 832', 'n.º 128, pos. 832' ),
	array( 'n.º 41, art. 254', 'n.º 41, pos. 254' ),
	array( 'n.º 36, art. 155', 'n.º 36, pos. 155' ),
	array( 'n.º 95, art. 425, y de 1992, n.º 26, art. 113', 'n.º 95, pos. 425, y de 1992, n.º 26, pos. 113' ),
	array( 'n.º 159, art. 1546', 'n.º 159, pos. 1546' ),
	array( 'n.º 90, art. 557', 'n.º 90, pos. 557' ),
	array( 'Dziennik Ustaw 2017, n.º 1147', 'Dziennik Ustaw 2017, pos. 1147' ),
	array( 'núm. 1943, 1954, 1985 y 2169, y de 2017, núms. 60 y 949', 'pos. 1943, 1954, 1985 y 2169, y de 2017, pos. 60 y 949' ),
);

$kz_done  = 0;
$kz_posts = 0;
$kz_hits  = array();

$kz_by_lang = array( 'uk' => $kz_pairs, 'es' => $kz_pairs_es );

foreach ( $kz_by_lang as $kz_lang => $kz_pairs ) {

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
	$kz_new     = $kz_content;

	foreach ( $kz_pairs as $kz_i => $kz_pair ) {
		$kz_count = substr_count( $kz_new, $kz_pair[0] );

		if ( 0 === $kz_count ) {
			continue;
		}

		$kz_new           = str_replace( $kz_pair[0], $kz_pair[1], $kz_new );
		$kz_done         += $kz_count;
		$kz_hits[ $kz_i ] = ( $kz_hits[ $kz_i ] ?? 0 ) + $kz_count;

		WP_CLI::log( sprintf( '  #%-4d tresc x%d: %s', $kz_id, $kz_count, mb_substr( $kz_pair[0], 0, 52 ) ) );
	}

	if ( $kz_new !== $kz_content ) {
		$kz_touched = true;

		if ( $kz_go ) {
			wp_update_post(
				array(
					'ID'           => $kz_id,
					'post_content' => $kz_new,
				)
			);
		}
	}

	// Comparison columns.
	$kz_churches = get_post_meta( $kz_id, 'churches', true );

	if ( is_array( $kz_churches ) ) {
		$kz_meta_changed = false;

		foreach ( $kz_churches as $kz_k => $kz_church ) {
			$kz_desc = (string) ( $kz_church['description'] ?? '' );
			$kz_fix  = $kz_desc;

			foreach ( $kz_pairs as $kz_i => $kz_pair ) {
				$kz_count = substr_count( $kz_fix, $kz_pair[0] );

				if ( 0 === $kz_count ) {
					continue;
				}

				$kz_fix           = str_replace( $kz_pair[0], $kz_pair[1], $kz_fix );
				$kz_done         += $kz_count;
				$kz_hits[ $kz_i ] = ( $kz_hits[ $kz_i ] ?? 0 ) + $kz_count;

				WP_CLI::log( sprintf( '  #%-4d kol%d x%d: %s', $kz_id, $kz_k + 1, $kz_count, mb_substr( $kz_pair[0], 0, 52 ) ) );
			}

			if ( $kz_fix !== $kz_desc ) {
				$kz_churches[ $kz_k ]['description'] = $kz_fix;
				$kz_meta_changed                     = true;
			}
		}

		if ( $kz_meta_changed ) {
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

if ( $kz_go && $kz_done ) {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cor_accordion_%' OR option_name LIKE '_transient_timeout_cor_accordion_%'" );
}

WP_CLI::log( sprintf( 'podmienionych: %d w %d wpisach', $kz_done, $kz_posts ) );

// Intentionally not reporting per-pair misses any more: the two language tables share
// index numbers, so a miss in one is indistinguishable from a hit in the other.

if ( $kz_go ) {
	WP_CLI::success( 'Zapisane, cache akordeonu wyczyszczony.' );
} else {
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
}
