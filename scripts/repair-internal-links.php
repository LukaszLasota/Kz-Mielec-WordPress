<?php
/**
 * Przekierowuje linki wewnetrzne w istniejacych tlumaczeniach na wlasny jezyk.
 *
 * Uruchamianie: ddev wp eval-file - < scripts/repair-internal-links.php
 *
 * Naprawia to bez ponownego tlumaczenia — operacja dotyczy wylacznie adresow,
 * ktore i tak nigdy nie byly tlumaczone, wiec nie kosztuje ani jednego znaku
 * limitu DeepL.
 *
 * Problem: adres nie jest proza, wiec slusznie nie idzie do tlumaczenia — ale
 * przez to zostaje wycelowany tam, gdzie celowal po polsku. Zmierzone na
 * ukrainskiej stronie „prawo": dziewiec przyciskow „Читати далі" prowadzilo na
 * polskie strony. Strona wygladala na przetlumaczona, tylko wyjscia z niej
 * wyprowadzaly z jezyka.
 *
 * Pliki w `/wp-content/` zostaja nietkniete celowo: PDF-y na stronie „prawo" to
 * polskie ustawy i prawo wewnetrzne Kosciola, ktorych obcych wersji nie ma.
 *
 * @package KzmielecTranslate
 */

if ( ! function_exists( 'pll_get_post' ) ) {
	echo "  BLAD: Polylang nieaktywny\n";
	return;
}

$typy   = array( 'page', 'meetings', 'comparison_topic' );
$jezyki = array_keys( \KzmielecTranslate\Translators\PostTranslator::DEEPL_LANG );

$suma = array(
	'zmienionych_wpisow' => 0,
	'przemapowanych'     => 0,
	'pominietych'        => 0,
);

foreach ( $jezyki as $lang ) {
	$w_jezyku = array(
		'wpisy'         => 0,
		'przemapowanych' => 0,
	);

	foreach ( get_posts(
		array(
			'post_type'   => $typy,
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => -1,
			'lang'        => $lang,
		)
	) as $p ) {
		$wynik = \KzmielecTranslate\Services\LinkRemapper::remap( (string) $p->post_content, $lang );

		$suma['pominietych'] += $wynik['skipped'];

		if ( $wynik['remapped'] < 1 || $wynik['content'] === $p->post_content ) {
			continue;
		}

		wp_update_post(
			array(
				'ID'           => $p->ID,
				'post_content' => $wynik['content'],
			)
		);

		++$w_jezyku['wpisy'];
		$w_jezyku['przemapowanych'] += $wynik['remapped'];
	}

	$suma['zmienionych_wpisow'] += $w_jezyku['wpisy'];
	$suma['przemapowanych']     += $w_jezyku['przemapowanych'];

	printf( "  %-4s wpisow zmienionych=%-3d linkow przemapowanych=%d\n", $lang, $w_jezyku['wpisy'], $w_jezyku['przemapowanych'] );
}

printf(
	"\n  razem: wpisow %d, linkow %d, pominietych (plik lub brak tlumaczenia) %d\n",
	$suma['zmienionych_wpisow'],
	$suma['przemapowanych'],
	$suma['pominietych']
);

// ── Kontrola: ile polskich adresow stron zostalo w tresci tlumaczen ───────
echo "\n  === kontrola: polskie adresy stron pozostale w tresci ===\n";

foreach ( $jezyki as $lang ) {
	$zostalo = 0;

	foreach ( get_posts(
		array(
			'post_type'   => $typy,
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => -1,
			'lang'        => $lang,
		)
	) as $p ) {
		if ( ! preg_match_all( '#href="(/[^"]*)"#', (string) $p->post_content, $m ) ) {
			continue;
		}

		foreach ( $m[1] as $u ) {
			if ( 0 === strpos( $u, '/wp-content' ) || 0 === strpos( $u, '#' ) ) {
				continue;
			}
			if ( preg_match( '#^/(en|uk|es)/#', $u ) ) {
				continue;
			}
			++$zostalo;
		}
	}

	printf( "  %-4s %d\n", $lang, $zostalo );
}
