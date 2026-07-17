<?php
/**
 * Template Name: Strona wiary
 *
 * Renders a belief subpage with auto-generated heading, hero tile,
 * content, scroll arrow, and bottom navigation tiles.
 *
 * @package Kzmielec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Admin\BeliefPageMeta;

get_header();
?>

<main id="primary" class="site-main is-layout-constrained page-belief">

	<?php
	while ( have_posts() ) :
		the_post();
		$belief_post_id = (int) get_the_ID();
		$base_image     = get_the_post_thumbnail(
			$belief_post_id,
			'medium',
			array(
				'alt'   => '',
				'class' => 'page-belief__hero-image--one',
			)
		);
		$hover_image_id = (int) get_post_meta( $belief_post_id, BeliefPageMeta::META_HOVER_IMAGE, true );
		$hover_image    = $hover_image_id
			? wp_get_attachment_image(
				$hover_image_id,
				'medium',
				false,
				array(
					'alt'   => '',
					'class' => 'page-belief__hero-image--two',
				)
			)
			: '';
		?>

		<h2 class="wp-block-heading is-style-section-line">
			<?php esc_html_e( 'W co i jak wierzymy', 'kzmielec' ); ?>
		</h2>

		<div class="page-belief__hero">
			<span class="page-belief__hero-image" aria-hidden="true">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe WP-generated markup.
				echo $base_image;
				?>
				<span class="page-belief__hero-bg"></span>
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe WP-generated markup.
				echo $hover_image;
				?>
			</span>
			<h1 class="page-belief__title"><?php the_title(); ?></h1>
		</div>

		<article class="page-belief__content">
			<?php the_content(); ?>
		</article>

		<div class="page-belief__separator">
			<?php
			$belief_nav_label = esc_js( __( 'Przewiń do nawigacji wiary', 'kzmielec' ) );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block output.
			echo do_blocks(
				sprintf(
					'<!-- wp:custom-block-package/scroll-arrow {"targetId":"belief-nav","direction":"down","ariaLabel":"%s"} /-->',
					$belief_nav_label
				)
			);
			?>
		</div>

		<div id="belief-nav">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block output.
			echo do_blocks( '<!-- wp:custom-block-package/navigable-tiles {"dataSource":"beliefs","columns":4,"highlightCurrent":true,"className":"pattern-page-belief"} /-->' );
			?>
		</div>

	<?php endwhile; ?>

</main>

<?php
get_footer();
