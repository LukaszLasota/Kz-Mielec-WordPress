<?php
/**
 * Template Name: Strona wiary
 * Template for belief pages (misja, wizja, wartości, historia, etc.)
 *
 * Automatically adds:
 * - "W co i jak wierzymy" heading
 * - Current page icon (featured image circle)
 * - Content from Gutenberg editor
 * - Separator arrow
 * - 8-circle belief navigation (current page grayed out)
 *
 * @package Kzmielec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$current_page_id = get_the_ID();
$belief_pages    = get_option( 'kzmielec_belief_pages', array() );
$theme_uri       = get_template_directory_uri();
?>

<main id="primary" class="site-main">
	<section class="belief">
		<h2 class="section-head"><span><?php esc_html_e( 'W co i jak wierzymy', 'kzmielec' ); ?></span></h2>

		<?php // Current page icon (circle with featured image). ?>
		<div class="belief__current-icon">
			<div class="vb__item">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="vb__image">
						<figure>
							<img class="vb__image--one"
								src="<?php echo esc_url( (string) get_the_post_thumbnail_url( $current_page_id, 'medium' ) ); ?>"
								alt="<?php echo esc_attr( get_the_title() ); ?>">
						</figure>
						<div class="vb__image--black"></div>
						<?php
						$overlay_icon_id = get_post_meta( $current_page_id, 'belief_overlay_icon', true );
						if ( $overlay_icon_id ) :
							$overlay_url = wp_get_attachment_image_url( (int) $overlay_icon_id, 'medium' );
							if ( $overlay_url ) :
								?>
								<figure>
									<img class="vb__image--two"
										src="<?php echo esc_url( $overlay_url ); ?>"
										alt="">
								</figure>
								<?php
							endif;
						endif;
						?>
					</div>
				<?php endif; ?>
				<p class="vb__paragraph"><?php echo esc_html( get_the_title() ); ?></p>
			</div>
		</div>

		<?php // Page content from Gutenberg editor. ?>
		<div class="belief__content sub-text">
			<?php the_content(); ?>
		</div>

		<?php // Separator arrow. ?>
		<div class="center__main">
			<div class="black-circle">
				<a href="#belief-nav">
					<figure>
						<img src="<?php echo esc_url( $theme_uri . '/assets/media/strzalki/3.png' ); ?>" alt="" width="20" height="20">
					</figure>
				</a>
			</div>
		</div>

		<?php // 8-circle belief navigation. ?>
		<div class="belief__navigation vb" id="belief-nav">
			<?php
			if ( ! empty( $belief_pages ) ) :
				foreach ( $belief_pages as $page_id ) :
					$page_id    = (int) $page_id;
					$page       = get_post( $page_id );
					$is_current = ( $page_id === $current_page_id );

					if ( ! $page || 'publish' !== $page->post_status ) {
						continue;
					}

					$thumb_url = get_the_post_thumbnail_url( $page_id, 'medium' );
					?>
					<div class="vb__item<?php echo $is_current ? ' vb__item--current' : ''; ?>">
						<a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>">
							<div class="vb__image">
								<?php if ( $thumb_url ) : ?>
									<figure>
										<img class="vb__image--one"
											src="<?php echo esc_url( (string) $thumb_url ); ?>"
											alt="<?php echo esc_attr( get_the_title( $page_id ) ); ?>">
									</figure>
									<div class="vb__image--black"></div>
								<?php endif; ?>
								<?php
								// Show overlay icon only for non-current pages.
								if ( ! $is_current ) :
									$nav_overlay_id = get_post_meta( $page_id, 'belief_overlay_icon', true );
									if ( $nav_overlay_id ) :
										$nav_overlay_url = wp_get_attachment_image_url( (int) $nav_overlay_id, 'medium' );
										if ( $nav_overlay_url ) :
											?>
											<figure>
												<img class="vb__image--two"
													src="<?php echo esc_url( $nav_overlay_url ); ?>"
													alt="">
											</figure>
											<?php
										endif;
									endif;
								endif;
								?>
							</div>
						</a>
						<p class="vb__paragraph"><?php echo esc_html( get_the_title( $page_id ) ); ?></p>
					</div>
					<?php
				endforeach;
			endif;
			?>
		</div>
	</section>
</main>

<?php
get_footer();
