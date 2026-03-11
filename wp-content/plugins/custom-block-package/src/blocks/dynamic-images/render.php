<?php
/**
 * Dynamic Images Block Render Template
 *
 * Generates a <picture> element with responsive sources for desktop, tablet, and mobile.
 *
 * @package CustomBlockPackage
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$img_desktop_id = isset( $attributes['imgDesktopID'] ) ? (int) $attributes['imgDesktopID'] : 0;
$img_tablet_id  = isset( $attributes['imgTabletID'] ) ? (int) $attributes['imgTabletID'] : 0;
$img_mobile_id  = isset( $attributes['imgMobileID'] ) ? (int) $attributes['imgMobileID'] : 0;

// Fetch image data for each breakpoint.
$desktop_data   = $img_desktop_id ? wp_get_attachment_image_src( $img_desktop_id, 'full' ) : null;
$desktop_srcset = $img_desktop_id ? (string) wp_get_attachment_image_srcset( $img_desktop_id, 'full' ) : '';
$desktop_sizes  = $img_desktop_id ? (string) wp_get_attachment_image_sizes( $img_desktop_id, 'full' ) : '';

$tablet_data   = $img_tablet_id ? wp_get_attachment_image_src( $img_tablet_id, 'full' ) : null;
$tablet_srcset = $img_tablet_id ? (string) wp_get_attachment_image_srcset( $img_tablet_id, 'full' ) : '';
$tablet_sizes  = $img_tablet_id ? (string) wp_get_attachment_image_sizes( $img_tablet_id, 'full' ) : '';

$mobile_data   = $img_mobile_id ? wp_get_attachment_image_src( $img_mobile_id, 'full' ) : null;
$mobile_srcset = $img_mobile_id ? (string) wp_get_attachment_image_srcset( $img_mobile_id, 'full' ) : '';
$mobile_sizes  = $img_mobile_id ? (string) wp_get_attachment_image_sizes( $img_mobile_id, 'full' ) : '';

if ( ! $desktop_data && ! $tablet_data && ! $mobile_data ) {
	return;
}

// Heading for SEO and accessibility (visually hidden).
$heading = isset( $attributes['heading'] ) ? trim( (string) $attributes['heading'] ) : '';

// If heading is set it acts as the text alternative for the image (decorative pattern).
// Otherwise use the alt text from the WordPress media library.
$alt_image_id = $img_desktop_id ? $img_desktop_id : ( $img_tablet_id ? $img_tablet_id : $img_mobile_id );
$alt_text     = $heading ? '' : ( $alt_image_id ? (string) get_post_meta( $alt_image_id, '_wp_attachment_image_alt', true ) : '' );

// Determine fallback image for <img> tag (desktop > tablet > mobile).
$fallback_data   = $desktop_data ? $desktop_data : ( $tablet_data ? $tablet_data : $mobile_data );
$fallback_srcset = $desktop_srcset ? $desktop_srcset : ( $tablet_srcset ? $tablet_srcset : $mobile_srcset );
$fallback_sizes  = $desktop_sizes ? $desktop_sizes : ( $tablet_sizes ? $tablet_sizes : $mobile_sizes );
?>

<?php
$wrapper_extra = array();
if ( ! empty( $attributes['anchor'] ) ) {
	$wrapper_extra['id'] = $attributes['anchor'];
}
?>
<div <?php echo get_block_wrapper_attributes( $wrapper_extra ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Returns pre-escaped HTML. ?>>
	<?php if ( $heading ) : ?>
		<h1 class="visually-hidden"><?php echo esc_html( $heading ); ?></h1>
	<?php endif; ?>
	<picture>
		<?php if ( $mobile_data ) : ?>
			<source
				srcset="<?php echo esc_attr( $mobile_srcset ? $mobile_srcset : $mobile_data[0] ); ?>"
				<?php if ( $mobile_sizes ) : ?>
					sizes="<?php echo esc_attr( $mobile_sizes ); ?>"
				<?php endif; ?>
				media="(max-width: 480px)"
				width="<?php echo intval( $mobile_data[1] ); ?>"
				height="<?php echo intval( $mobile_data[2] ); ?>"
			/>
		<?php endif; ?>

		<?php if ( $tablet_data ) : ?>
			<source
				srcset="<?php echo esc_attr( $tablet_srcset ? $tablet_srcset : $tablet_data[0] ); ?>"
				<?php if ( $tablet_sizes ) : ?>
					sizes="<?php echo esc_attr( $tablet_sizes ); ?>"
				<?php endif; ?>
				media="(max-width: 768px)"
				width="<?php echo intval( $tablet_data[1] ); ?>"
				height="<?php echo intval( $tablet_data[2] ); ?>"
			/>
		<?php endif; ?>

		<img
			src="<?php echo esc_url( $fallback_data[0] ); ?>"
			alt="<?php echo esc_attr( $alt_text ); ?>"
			width="<?php echo intval( $fallback_data[1] ); ?>"
			height="<?php echo intval( $fallback_data[2] ); ?>"
			loading="eager"
			fetchpriority="high"
			decoding="async"
			<?php if ( $fallback_srcset ) : ?>
				srcset="<?php echo esc_attr( $fallback_srcset ); ?>"
			<?php endif; ?>
			<?php if ( $fallback_sizes ) : ?>
				sizes="<?php echo esc_attr( $fallback_sizes ); ?>"
			<?php endif; ?>
		/>
	</picture>
</div>
