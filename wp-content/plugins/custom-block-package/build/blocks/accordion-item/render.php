<?php
/**
 * Accordion Item Block Render Template
 *
 * Renders a single accordion item with title and collapsible content.
 * Follows WAI-ARIA Authoring Practices for accordion pattern.
 *
 * @package CustomBlockPackage
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Inner blocks HTML.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$accordion_title = isset( $attributes['title'] ) ? $attributes['title'] : '';
$content_id      = wp_unique_id( 'accordion-content-' );
?>

<?php
$wrapper_extra = array( 'class' => 'cbp-block accordion__item' );
if ( ! empty( $attributes['anchor'] ) ) {
	$wrapper_extra['id'] = $attributes['anchor'];
}
?>
<div <?php echo get_block_wrapper_attributes( $wrapper_extra ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Returns pre-escaped HTML. ?>>
	<h3 class="accordion__title-text">
		<button
			class="accordion__title"
			aria-expanded="false"
			aria-controls="<?php echo esc_attr( $content_id ); ?>"
		>
			<?php echo wp_kses_post( $accordion_title ); ?>
		</button>
	</h3>
	<div class="accordion__content" id="<?php echo esc_attr( $content_id ); ?>" hidden>
		<div class="accordion__content--box">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Inner blocks are already escaped by WordPress. ?>
		</div>
	</div>
</div>