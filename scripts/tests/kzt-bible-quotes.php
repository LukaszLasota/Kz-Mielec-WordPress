<?php
/**
 * Are the Scripture quotations in the content a published translation, or DeepL's paraphrase?
 *
 * This test exists because of what the dress rehearsal on a copy of the production database
 * revealed: `substitute-bible-quotes.php` matches DeepL's LITERAL output, and DeepL is not
 * deterministic. On reproduction two of 52 substitutions failed to find their text, so two
 * quotations stayed paraphrases. Nothing shows on the page: the paragraph is grammatical, it
 * reads sensibly, and it simply is not what the reader will find in their own Bible.
 *
 * What is checked is the TARGET side of every pair in `scripts/data/bible-substitutions.php`
 * — exactly the text the script inserts. The first version of this test assumed whole verses
 * from `bible-quotes.php` and reported 42 failures on a healthy database, because six of the
 * entries are short phrases quoted inside a sentence rather than complete verses.
 */
$fails = array();
$data  = ABSPATH . 'scripts/data/bible-substitutions.php';

if ( ! file_exists( $data ) ) {
	echo "FAIL\n  - missing file $data\n";
	exit( 1 );
}

$table = include $data;

if ( ! is_array( $table ) || ! $table ) {
	echo "FAIL\n  - the substitution table is empty\n";
	exit( 1 );
}

if ( ! function_exists( 'pll_languages_list' ) ) {
	echo "PASS: Polylang inactive — the foreign quotations have nowhere to be\n";
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
			 * A fragment, not the whole string: the inserted text sits in a paragraph that
			 * carries its own punctuation and markup around it. Fifty characters is enough to
			 * tell a translation from a paraphrase, and does not trip over the sentence end.
			 */
			$needle = mb_substr( $wanted, 0, 50 );

			$hits = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE %s",
					'%' . $wpdb->esc_like( $needle ) . '%'
				)
			);

			// The comparison table's columns live in post meta, not in the post content.
			if ( 0 === $hits ) {
				$hits = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'churches' AND meta_value LIKE %s",
						'%' . $wpdb->esc_like( $needle ) . '%'
					)
				);
			}

			if ( 0 === $hits ) {
				$fails[] = sprintf( '[%s] inserted translation missing: %s…', $lang, mb_substr( $needle, 0, 54 ) );
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
	printf( "  (%d of %d checked are present)\n", $present, $checked );
	exit( 1 );
}

printf( "PASS: Scripture in published translations, %d of %d present in the content\n", $present, $checked );
