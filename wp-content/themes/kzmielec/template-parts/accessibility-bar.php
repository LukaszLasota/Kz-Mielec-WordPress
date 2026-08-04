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
 * Each label deliberately starts with the visible "A" so the accessible name
 * contains the visible text (WCAG 2.5.3 Label in Name) — a button whose only
 * visible label is a letter still has to be announced as something meaningful.
 *
 * @var array<int, array{value: string, label: string}> $kzmielec_text_sizes
 */
$kzmielec_text_sizes = array(
	array(
		'value' => 'normal',
		'label' => __( 'A — rozmiar standardowy', 'kzmielec' ),
	),
	array(
		'value' => 'large',
		'label' => __( 'A — tekst powiększony', 'kzmielec' ),
	),
	array(
		'value' => 'xlarge',
		'label' => __( 'A — tekst największy', 'kzmielec' ),
	),
);
?>
<div class="a11y-bar" role="group" aria-label="<?php esc_attr_e( 'Ustawienia dostępności', 'kzmielec' ); ?>">
	<div class="a11y-bar__inner">
		<span class="a11y-bar__label" aria-hidden="true"><?php esc_html_e( 'Rozmiar tekstu', 'kzmielec' ); ?></span>

		<?php foreach ( $kzmielec_text_sizes as $kzmielec_index => $kzmielec_size ) : ?>
			<button
				type="button"
				class="a11y-bar__button a11y-bar__button--size a11y-bar__button--step-<?php echo esc_attr( (string) $kzmielec_index ); ?>"
				data-a11y-size="<?php echo esc_attr( $kzmielec_size['value'] ); ?>"
				aria-pressed="<?php echo 0 === $kzmielec_index ? 'true' : 'false'; ?>"
			>
				<span class="a11y-bar__glyph" aria-hidden="true">A</span>
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
