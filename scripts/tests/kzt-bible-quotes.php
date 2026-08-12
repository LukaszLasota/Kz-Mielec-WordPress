<?php
/**
 * Czy cytaty Pisma stoja w tresci w uznanym przekladzie, a nie w parafrazie DeepL.
 *
 * Ten test istnieje z powodu, ktory ujawnila proba generalna na kopii bazy produkcyjnej:
 * `substitute-bible-quotes.php` dopasowuje DOSLOWNE wyjscie DeepL, a DeepL nie jest
 * deterministyczny. Przy odtworzeniu dwie z 52 podmian nie znalazly swojego tekstu, wiec
 * dwa cytaty zostaly parafraza. Na stronie nie widac tego wcale: akapit jest poprawny
 * jezykowo, brzmi sensownie i po prostu nie jest tym, co czytelnik znajdzie w swojej
 * Biblii.
 *
 * Sprawdzana jest strona DOCELOWA kazdej pary z `scripts/data/bible-substitutions.php` —
 * czyli dokladnie ten tekst, ktory skrypt wstawia. Pierwsza wersja tego testu zakladala
 * pelne wersety z `bible-quotes.php` i zglaszala 42 bledy na zdrowej bazie, bo szesc
 * pozycji to krotkie frazy cytowane w zdaniu, a nie cale wersety.
 */
$fails = array();
$data  = ABSPATH . 'scripts/data/bible-substitutions.php';

if ( ! file_exists( $data ) ) {
	echo "FAIL\n  - brak pliku $data\n";
	exit( 1 );
}

$table = include $data;

if ( ! is_array( $table ) || ! $table ) {
	echo "FAIL\n  - tablica podmian pusta\n";
	exit( 1 );
}

if ( ! function_exists( 'pll_languages_list' ) ) {
	echo "PASS: Polylang nieaktywny — obce cytaty nie maja gdzie stac\n";
	exit( 0 );
}

global $wpdb;

$checked = 0;
$present = 0;

foreach ( $table as $group ) {
	if ( ! is_array( $group ) ) {
		continue;
	}

	foreach ( $group as $lang => $pairs ) {
		if ( 'pl' === $lang || ! is_array( $pairs ) ) {
			continue;
		}

		foreach ( $pairs as $pair ) {
			$wanted = is_array( $pair ) && isset( $pair[1] ) ? trim( (string) $pair[1] ) : '';

			if ( '' === $wanted ) {
				continue;
			}

			++$checked;

			/*
			 * Szukamy fragmentu, nie calosci: wstawiony tekst siedzi w akapicie, ktory
			 * niesie wlasna interpunkcje i znaczniki wokol. 50 znakow wystarcza, zeby
			 * odroznic przeklad od parafrazy, i nie wywraca sie na koncowce zdania.
			 */
			$needle = mb_substr( $wanted, 0, 50 );

			$hits = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE %s",
					'%' . $wpdb->esc_like( $needle ) . '%'
				)
			);

			// Kolumny tabeli porownania siedza w polu meta, nie w tresci wpisu.
			if ( 0 === $hits ) {
				$hits = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'churches' AND meta_value LIKE %s",
						'%' . $wpdb->esc_like( $needle ) . '%'
					)
				);
			}

			if ( 0 === $hits ) {
				$fails[] = sprintf( '[%s] brak wstawionego przekladu: %s…', $lang, mb_substr( $needle, 0, 54 ) );
				continue;
			}

			++$present;
		}
	}
}

if ( $fails ) {
	echo "FAIL\n";
	foreach ( $fails as $f ) {
		echo "  - $f\n";
	}
	printf( "  (obecnych %d z %d sprawdzanych)\n", $present, $checked );
	exit( 1 );
}

printf( "PASS: cytaty Pisma w uznanych przekladach, %d z %d obecnych w tresci\n", $present, $checked );
