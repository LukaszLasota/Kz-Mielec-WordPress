<?php
/**
 * Adds the `x-default` hreflang that Polylang omits on this configuration.
 *
 * @package Kzmielec
 */

declare(strict_types=1);

namespace Kzmielec\Seo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Marks the Polish version as the default for visitors no language version fits.
 *
 * Polylang emits a complete hreflang graph — pl, en, uk, es, each pointing at the
 * others — but it adds `x-default` under one condition only:
 * `is_front_page() && ! hide_default && force_lang < 3`. This site runs with
 * `hide_default => true`, because Polish sits at the site root with no `/pl/` prefix
 * and no existing Polish URL was allowed to move. So the condition is never true and
 * `x-default` was never emitted anywhere. Measured on the home page: four hreflang
 * entries, none of them `x-default`.
 *
 * What that costs: `x-default` is the entry Google uses for a searcher whose language
 * matches none of the four. Without it, that searcher gets whichever version the
 * crawler happened to favour. With it, they get Polish — which is the right answer
 * for a congregation in Mielec whose services are in Polish.
 *
 * On every page, not only the front page. Polylang's own condition restricts it to
 * the front page, which is the conservative reading of Google's documentation; but
 * the guidance is per-page, and a Ukrainian searcher landing on a doctrinal statement
 * has the same ambiguity to resolve as one landing on the home page.
 *
 * Implemented through Polylang's own `pll_rel_hreflang_attributes` filter rather than
 * by printing a second `<link>` in `wp_head`. Two tags for one relationship is how
 * you get a conflicting graph, and the filter runs after Polylang has resolved the
 * per-page URLs — which is exactly the value `x-default` has to point at.
 */
class Hreflang {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'pll_rel_hreflang_attributes', array( $this, 'add_x_default' ) );
	}

	/**
	 * Point `x-default` at the Polish URL of the current page.
	 *
	 * @param array<string, string> $hreflangs Language code => URL, as Polylang built it.
	 * @return array<string, string>
	 */
	public function add_x_default( $hreflangs ): array {
		$hreflangs = (array) $hreflangs;

		if ( isset( $hreflangs['x-default'] ) ) {
			return $hreflangs;
		}

		/*
		 * The Polish entry, whichever key it came out under. Polylang drops the country
		 * code when it is not needed, so this is `pl` here — but it emits `pl-PL` when
		 * two variants of one language exist, and hard-coding `pl` would then silently
		 * stop working.
		 */
		$polish = '';

		foreach ( $hreflangs as $code => $url ) {
			if ( 'pl' === $code || 0 === strpos( (string) $code, 'pl-' ) ) {
				$polish = (string) $url;
				break;
			}
		}

		if ( '' === $polish ) {
			return $hreflangs;
		}

		$hreflangs['x-default'] = $polish;

		return $hreflangs;
	}
}
