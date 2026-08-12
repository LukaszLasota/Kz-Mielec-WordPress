<?php
/**
 * Blocks that fetch content must narrow it to the language of the rendered post.
 *
 * This test exists because the defect is INVISIBLE on the front end. Polylang narrowed the
 * queries by itself, using the language of the request, so the page looked right while the
 * editor — which renders blocks through a REST route with no language context — received all
 * four versions at once: 12 meetings instead of 3, 148 topics instead of 37, 36 headings
 * instead of 9.
 *
 * The heart of the test: make a foreign-language post current while LEAVING the request in
 * Polish. That is exactly the editor's situation. The block must answer in the post's
 * language.
 */
$fails = array();
$svc   = '\CustomBlockPackage\Services\NavigableTilesService';

if ( ! class_exists( $svc ) ) {
	echo "FAIL\n  - missing class $svc\n";
	exit( 1 );
}

if ( ! function_exists( 'pll_get_post' ) ) {
	echo "PASS: Polylang inactive — narrowing does not apply\n";
	exit( 0 );
}

/** The Polish front page and its translations. */
$front = array( 'pl' => 131 );
foreach ( array( 'en', 'uk', 'es' ) as $l ) {
	$t = pll_get_post( 131, $l );
	if ( $t ) {
		$front[ $l ] = (int) $t;
	}
}

if ( 4 !== count( $front ) ) {
	$fails[] = 'did not find four versions of the front page, found ' . count( $front );
}

// ── tiles: count and language ──────────────────────────────────────────────
foreach ( $front as $lang => $id ) {
	$GLOBALS['post'] = get_post( $id );
	setup_postdata( $GLOBALS['post'] );

	$meetings = $svc::get_meetings();
	$beliefs  = $svc::get_beliefs();

	if ( 3 !== count( $meetings ) ) {
		$fails[] = "$lang: meetings " . count( $meetings ) . ', expected 3';
	}
	if ( 8 !== count( $beliefs ) ) {
		$fails[] = "$lang: belief pages " . count( $beliefs ) . ', expected 8';
	}

	foreach ( array( 'meetings' => $meetings, 'beliefs' => $beliefs ) as $what => $items ) {
		foreach ( $items as $item ) {
			$got = (string) pll_get_post_language( (int) $item['id'] );
			if ( $got !== $lang ) {
				$fails[] = "$lang: $what — tile #" . $item['id'] . " is in language '$got'";
				break;
			}
		}
	}

	wp_reset_postdata();
}

// ── comparison accordion: topics and headings ──────────────────────────────
$cmp = array( 'pl' => 83 );
foreach ( array( 'en', 'uk', 'es' ) as $l ) {
	$t = pll_get_post( 83, $l );
	if ( $t ) {
		$cmp[ $l ] = (int) $t;
	}
}

foreach ( $cmp as $lang => $id ) {
	$topics = get_posts(
		array(
			'post_type'      => 'comparison_topic',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'lang'           => $lang,
		)
	);
	$terms  = get_terms(
		array(
			'taxonomy'   => 'comparison_category',
			'hide_empty' => true,
			'lang'       => $lang,
		)
	);

	if ( 37 !== count( $topics ) ) {
		$fails[] = "$lang: comparison topics " . count( $topics ) . ', expected 37';
	}
	if ( ! is_array( $terms ) || 9 !== count( $terms ) ) {
		$fails[] = "$lang: naglowkow akordeonu " . ( is_array( $terms ) ? count( $terms ) : 0 ) . ', expected 9';
	}
}

// ── the accordion order is the same in every language ──────────────────────
$by_lang = array();
foreach ( array_keys( $cmp ) as $lang ) {
	$ordered = get_terms(
		array(
			'taxonomy'   => 'comparison_category',
			'hide_empty' => true,
			'lang'       => $lang,
			'meta_key'   => 'sort_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'    => 'meta_value_num',
			'order'      => 'ASC',
		)
	);

	$by_lang[ $lang ] = is_array( $ordered ) ? wp_list_pluck( $ordered, 'term_id' ) : array();
}

if ( function_exists( 'pll_get_term' ) && $by_lang ) {
	foreach ( $by_lang['pl'] as $position => $pl_term ) {
		foreach ( array( 'en', 'uk', 'es' ) as $lang ) {
			if ( ! isset( $by_lang[ $lang ] ) ) {
				continue;
			}

			$translated = (int) pll_get_term( $pl_term, $lang );
			$found      = array_search( $translated, $by_lang[ $lang ], true );

			if ( $found !== $position ) {
				$fails[] = "accordion order: position $position in pl is position " . var_export( $found, true ) . " in $lang";
			}
		}
	}
}

if ( $fails ) {
	echo "FAIL\n";
	foreach ( $fails as $f ) {
		echo "  - $f\n";
	}
	exit( 1 );
}

echo "PASS: blocks narrow content to the post language; accordion agrees in count and order\n";
