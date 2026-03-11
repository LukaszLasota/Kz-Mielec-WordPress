<?php
/**
 * Szablon renderowania bloku Obraz i Tekst
 *
 * @package CustomBlockPackage
 *
 * @var array $attributes Atrybuty bloku
 * @var string $content Zawartość bloku
 * @var WP_Block $block Instancja bloku
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text     = $attributes['text'] ?? '';
$img_id   = $attributes['imgID'] ?? 0;
$post_url = $attributes['postURL'] ?? [
	'url'           => '',
	'opensInNewTab' => false,
];

$full_width = ! empty( $attributes['fullWidth'] );
$classes    = 'about-one' . ( $full_width ? ' about-one--full' : '' );

$wrapper_extra = [ 'class' => $classes ];
if ( ! empty( $attributes['anchor'] ) ) {
	$wrapper_extra['id'] = $attributes['anchor'];
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_extra );

$link_url    = $post_url['url'] ?? '';
$link_target = ! empty( $post_url['opensInNewTab'] ) ? '_blank' : '';
$link_rel    = ! empty( $post_url['opensInNewTab'] ) ? 'noopener noreferrer' : '';
?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $link_url ) : ?>
		<a href="<?php echo esc_url( $link_url ); ?>"
			<?php
			if ( $link_target ) :
				?>
				target="<?php echo esc_attr( $link_target ); ?>"<?php endif; ?>
			<?php
			if ( $link_rel ) :
				?>
				rel="<?php echo esc_attr( $link_rel ); ?>"<?php endif; ?>>
			<figure>
				<?php if ( $img_id ) : ?>
					<?php
					echo wp_get_attachment_image(
						$img_id,
						'large',
						false,
						[
							'class' => 'about-one__image',
						]
					);
					?>
				<?php endif; ?>
				<?php if ( $text ) : ?>
					<figcaption class="about-one__caption"><?php echo wp_kses_post( $text ); ?></figcaption>
				<?php endif; ?>
			</figure>
			<div class="about-one__overlay"></div>
			<?php if ( $link_target ) : ?>
				<span class="screen-reader-text"><?php esc_html_e( '(otwiera w nowej karcie)', 'custom-block-package' ); ?></span>
			<?php endif; ?>
		</a>
	<?php else : ?>
		<figure>
			<?php if ( $img_id ) : ?>
				<?php
				echo wp_get_attachment_image(
					$img_id,
					'large',
					false,
					[
						'class' => 'about-one__image',
					]
				);
				?>
			<?php endif; ?>
			<?php if ( $text ) : ?>
				<figcaption class="about-one__caption"><?php echo wp_kses_post( $text ); ?></figcaption>
			<?php endif; ?>
			<div class="about-one__overlay"></div>
		</figure>
	<?php endif; ?>
</div>
