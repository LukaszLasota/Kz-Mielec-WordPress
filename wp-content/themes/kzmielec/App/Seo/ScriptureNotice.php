<?php
/**
 * Puts the Scripture attribution where the Scripture actually is.
 *
 * @package Kzmielec
 */

declare(strict_types=1);

namespace Kzmielec\Seo;

use Kzmielec\Interfaces\ActionHookInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Appends the source note to the pages that quote a copyrighted Bible translation.
 *
 * NIV and NVI belong to Biblica, the Ukrainian УТТ to the Ukrainian Bible Society, and the
 * EIB literary translation to the Ewangeliczny Instytut Biblijny. Each of them asks for a
 * source note wherever its text is quoted, so the note is a licence condition rather than
 * a courtesy — and it has to travel with the quotations.
 *
 * It used to sit in the main footer, which put a legal notice about somebody else's work on
 * every page of the site, including the great majority that quote nothing. Now it appears
 * only under content carrying the marker `_kzt_scripture`, which
 * `scripts/substitute-bible-quotes.php` sets by looking for the substituted text itself.
 * The marker is written by the script rather than by hand for one reason: reproducing the
 * content on production would otherwise bring the quotations without the note, and nobody
 * would notice.
 */
class ScriptureNotice implements ActionHookInterface {

	/**
	 * Meta key marking content that quotes a copyrighted translation.
	 */
	public const META = '_kzt_scripture';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		// Priority 20: after the block renderer and after any content filter that adds
		// its own tail, so the note stays last.
		add_filter( 'the_content', array( $this, 'append' ), 20 );
	}

	/**
	 * Append the note to marked content.
	 *
	 * @param string $content Rendered content.
	 * @return string
	 */
	public function append( $content ): string {
		$content = (string) $content;

		/*
		 * `in_the_loop()` is deliberately NOT part of this guard.
		 *
		 * `page.php` in this theme calls `the_content()` without a loop — no
		 * `while ( have_posts() )` at all, it relies on the global post WordPress has
		 * already set up. Requiring `in_the_loop()` therefore rejected every page on that
		 * template, which is where the seven Council statements live: they carried the
		 * marker and showed no note, while `page-belief.php` (which does loop) worked.
		 * `is_singular()` plus the marker is enough, and the duplicate check below covers
		 * a filter that runs twice.
		 */
		if ( ! is_singular() ) {
			return $content;
		}

		$post_id = get_the_ID();

		if ( ! $post_id || '' === (string) get_post_meta( $post_id, self::META, true ) ) {
			return $content;
		}

		// The filter can run twice on cached or nested output.
		if ( false !== strpos( $content, 'scripture-notice' ) ) {
			return $content;
		}

		/*
		 * The same source string as before, so the three existing translations keep
		 * working — the note moved, its wording did not.
		 */
		$note = __( 'Cytaty biblijne: Biblia Warszawska (w oświadczeniach Naczelnej Rady Kościoła) oraz Biblia Ewangeliczna, przekład literacki, © Ewangeliczny Instytut Biblijny.', 'kzmielec' );

		return $content . sprintf(
			'<aside class="scripture-notice"><p>%s</p></aside>',
			esc_html( $note )
		);
	}
}
