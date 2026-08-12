<?php
/**
 * Makes Scripture references consistent inside the translated content.
 *
 * Run with:
 *   ddev wp eval-file scripts/normalize-bible-refs.php --path=/var/www/html          (dry run)
 *   ddev wp eval-file scripts/normalize-bible-refs.php --path=/var/www/html -- go    (writes)
 *
 * WHY. The doctrinal statements carry 62 Scripture references each. DeepL got every
 * chapter and verse number right — 186 reference pairs compared against the Polish
 * source, zero differences — but it varied the book names freely: English cited
 * Matthew as "Mt" eight times, "Matt" eight times and "Matthew" twice, and mixed
 * "1 Cor 11:20" with "1 Cor 10,16"; Ukrainian put a full stop after some books and
 * not others and split separators 85 colons to 109 commas; Spanish set "Génesis"
 * beside "Éx" and "1 Pe" beside "2 P". In a document that a reader may check verse
 * by verse, three names for one book reads as carelessness.
 *
 * The POLISH source is deliberately left alone. It is the verbatim text of the
 * Supreme Church Council's statements — including its own small inconsistencies
 * ("1Kor" once, "1 Kor" eighteen times) — and retypesetting another body's official
 * document is not ours to do. Only the three translations, which we authored, are
 * normalised.
 *
 * Scope is narrow on purpose: only the inside of parentheses that already look like
 * a reference. Prose is never touched, so a sentence mentioning Mark the Evangelist
 * keeps his name.
 *
 * @package Kzmielec
 */

// No `declare(strict_types=1)`: `wp eval-file` runs this through eval(), where a
// declare would have to be the first statement of the script and so cannot appear.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$kz_execute = in_array( 'go', (array) $args, true );

/**
 * Canonical book form per language, keyed by every variant seen in the content.
 *
 * The canonical choice is the majority form already in use, so the edit stays as
 * small as it can be while still ending up consistent.
 */
function kz_books(): array {
	return array(
	'en' => array(
		'Matt' => 'Matt', 'Matthew' => 'Matt', 'Mt' => 'Matt',
		'Mark' => 'Mark', 'Mk' => 'Mark',
		'Luke' => 'Luke', 'Lk' => 'Luke',
		'John' => 'John', 'Jn' => 'John',
		'Acts' => 'Acts',
		'Rom' => 'Rom', 'Romans' => 'Rom',
		'1 Cor' => '1 Cor', '1 Corinthians' => '1 Cor', '1Cor' => '1 Cor',
		'2 Cor' => '2 Cor', '2 Corinthians' => '2 Cor',
		'Gal' => 'Gal', 'Galatians' => 'Gal',
		'Eph' => 'Eph', 'Ephesians' => 'Eph',
		'Phil' => 'Phil', 'Philippians' => 'Phil',
		'Col' => 'Col', 'Colossians' => 'Col',
		'1 Thess' => '1 Thess', '1 Thessalonians' => '1 Thess',
		'1 Tim' => '1 Tim', '1 Timothy' => '1 Tim',
		'Heb' => 'Heb', 'Hebrews' => 'Heb',
		'Jas' => 'Jas', 'James' => 'Jas',
		'1 Pet' => '1 Pet', '1 Peter' => '1 Pet',
		'2 Pet' => '2 Pet', '2 Peter' => '2 Pet',
		'1 John' => '1 John',
		'Jude' => 'Jude',
		'Rev' => 'Rev', 'Revelation' => 'Rev',
		'Gen' => 'Gen', 'Genesis' => 'Gen',
		'Ex' => 'Ex', 'Exod' => 'Ex', 'Exodus' => 'Ex',
		'Lev' => 'Lev', 'Leviticus' => 'Lev',
		'Num' => 'Num', 'Numbers' => 'Num',
		'Deut' => 'Deut', 'Deuteronomy' => 'Deut',
		'Judg' => 'Judg', 'Judges' => 'Judg',
		'Ruth' => 'Ruth',
		'Ps' => 'Ps', 'Psalm' => 'Ps', 'Psalms' => 'Ps',
		'Eccl' => 'Eccl', 'Ecclesiastes' => 'Eccl',
		'Mal' => 'Mal', 'Malachi' => 'Mal',
	),
	'uk' => array(
		'Мт' => 'Мт.', 'Мт.' => 'Мт.', 'Матв' => 'Мт.', 'Матв.' => 'Мт.',
		'Мк' => 'Мр.', 'Мк.' => 'Мр.', 'Мр' => 'Мр.', 'Мр.' => 'Мр.',
		'Лк' => 'Лк.', 'Лк.' => 'Лк.',
		'Ів' => 'Ів.', 'Ів.' => 'Ів.',
		'Дії' => 'Дії', 'Ді' => 'Дії', 'Діян' => 'Дії', 'Діян.' => 'Дії',
		'Рим' => 'Рим.', 'Рим.' => 'Рим.',
		'1 Кор' => '1 Кор.', '1 Кор.' => '1 Кор.',
		'2 Кор' => '2 Кор.', '2 Кор.' => '2 Кор.',
		'Гал' => 'Гал.', 'Гал.' => 'Гал.',
		'Еф' => 'Еф.', 'Еф.' => 'Еф.',
		'Флп' => 'Флп.', 'Флп.' => 'Флп.', 'Фил' => 'Флп.', 'Фил.' => 'Флп.',
		'Кол' => 'Кол.', 'Кол.' => 'Кол.',
		'1 Сол' => '1 Сол.', '1 Сол.' => '1 Сол.',
		'1 Тим' => '1 Тим.', '1 Тим.' => '1 Тим.',
		'Євр' => 'Євр.', 'Євр.' => 'Євр.',
		'Як' => 'Як.', 'Як.' => 'Як.',
		'1 Пт' => '1 Пет.', '1 Пет' => '1 Пет.', '1 Пет.' => '1 Пет.',
		'2 Пт' => '2 Пет.', '2 Пет' => '2 Пет.', '2 Пет.' => '2 Пет.',
		'1 Ів' => '1 Ів.', '1 Ів.' => '1 Ів.',
		'Юда' => 'Юда', 'Юд' => 'Юда', 'Юд.' => 'Юда',
		'Об' => 'Об.', 'Об.' => 'Об.', 'Об’явл' => 'Об.', 'Об’явл.' => 'Об.', 'Обʼявл.' => 'Об.',
		'Бут' => 'Бут.', 'Бут.' => 'Бут.',
		'Вих' => 'Вих.', 'Вих.' => 'Вих.',
		'Лев' => 'Лев.', 'Лев.' => 'Лев.',
		'Чис' => 'Чис.', 'Чис.' => 'Чис.',
		'Втор' => 'Повт.', 'Втор.' => 'Повт.', 'Повт' => 'Повт.', 'Повт.' => 'Повт.',
		'Суд' => 'Суд.', 'Суд.' => 'Суд.',
		'Рут' => 'Рут', 'Рут.' => 'Рут',
		'Пс' => 'Пс.', 'Пс.' => 'Пс.',
		'Еккл' => 'Екл.', 'Еккл.' => 'Екл.', 'Екл' => 'Екл.', 'Екл.' => 'Екл.',
		'Мл' => 'Мал.', 'Мал' => 'Мал.', 'Мал.' => 'Мал.',
	),
	'es' => array(
		'Mt' => 'Mt', 'Mateo' => 'Mt',
		'Mc' => 'Mc', 'Marcos' => 'Mc', 'Mr' => 'Mc',
		'Lc' => 'Lc', 'Lucas' => 'Lc',
		'Jn' => 'Jn', 'Juan' => 'Jn',
		'Hch' => 'Hch', 'Hechos' => 'Hch',
		'Rom' => 'Rom', 'Ro' => 'Rom', 'Romanos' => 'Rom',
		'1 Cor' => '1 Cor', '1 Co' => '1 Cor', '1 Corintios' => '1 Cor',
		'2 Cor' => '2 Cor', '2 Co' => '2 Cor', '2 Corintios' => '2 Cor',
		'Gál' => 'Gál', 'Gal' => 'Gál', 'Gá' => 'Gál', 'Gálatas' => 'Gál',
		'Ef' => 'Ef', 'Efesios' => 'Ef',
		'Flp' => 'Flp', 'Fil' => 'Flp', 'Filipenses' => 'Flp',
		'Col' => 'Col', 'Colosenses' => 'Col',
		'1 Tes' => '1 Tes', '1 Ts' => '1 Tes', '1 Tesalonicenses' => '1 Tes',
		'1 Tim' => '1 Tim', '1 Ti' => '1 Tim', '1 Timoteo' => '1 Tim',
		'Heb' => 'Heb', 'He' => 'Heb', 'Hebreos' => 'Heb',
		'Stg' => 'Stg', 'Sant' => 'Stg', 'Santiago' => 'Stg',
		'1 P' => '1 P', '1 Pe' => '1 P', '1 Pedro' => '1 P',
		'2 P' => '2 P', '2 Pe' => '2 P', '2 Pedro' => '2 P',
		'1 Jn' => '1 Jn',
		'Jud' => 'Jud', 'Judas' => 'Jud',
		'Ap' => 'Ap', 'Apocalipsis' => 'Ap',
		'Gn' => 'Gn', 'Gén' => 'Gn', 'Génesis' => 'Gn',
		'Ex' => 'Éx', 'Éx' => 'Éx', 'Éxodo' => 'Éx', 'Exodo' => 'Éx',
		'Lev' => 'Lv', 'Lv' => 'Lv', 'Levítico' => 'Lv',
		'Núm' => 'Nm', 'Nm' => 'Nm', 'Números' => 'Nm',
		'Dt' => 'Dt', 'Deut' => 'Dt', 'Deuteronomio' => 'Dt',
		'Jue' => 'Jue', 'Jueces' => 'Jue',
		'Rt' => 'Rt', 'Rut' => 'Rt',
		'Sal' => 'Sal', 'Salmo' => 'Sal', 'Salmos' => 'Sal',
		'Ecl' => 'Ecl', 'Eclesiastés' => 'Ecl',
		'Ml' => 'Mal', 'Mal' => 'Mal', 'Malaquías' => 'Mal',
	),
	);
}

/**
 * Chapter/verse separator per language, and the connector for "compare".
 *
 * English takes the colon, which is what English-language Bibles use and what 184
 * of its 199 separators already were. Ukrainian and Spanish take the comma, the
 * continental convention and the one the Polish source uses.
 */
function kz_style(): array {
	return array(
	'en' => array( 'sep' => ':', 'cf' => 'cf.' ),
	'uk' => array( 'sep' => ',', 'cf' => 'пор.' ),
	'es' => array( 'sep' => ',', 'cf' => 'cf.' ),
	);
}

/**
 * Rewrite one reference parenthetical.
 *
 * @param string $ref  Text inside the parentheses.
 * @param string $lang Language slug.
 * @return string
 */
function kz_normalize_ref( string $ref, string $lang ): string {
	/*
	 * The tables are fetched, not read from globals: `wp eval-file` evaluates this
	 * file inside a function, so nothing declared at the top of it is actually a
	 * global and `global $kz_books` arrived as null.
	 */
	$books = kz_books();
	$style = kz_style();

	$out = $ref;

	/*
	 * A parenthesis is only a Scripture reference if it names a book of the Bible.
	 * Without this guard the pattern also matched legal citations — the Ukrainian
	 * "(Збірник законів за 2016 рік, п. 1943, 1954 …)" and the Spanish
	 * "(Boletín Oficial de 2016, núm. 1943, 1954 …)" are the Polish Journal of Laws,
	 * and the separator rule below closed the space after "1943,". Caught in a dry
	 * run; it would have damaged the child-protection policy in two languages.
	 */
	$has_book = false;

	foreach ( array_keys( $books[ $lang ] ) as $variant ) {
		if ( preg_match( '/(?<![\p{L}\.])' . preg_quote( rtrim( $variant, '.' ), '/' ) . '\.?(?=\s?\d)/u', $out ) ) {
			$has_book = true;
			break;
		}
	}

	if ( ! $has_book ) {
		return $ref;
	}

	// Longest variant first, so "1 Corinthians" is not eaten by "1 Cor".
	$map = $books[ $lang ];
	uksort(
		$map,
		static function ( $a, $b ) {
			return mb_strlen( $b ) <=> mb_strlen( $a );
		}
	);

	foreach ( $map as $variant => $canonical ) {
		if ( $variant === $canonical ) {
			continue;
		}

		/*
		 * The variant must be followed by a space and a digit, so only an actual
		 * citation is rewritten. A trailing full stop in the variant is optional in
		 * the pattern, because "Мт" and "Мт." are the same book cited two ways.
		 */
		$pattern = '/(?<![\p{L}\.])' . preg_quote( rtrim( $variant, '.' ), '/' ) . '\.?(?=\s?\d)/u';
		$out     = preg_replace( $pattern, str_replace( '$', '\$', $canonical ), $out );
	}

	/*
	 * Only the FIRST separator of each segment is the chapter/verse one; every later
	 * one separates verses from each other. Replacing them all turned
	 * "1 Cor 7:11, 15" — verses 11 and 15 — into "1 Cor 7:11:15", which means
	 * nothing. Caught in a dry run, and the reason this runs segment by segment.
	 */
	$sep      = $style[ $lang ]['sep'];
	$segments = preg_split( '/;/u', $out );

	foreach ( $segments as $i => $segment ) {
		$segments[ $i ] = preg_replace(
			'/(\d)\s?[,:]\s?(?=\d)/u',
			'$1' . $sep,
			$segment,
			1
		);
	}

	$out = implode( ';', $segments );

	// One connector for "compare with".
	$cf  = $style[ $lang ]['cf'];
	$out = preg_replace( '/\b(?:cf\.|por\.\s*z|пор\.|compárese\s+con|see\s+also|y\s+ss\.)\s*/u', $cf . ' ', $out );

	// DeepL left doubled spaces behind in a few places.
	$out = preg_replace( '/\s{2,}/', ' ', $out );

	return $out;
}

$kz_types   = array( 'page', 'meetings', 'comparison_topic' );
$kz_changed = 0;
$kz_refs    = 0;
$kz_samples = array();

foreach ( array( 'en', 'uk', 'es' ) as $kz_lang ) {
	$kz_ids = get_posts(
		array(
			'post_type'      => $kz_types,
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

		$kz_content = get_post_field( 'post_content', $kz_id );

		$kz_new = preg_replace_callback(
			'/\(([^()]*?\d+\s?[,:]\s?\d+[^()]*?)\)/u',
			static function ( $m ) use ( $kz_lang, &$kz_refs, &$kz_samples ) {
				++$kz_refs;
				$fixed = kz_normalize_ref( $m[1], $kz_lang );

				if ( $fixed !== $m[1] && count( $kz_samples ) < 200 ) {
					$kz_samples[] = array( $kz_lang, $m[1], $fixed );
				}

				return '(' . $fixed . ')';
			},
			$kz_content
		);

		if ( null === $kz_new || $kz_new === $kz_content ) {
			continue;
		}

		++$kz_changed;

		if ( $kz_execute ) {
			wp_update_post(
				array(
					'ID'           => $kz_id,
					'post_content' => $kz_new,
				)
			);
		}
	}
}

foreach ( $kz_samples as $kz_s ) {
	WP_CLI::log( sprintf( "  [%s]\n    - (%s)\n    + (%s)", $kz_s[0], $kz_s[1], $kz_s[2] ) );
}

WP_CLI::log( sprintf( 'odsylaczy przejrzanych: %d, wpisow do zmiany: %d', $kz_refs, $kz_changed ) );

if ( $kz_execute ) {
	WP_CLI::success( 'Zapisane.' );
} else {
	WP_CLI::warning( 'PRÓBA — nic nie zapisano. Dodaj `-- go`, aby zapisać.' );
}
