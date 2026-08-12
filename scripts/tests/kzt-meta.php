<?php
/**
 * Pola meta: zserializowana tablica churches, pola spotkan, Yoast.
 * Najwazniejsza asercja: identyfikatory i liczby przechodza BEZ ZMIAN.
 */
$fails = array();
$ct_c  = '\KzmielecTranslate\Translators\ChurchesTranslator';
$mt_c  = '\KzmielecTranslate\Translators\MeetingMetaTranslator';
$yt_c  = '\KzmielecTranslate\Translators\YoastTranslator';

foreach ( array( $ct_c, $mt_c, $yt_c ) as $k ) {
	if ( ! class_exists( $k ) ) {
		$fails[] = "brak klasy $k";
	}
}
if ( $fails ) {
	echo "FAIL\n";
	foreach ( $fails as $f ) {
		echo "  - $f\n";
	}
	exit( 1 );
}

$stub = new \KzmielecTranslate\Services\StubTranslator();

// ── churches ──────────────────────────────────────────────────────────────
$src = wp_insert_post( array( 'post_type' => 'comparison_topic', 'post_status' => 'publish', 'post_title' => 'Bóg' ) );
$dst = wp_insert_post( array( 'post_type' => 'comparison_topic', 'post_status' => 'publish', 'post_title' => '[EN-GB] Bóg' ) );

update_post_meta(
	$src,
	'churches',
	array(
		array(
			'church_name' => 'Kościół Rzymskokatolicki',
			'description' => '<p>Trójca Święta.</p>',
		),
		array(
			'church_name' => 'Kościół Zielonoświątkowy',
			'description' => '<p>Bóg Trójjedyny.</p>',
		),
	)
);
update_post_meta( $src, 'sort_order', 7 );

$r_ch = ( new $ct_c( $stub ) )->translate( (int) $src, (int) $dst, 'EN-GB', true );
$got  = get_post_meta( $dst, 'churches', true );

if ( ! is_array( $got ) || 2 !== count( $got ) ) {
	$fails[] = 'churches: oczekiwano 2 wpisow, jest ' . ( is_array( $got ) ? count( $got ) : 'nie-tablica' );
} else {
	if ( false === strpos( (string) $got[0]['description'], '[EN-GB]' ) ) {
		$fails[] = 'churches: opis nieprzetlumaczony';
	}
	if ( false === strpos( (string) $got[0]['description'], '<p>' ) ) {
		$fails[] = 'churches: zgubiony znacznik HTML w opisie';
	}
	if ( '' === trim( (string) $got[0]['church_name'] ) ) {
		$fails[] = 'churches: pusta nazwa wyznania';
	}
	if ( ! isset( $got[1]['description'] ) || false === strpos( (string) $got[1]['description'], '[EN-GB]' ) ) {
		$fails[] = 'churches: drugi wpis nieprzetlumaczony';
	}
}
if ( '7' !== (string) get_post_meta( $dst, 'sort_order', true ) ) {
	$fails[] = 'sort_order nie zostal skopiowany bez zmian';
}
if ( $r_ch['segments'] < 4 ) {
	$fails[] = 'churches: zgloszono ' . $r_ch['segments'] . ' segmentow, oczekiwano 4';
}

// Tryb raportowania nie zapisuje.
$dst2 = wp_insert_post( array( 'post_type' => 'comparison_topic', 'post_status' => 'publish', 'post_title' => 'x' ) );
( new $ct_c( $stub ) )->translate( (int) $src, (int) $dst2, 'EN-GB', false );
if ( '' !== (string) get_post_meta( $dst2, 'churches', true ) && array() !== (array) get_post_meta( $dst2, 'churches', true ) ) {
	$fails[] = 'churches: tryb raportowania zapisal dane';
}
wp_delete_post( (int) $dst2, true );

// ── meeting meta ──────────────────────────────────────────────────────────
$msrc = wp_insert_post( array( 'post_type' => 'meetings', 'post_status' => 'publish', 'post_title' => 'Nabożeństwo' ) );
$mdst = wp_insert_post( array( 'post_type' => 'meetings', 'post_status' => 'publish', 'post_title' => '[EN-GB] Nabożeństwo' ) );

update_post_meta( $msrc, '_meeting_day_hour', 'Niedziela 10:30' );
update_post_meta( $msrc, '_meeting_place', 'ul. Przemysłowa 2' );
update_post_meta( $msrc, '_meeting_hover_image', 208 );
update_post_meta( $msrc, '_meeting_anchor', 10 );

( new $mt_c( $stub ) )->translate( (int) $msrc, (int) $mdst, 'EN-GB', true );

if ( false === strpos( (string) get_post_meta( $mdst, '_meeting_day_hour', true ), '[EN-GB]' ) ) {
	$fails[] = '_meeting_day_hour nieprzetlumaczony';
}
if ( false === strpos( (string) get_post_meta( $mdst, '_meeting_place', true ), '[EN-GB]' ) ) {
	$fails[] = '_meeting_place nieprzetlumaczone';
}
if ( '208' !== (string) get_post_meta( $mdst, '_meeting_hover_image', true ) ) {
	$fails[] = '_meeting_hover_image ZMIENIONE — ID obrazka musi przejsc bez zmian';
}
if ( '10' !== (string) get_post_meta( $mdst, '_meeting_anchor', true ) ) {
	$fails[] = '_meeting_anchor ZMIENIONE — kotwica musi przejsc bez zmian';
}

// ── Yoast ─────────────────────────────────────────────────────────────────
update_post_meta( $src, '_yoast_wpseo_title', 'Bóg — kim jest' );
update_post_meta( $src, '_yoast_wpseo_metadesc', 'Czym różni się nauka o Bogu.' );
update_post_meta( $src, '_yoast_wpseo_content_score', 60 );

( new $yt_c( $stub ) )->translate( (int) $src, (int) $dst, 'EN-GB', true );

if ( false === strpos( (string) get_post_meta( $dst, '_yoast_wpseo_title', true ), '[EN-GB]' ) ) {
	$fails[] = 'tytul SEO nieprzetlumaczony';
}
if ( false === strpos( (string) get_post_meta( $dst, '_yoast_wpseo_metadesc', true ), '[EN-GB]' ) ) {
	$fails[] = 'opis SEO nieprzetlumaczony';
}
if ( '' !== (string) get_post_meta( $dst, '_yoast_wpseo_content_score', true ) ) {
	$fails[] = 'content_score przeniesiony — Yoast ma go przeliczyc sam';
}

// ── pary zapisane dla wszystkich trzech ───────────────────────────────────
$pary = \KzmielecTranslate\Services\SegmentStore::all( (int) $dst );
if ( count( $pary ) < 6 ) {
	$fails[] = 'zapisano ' . count( $pary ) . ' par dla meta, oczekiwano >=6 (4 churches + 2 Yoast)';
}
$pola = wp_list_pluck( $pary, 'field' );
foreach ( array( 'churches[0].description', '_yoast_wpseo_title' ) as $oczekiwane ) {
	if ( ! in_array( $oczekiwane, $pola, true ) ) {
		$fails[] = "brak pary dla pola \"$oczekiwane\" (jest: " . implode( ', ', $pola ) . ')';
	}
}

foreach ( array( $src, $dst, $msrc, $mdst ) as $id ) {
	wp_delete_post( (int) $id, true );
}

if ( $fails ) {
	echo "FAIL\n";
	foreach ( $fails as $f ) {
		echo "  - $f\n";
	}
	exit( 1 );
}
echo "PASS: pola meta tlumaczone, identyfikatory i liczby nietkniete\n";
