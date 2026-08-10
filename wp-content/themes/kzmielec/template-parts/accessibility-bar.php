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
?>
<div class="a11y-bar">
	<div class="a11y-bar__inner">

		<?php if ( $kzmielec_languages ) : ?>
			<nav class="a11y-bar__lang" aria-label="<?php esc_attr_e( 'Wybór języka', 'kzmielec' ); ?>">
				<ul class="a11y-bar__lang-list">
					<?php foreach ( $kzmielec_languages as $kzmielec_lang ) : ?>
						<?php
						$kzmielec_code    = strtoupper( (string) ( $kzmielec_lang['slug'] ?? '' ) );
						$kzmielec_current = ! empty( $kzmielec_lang['current_lang'] );
						?>
						<li class="a11y-bar__lang-item">
							<a
								class="a11y-bar__lang-link"
								href="<?php echo esc_url( (string) ( $kzmielec_lang['url'] ?? home_url( '/' ) ) ); ?>"
								lang="<?php echo esc_attr( str_replace( '_', '-', (string) ( $kzmielec_lang['locale'] ?? '' ) ) ); ?>"
								hreflang="<?php echo esc_attr( (string) ( $kzmielec_lang['slug'] ?? '' ) ); ?>"
								<?php echo $kzmielec_current ? ' aria-current="true"' : ''; ?>
							>
								<?php
								/*
								 * Same shape as the A+ / A++ buttons: the code is what you
								 * read, the full name is what you hear, and the accessible
								 * name begins with the visible text so WCAG 2.5.3 (Label in
								 * Name) holds. "PL" on its own says nothing out loud.
								 */
								?>
								<span class="a11y-bar__lang-code" aria-hidden="true"><?php echo esc_html( $kzmielec_code ); ?></span>
								<span class="visually-hidden"><?php echo esc_html( $kzmielec_code . ' — ' . (string) ( $kzmielec_lang['name'] ?? '' ) ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

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

	</div>
</div>
