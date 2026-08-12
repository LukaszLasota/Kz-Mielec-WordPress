<?php
/**
 * Tworzy menu nawigacyjne dla kazdego jezyka obcego i przypisuje je w Polylangu.
 *
 * Uruchamianie: ddev wp eval-file - < scripts/setup-language-menus.php
 * Na produkcji:  wp --path=<KZ> eval-file - < scripts/setup-language-menus.php
 *
 * Dlaczego to nie jest zwykle kopiowanie pozycji: menu glowne sklada sie
 * wylacznie z LINKOW WLASNYCH z kotwicami (`/#one` … `/#four`), nie z odnosnikow
 * do stron. Mapowanie `_menu_item_object_id` na tlumaczenie — pierwotny pomysl —
 * daloby menu puste, bo nie ma tu ani jednego odnosnika do posta.
 *
 * Etykiety brane sa z PRZETLUMACZONYCH NAGLOWKOW sekcji o tej samej kotwicy, nie
 * tlumaczone osobno. Dzieki temu pozycja menu mowi doslownie to samo, co sekcja,
 * do ktorej prowadzi — a nie zblizone synonimy, ktore DeepL wyprodukowalby przy
 * dwoch niezaleznych tlumaczeniach tego samego sensu.
 *
 * Kotwice (`one`…`four`) sa atrybutami `anchor`, ktorych celowo nie tlumaczymy,
 * wiec sa identyczne we wszystkich jezykach.
 *
 * @package KzmielecTranslate
 */

if ( ! function_exists( 'pll_get_post' ) ) {
	echo "  BLAD: Polylang nieaktywny\n";
	return;
}

$lokalizacja = 'primary';
$motyw       = (string) get_option( 'stylesheet' );
$jezyki      = array(
	'en' => 'EN',
	'uk' => 'UK',
	'es' => 'ES',
);

$zrodlo_menu = wp_get_nav_menu_object( (int) ( get_nav_menu_locations()[ $lokalizacja ] ?? 0 ) );

if ( ! $zrodlo_menu ) {
	echo "  BLAD: brak menu w lokalizacji $lokalizacja\n";
	return;
}

// Kolejnosc i kotwice odczytane z polskiego menu, zeby nie zaszyc ich na sztywno.
$pozycje = array();

foreach ( wp_get_nav_menu_items( $zrodlo_menu->term_id ) as $i ) {
	if ( 'custom' !== $i->type ) {
		printf( "  POMINIETO pozycje #%d \"%s\" — typ %s, nie link wlasny\n", $i->ID, $i->title, $i->type );
		continue;
	}

	$fragment = '';

	if ( preg_match( '/#(.+)$/', (string) $i->url, $m ) ) {
		$fragment = $m[1];
	}

	$pozycje[] = array(
		'anchor'    => $fragment,
		'pl_title'  => $i->title,
		'order'     => (int) $i->menu_order,
	);
}

printf( "  polskie menu \"%s\": %d pozycji, kotwice: %s\n\n", $zrodlo_menu->name, count( $pozycje ), implode( ', ', wp_list_pluck( $pozycje, 'anchor' ) ) );

$opcje = PLL()->options;
$nav   = (array) $opcje->get( 'nav_menus' );

foreach ( $jezyki as $lang => $etykieta ) {
	$front = (int) pll_get_post( (int) get_option( 'page_on_front' ), $lang );

	if ( ! $front ) {
		printf( "  %s: BRAK przetlumaczonej strony glownej — pomijam\n", $lang );
		continue;
	}

	$tresc = (string) get_post( $front )->post_content;
	$nazwa = $zrodlo_menu->name . ' (' . $etykieta . ')';
	$menu  = wp_get_nav_menu_object( $nazwa );
	$id    = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $nazwa );

	if ( is_wp_error( $id ) || ! $id ) {
		printf( "  %s: nie udalo sie utworzyc menu\n", $lang );
		continue;
	}

	pll_set_term_language( $id, $lang );

	// Czyscimy, zeby ponowne uruchomienie nie dublowalo pozycji.
	foreach ( (array) wp_get_nav_menu_items( $id ) as $stare ) {
		wp_delete_post( (int) $stare->ID, true );
	}

	$baza  = trailingslashit( (string) get_option( 'home' ) ) . $lang . '/';
	$dodane = 0;

	foreach ( $pozycje as $p ) {
		$tytul = $p['pl_title'];

		// Etykieta z przetlumaczonego naglowka o tej samej kotwicy.
		if ( '' !== $p['anchor'] && preg_match( '#<h[1-6][^>]*id="' . preg_quote( $p['anchor'], '#' ) . '"[^>]*>(.*?)</h[1-6]>#s', $tresc, $m ) ) {
			$z_naglowka = trim( wp_strip_all_tags( $m[1] ) );

			if ( '' !== $z_naglowka ) {
				$tytul = $z_naglowka;
			}
		}

		$wynik = wp_update_nav_menu_item(
			$id,
			0,
			array(
				'menu-item-title'   => $tytul,
				'menu-item-url'     => $baza . ( '' !== $p['anchor'] ? '#' . $p['anchor'] : '' ),
				'menu-item-type'    => 'custom',
				'menu-item-status'  => 'publish',
				'menu-item-position' => $p['order'],
			)
		);

		if ( ! is_wp_error( $wynik ) ) {
			++$dodane;
		}
	}

	$nav[ $motyw ][ $lokalizacja ][ $lang ] = $id;

	printf( "  %s: menu #%d \"%s\", pozycji %d\n", $lang, $id, $nazwa, $dodane );

	foreach ( (array) wp_get_nav_menu_items( $id ) as $i ) {
		printf( "       %-30s %s\n", mb_substr( $i->title, 0, 28 ), str_replace( get_option( 'home' ), '', (string) $i->url ) );
	}
}

// Polski zostaje na swoim menu.
$nav[ $motyw ][ $lokalizacja ]['pl'] = (int) $zrodlo_menu->term_id;

$opcje->set( 'nav_menus', $nav );
$opcje->save();
PLL()->model->clean_languages_cache();

printf( "\n  mapowanie zapisane: %s\n", wp_json_encode( $nav[ $motyw ][ $lokalizacja ] ) );
