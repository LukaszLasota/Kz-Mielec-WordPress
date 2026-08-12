<?php
/**
 * Accessibility bar — text size and high contrast controls.
 *
 * Variant A: a full-width strip above the site header. It scrolls away with the
 * page instead of sticking, so it never competes with the sticky menu for the
 * top of the viewport.
 *
 * Included from `header.php` AFTER the skip link on purpose: the strip is the
 * first thing on the page visually, but the first Tab still has to reach
 * "Przejdź do treści" rather than a font-size button.
 *
 * The strip is hidden until the inline script in `RegisterAssets` marks the
 * document with `data-a11y-js`, because every control here is inert without
 * JavaScript.
 *
 * @package Kzmielec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The three steps, in order. The value is the `data-a11y-size` attribute the
 * button applies to <html>; the stylesheet turns it into a root font-size
 * multiplier.
 *
 * `glyph` is what the visitor reads, and each label deliberately begins with
 * exactly that string, because the accessible name has to contain the visible
 * text (WCAG 2.5.3 Label in Name) — "A+" on screen must be "A+" when announced,
 * followed by something meaningful, since a letter and a plus sign on their own
 * say nothing out loud.
 *
 * The steps used to be one "A" at three font sizes. Size alone was too quiet a
 * signal: at a few pixels apart, and especially on a phone where the row has to
 * be tight, the three buttons read as identical.
 *
 * @var array<int, array{value: string, glyph: string, label: string}> $kzmielec_text_sizes
 */
$kzmielec_text_sizes = array(
	array(
		'value' => 'normal',
		'glyph' => 'A',
		'label' => __( 'A — rozmiar standardowy', 'kzmielec' ),
	),
	array(
		'value' => 'large',
		'glyph' => 'A+',
		'label' => __( 'A+ — tekst powiększony', 'kzmielec' ),
	),
	array(
		'value' => 'xlarge',
		'glyph' => 'A++',
		'label' => __( 'A++ — tekst największy', 'kzmielec' ),
	),
);

/*
 * Two groups in one strip, and the split is load-bearing.
 *
 * The accessibility controls are inert without JavaScript, which is why the
 * strip as a whole used to stay hidden until the inline script sets
 * `data-a11y-js`. The language links are ordinary anchors and work with
 * JavaScript off, so gating them the same way would take language choice away
 * from visitors who have nothing wrong with their setup. The gate therefore
 * moved down one level, onto `.a11y-bar__controls`.
 *
 * The switcher sits AFTER the controls in the markup, at the right-hand end of
 * the strip beside the contrast button. It used to come first and be pushed left
 * by an auto margin, on the reasoning that the half of the strip that works
 * without JavaScript should be reached first. Placement moved to the right at the
 * client's request, and the markup followed it rather than being reordered with
 * CSS: `order` would have left the switcher last on screen and first in the tab
 * sequence, so focus would jump from the far right back to the left-hand
 * controls. Matching document order to visual order is what WCAG 2.4.3 (Focus
 * Order) asks for, and it costs nothing here — with the controls hidden, the
 * switcher is the only thing in the strip either way.
 *
 * They are also two different things to a screen reader: a `role="group"`
 * labelled "Ustawienia dostępności" would announce the language choice under a
 * name that does not describe it. Hence a separate <nav> with its own label,
 * and no role on the outer container at all.
 *
 * `raw => 1` returns the language data instead of Polylang's own markup,
 * because this theme builds its navigation by hand.
 *
 * `hide_if_empty => 0` is not optional: Polylang hides languages that have no
 * posts yet, so without it the switcher would list Polish alone until the
 * content is translated — which is exactly the state this ships in.
 */
$kzmielec_languages = function_exists( 'pll_the_languages' )
	? (array) pll_the_languages(
		array(
			'raw'                    => 1,
			'hide_if_no_translation' => 0,
			'hide_if_empty'          => 0,
		)
	)
	: array();

/*
 * The switcher is a collapsed <details> showing the current language, and the
 * other languages live in the panel it opens. Four inline links cost the whole
 * left half of the strip on a phone; one flag costs a corner.
 *
 * Native <details>, not a scripted menu, and that is the point: it opens with
 * the keyboard, announces its own expanded state, and above all **works with
 * JavaScript off**, which the four plain anchors did too. A button with
 * `aria-expanded` would have handed language choice to a script — the one thing
 * this strip has always avoided for the half of it that does not need one.
 *
 * The current language is split out of the list rather than marked inside it:
 * a panel that offers you the language you are already reading wastes the one
 * row a phone can spare.
 */
$kzmielec_current_lang = null;
$kzmielec_other_langs  = array();

foreach ( $kzmielec_languages as $kzmielec_lang ) {
	if ( ! empty( $kzmielec_lang['current_lang'] ) ) {
		$kzmielec_current_lang = $kzmielec_lang;
		continue;
	}

	$kzmielec_other_langs[] = $kzmielec_lang;
}

/*
 * Fallback for the case where Polylang reports no current language — a 404 under
 * no prefix, for one. Without it the summary would render empty and the switcher
 * would be a caret with nothing to say.
 */
if ( null === $kzmielec_current_lang && $kzmielec_languages ) {
	$kzmielec_current_lang = reset( $kzmielec_languages );
	$kzmielec_other_langs  = array_slice( $kzmielec_languages, 1 );
}

/**
 * One language is not a choice, so the switcher does not render at all.
 *
 * @var bool $kzmielec_show_switcher
 */
$kzmielec_show_switcher = null !== $kzmielec_current_lang && array() !== $kzmielec_other_langs;

/**
 * Caret for the summary. An SVG rather than a CSS triangle on purpose: the
 * high-contrast block repaints every border on the page with
 * `border-color: … !important`, which would turn a triangle's transparent side
 * borders yellow and leave a solid square. A stroked path takes `currentcolor`
 * and follows the palette without that trap.
 *
 * @var string $kzmielec_caret
 */
$kzmielec_caret = '<svg class="a11y-bar__lang-caret" width="10" height="6" viewBox="0 0 10 6" fill="none" stroke="currentcolor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M1 1l4 4 4-4"/></svg>';
?>
<div class="a11y-bar">
	<div class="a11y-bar__inner">


		<div class="a11y-bar__controls" role="group" aria-label="<?php esc_attr_e( 'Ustawienia dostępności', 'kzmielec' ); ?>">
		<span class="a11y-bar__label" aria-hidden="true"><?php esc_html_e( 'Rozmiar tekstu', 'kzmielec' ); ?></span>

		<?php foreach ( $kzmielec_text_sizes as $kzmielec_index => $kzmielec_size ) : ?>
			<button
				type="button"
				class="a11y-bar__button a11y-bar__button--size a11y-bar__button--step-<?php echo esc_attr( (string) $kzmielec_index ); ?>"
				data-a11y-size="<?php echo esc_attr( $kzmielec_size['value'] ); ?>"
				aria-pressed="<?php echo 0 === $kzmielec_index ? 'true' : 'false'; ?>"
			>
				<span class="a11y-bar__glyph" aria-hidden="true"><?php echo esc_html( $kzmielec_size['glyph'] ); ?></span>
				<span class="visually-hidden"><?php echo esc_html( $kzmielec_size['label'] ); ?></span>
			</button>
		<?php endforeach; ?>

		<?php
		/*
		 * Two labels for one button, and only ever one of them rendered. This
		 * control is by far the widest thing on the strip — 207px against 126px
		 * for all three letters together once the largest text setting is on —
		 * so on a phone the row broke onto a second line. The short form is what
		 * makes it fit; the long form stays wherever there is room for it.
		 *
		 * Switched with `display: none`, not with `visually-hidden`, because a
		 * display-hidden node is excluded from the accessible name. The button is
		 * therefore announced with exactly the words that are on screen, which is
		 * what WCAG 2.5.3 (Label in Name) asks for. Two `visually-hidden` spans
		 * would have concatenated into "Wysoki kontrast Kontrast".
		 */
		?>
		<button
			type="button"
			class="a11y-bar__button a11y-bar__button--contrast"
			data-a11y-contrast
			aria-pressed="false"
		>
			<span class="a11y-bar__glyph a11y-bar__glyph--wide"><?php esc_html_e( 'Wysoki kontrast', 'kzmielec' ); ?></span>
			<span class="a11y-bar__glyph a11y-bar__glyph--narrow"><?php esc_html_e( 'Kontrast', 'kzmielec' ); ?></span>
		</button>
		</div>

		<?php if ( $kzmielec_show_switcher ) : ?>
			<?php
			$kzmielec_current_code = strtoupper( (string) ( $kzmielec_current_lang['slug'] ?? '' ) );
			$kzmielec_current_name = (string) ( $kzmielec_current_lang['name'] ?? '' );
			?>
			<nav class="a11y-bar__lang" aria-label="<?php esc_attr_e( 'Wybór języka', 'kzmielec' ); ?>">
				<details class="a11y-bar__lang-switch">
					<summary class="a11y-bar__lang-summary">
						<?php
						/*
						 * Flag first, code second. The flag is decoration only: it carries
						 * aria-hidden, and it is hidden altogether in high contrast, where
						 * decorative colour works against the whole point of the mode. With
						 * it hidden the summary still reads "PL" beside the caret.
						 */
						echo \Kzmielec\Core\LanguageFlags::get( (string) ( $kzmielec_current_lang['slug'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed inline SVG, no external input.
						?>
						<span class="a11y-bar__lang-code" aria-hidden="true"><?php echo esc_html( $kzmielec_current_code ); ?></span>
						<?php
						/*
						 * Same shape as the A+ / A++ buttons: the code is what you read, the
						 * sentence is what you hear, and the accessible name BEGINS with the
						 * visible text, which is what WCAG 2.5.3 (Label in Name) asks for.
						 * "PL" on its own says nothing out loud, and a control that opens a
						 * panel has to say so.
						 */
						?>
						<span class="visually-hidden">
							<?php
							printf(
								/* translators: 1: language code, 2: language name. */
								esc_html__( '%1$s — język strony: %2$s. Rozwiń, aby zmienić język', 'kzmielec' ),
								esc_html( $kzmielec_current_code ),
								esc_html( $kzmielec_current_name )
							);
							?>
						</span>
						<?php echo $kzmielec_caret; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed inline SVG, no external input. ?>
					</summary>

					<ul class="a11y-bar__lang-list">
						<?php foreach ( $kzmielec_other_langs as $kzmielec_lang ) : ?>
							<?php $kzmielec_code = strtoupper( (string) ( $kzmielec_lang['slug'] ?? '' ) ); ?>
							<li class="a11y-bar__lang-item">
								<a
									class="a11y-bar__lang-link"
									href="<?php echo esc_url( (string) ( $kzmielec_lang['url'] ?? home_url( '/' ) ) ); ?>"
									lang="<?php echo esc_attr( str_replace( '_', '-', (string) ( $kzmielec_lang['locale'] ?? '' ) ) ); ?>"
									hreflang="<?php echo esc_attr( (string) ( $kzmielec_lang['slug'] ?? '' ) ); ?>"
								>
									<?php echo \Kzmielec\Core\LanguageFlags::get( (string) ( $kzmielec_lang['slug'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed inline SVG, no external input. ?>
									<span class="a11y-bar__lang-code" aria-hidden="true"><?php echo esc_html( $kzmielec_code ); ?></span>
									<span class="visually-hidden"><?php echo esc_html( $kzmielec_code . ' — ' . (string) ( $kzmielec_lang['name'] ?? '' ) ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</details>
			</nav>
		<?php endif; ?>

	</div>
</div>
