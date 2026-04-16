<?php
/**
 * Scroll Arrow Block Render Template
 *
 * Renders a circular scroll-to button with the arrow image from the plugin.
 * Direction (down/up) selects the corresponding arrow image.
 * Smooth scroll handled by view.js.
 *
 * @package CustomBlockPackage
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$target_id  = isset( $attributes['targetId'] ) ? sanitize_html_class( $attributes['targetId'] ) : '';
$direction  = isset( $attributes['direction'] ) && 'up' === $attributes['direction'] ? 'up' : 'down';
$aria_label = isset( $attributes['ariaLabel'] ) ? trim( (string) $attributes['ariaLabel'] ) : '';

if ( ! $target_id ) {
	return;
}

if ( ! $aria_label ) {
	$aria_label = 'down' === $direction
		? __( 'Przewiń w dół', 'custom-block-package' )
		: __( 'Przewiń w górę', 'custom-block-package' );
}

$arrow_file = 'down' === $direction ? 'arrow-down.png' : 'arrow-up.png';
$arrow_url  = plugins_url( 'images/' . $arrow_file, __FILE__ );

$wrapper_extra = array();
if ( ! empty( $attributes['anchor'] ) ) {
	$wrapper_extra['id'] = $attributes['anchor'];
}
?>
<div <?php echo get_block_wrapper_attributes( $wrapper_extra ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Returns pre-escaped HTML. ?>>
	<a
		href="<?php echo esc_attr( '#' . $target_id ); ?>"
		class="scroll-arrow"
		aria-label="<?php echo esc_attr( $aria_label ); ?>"
	>
		<figure>
			<img
				src="<?php echo esc_url( $arrow_url ); ?>"
				alt=""
				loading="lazy"
				aria-hidden="true"
			/>
		</figure>
	</a>
</div>
