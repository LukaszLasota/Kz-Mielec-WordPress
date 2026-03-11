<?php
/**
 * Meeting List Block Render Template
 *
 * @package CustomBlockPackage
 *
 * @var array $attributes Block attributes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$meeting_post_type = 'meetings';
$numberposts       = isset( $attributes['numberposts'] ) ? (int) $attributes['numberposts'] : -1;
$block_title       = isset( $attributes['blockTitle'] ) ? $attributes['blockTitle'] : __( 'Nasze spotkania', 'custom-block-package' );

// Cache inner content only; wrapper is rendered fresh for anchor support.
$cache_key    = \CustomBlockPackage\Cache\BlockCache::key( \CustomBlockPackage\Cache\BlockCache::MEETING_LIST_PREFIX, $attributes );
$cache_expire = \CustomBlockPackage\Cache\BlockCache::TTL;

$cached_output = get_transient( $cache_key );
if ( false === $cached_output ) {
	ob_start();
	?>
	<?php if ( ! empty( $block_title ) ) : ?>
		<h2><?php echo esc_html( $block_title ); ?></h2>
	<?php endif; ?>

	<div class="flipping-cards">
		<?php
		$meetings = get_posts(
			array(
				'post_type'              => $meeting_post_type,
				'numberposts'            => $numberposts,
				'meta_key'               => 'priority',
				'orderby'                => 'meta_value_num',
				'order'                  => 'ASC',
				'update_post_term_cache' => false,
				'cache_results'          => false,
			)
		);

		if ( ! empty( $meetings ) ) :
			foreach ( $meetings as $meeting ) :
				$meeting_title = get_the_title( $meeting->ID );
				$day_hour      = get_field( 'day_hour', $meeting->ID );
				$place         = get_field( 'place', $meeting->ID );
				$content       = apply_filters( 'the_content', $meeting->post_content );
				?>
				<div class="flipping-cards__card" tabindex="0" role="button" aria-expanded="false">
					<div class="flipping-cards__card-inner">
						<div class="flipping-cards__card-front">
							<h3><?php echo esc_html( $meeting_title ); ?></h3>

							<?php if ( $day_hour ) : ?>
								<p><?php echo esc_html( $day_hour ); ?></p>
							<?php else : ?>
								<p><?php esc_html_e( 'Brak godziny', 'custom-block-package' ); ?></p>
							<?php endif; ?>

							<?php if ( $place ) : ?>
								<p><?php echo esc_html( $place ); ?></p>
							<?php else : ?>
								<p><?php esc_html_e( 'Brak miejsca', 'custom-block-package' ); ?></p>
							<?php endif; ?>
							<p class="flipping-cards__more"><?php esc_html_e( 'Zobacz więcej', 'custom-block-package' ); ?></p>
						</div>

						<div class="flipping-cards__card-back" aria-hidden="true">
							<div><?php echo wp_kses_post( $content ); ?></div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Brak dostępnych spotkań.', 'custom-block-package' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
	$cached_output = ob_get_clean();
	set_transient( $cache_key, $cached_output, $cache_expire );
}

// Wrapper rendered outside cache so anchor id is always present.
$wrapper_extra = array( 'class' => 'meetings' );
if ( ! empty( $attributes['anchor'] ) ) {
	$wrapper_extra['id'] = $attributes['anchor'];
}
?>
<div <?php echo get_block_wrapper_attributes( $wrapper_extra ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $cached_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
