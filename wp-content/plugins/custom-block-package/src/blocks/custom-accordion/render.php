<?php
/**
 * Custom Accordion Block Render Template
 *
 * Wrapper for accordion items.
 *
 * @package CustomBlockPackage
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Inner blocks HTML.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! trim( $content ) ) {
	return;
}
?>

<?php
$wrapper_extra = array( 'class' => 'cbp-block' );
if ( ! empty( $attributes['anchor'] ) ) {
	$wrapper_extra['id'] = $attributes['anchor'];
}
?>
<div <?php echo get_block_wrapper_attributes( $wrapper_extra ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Returns pre-escaped HTML. ?>>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Inner blocks are already escaped by WordPress. ?>
</div>
