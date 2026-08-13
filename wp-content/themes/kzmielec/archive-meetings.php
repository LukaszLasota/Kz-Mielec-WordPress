<?php
/**
 * Archive template for CPT meetings.
 *
 * Renders full descriptions of all meetings with anchor IDs
 * for cross-linking from homepage tiles.
 *
 * @package Kzmielec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CustomBlockPackage\Admin\MeetingMeta;
use CustomBlockPackage\Services\MeetingSchedule;

get_header();
?>

<main id="primary" class="site-main is-layout-constrained archive-meetings pattern-archive-meetings">

	<h1 class="archive-meetings__sr-heading"><?php esc_html_e( 'Zaplanuj wizytę', 'kzmielec' ); ?></h1>

	<?php
	$archive_query = new \WP_Query(
		array(
			'post_type'      => 'meetings',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	if ( $archive_query->have_posts() ) :
		$collected_meetings = array();

		while ( $archive_query->have_posts() ) {
			$archive_query->the_post();
			$collected_meetings[] = (int) get_the_ID();
		}

		$total = count( $collected_meetings );

		foreach ( $collected_meetings as $index => $meeting_id ) :
			$post_obj = get_post( $meeting_id );
			if ( ! $post_obj instanceof \WP_Post ) {
				continue;
			}

			// Set the global post so the_title()/the_content() resolve to THIS
			// meeting. setup_postdata() alone does not update $GLOBALS['post'],
			// which previously left every item showing the last meeting's title.
			$GLOBALS['post'] = $post_obj; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $post_obj );

			// $day_hour is built from the shared weekday/time pair in the language
			// of this page, rather than read from the derived meta of the same
			// name, so the visible text cannot lag behind an edit. That stored
			// copy is an index for site search and nothing else.
			$anchor         = $post_obj->post_name;
			$day_hour       = MeetingSchedule::label( $meeting_id );
			$hover_image_id = (int) get_post_meta( $meeting_id, MeetingMeta::META_HOVER_IMAGE, true );
			$base_image     = get_the_post_thumbnail(
				$meeting_id,
				'medium',
				array(
					'alt'   => '',
					'class' => 'archive-meetings__image--one',
				)
			);
			$hover_image    = $hover_image_id
				? wp_get_attachment_image(
					$hover_image_id,
					'medium',
					false,
					array(
						'alt'   => '',
						'class' => 'archive-meetings__image--two',
					)
				)
				: '';

			$next_anchor = '';
			if ( isset( $collected_meetings[ $index + 1 ] ) ) {
				$next_post   = get_post( $collected_meetings[ $index + 1 ] );
				$next_anchor = $next_post instanceof \WP_Post ? $next_post->post_name : '';
			}
			?>

			<article id="<?php echo esc_attr( $anchor ); ?>" class="archive-meetings__item">

				<div class="archive-meetings__hero">
					<span class="archive-meetings__hero-image" aria-hidden="true">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe WP-generated markup.
						echo $base_image;
						?>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe WP-generated markup.
						echo $hover_image;
						?>
					</span>
					<h2 class="archive-meetings__title"><?php the_title(); ?></h2>
					<?php if ( '' !== $day_hour ) : ?>
						<p class="archive-meetings__meta"><?php echo esc_html( $day_hour ); ?></p>
					<?php endif; ?>
				</div>

				<div class="archive-meetings__content">
					<?php the_content(); ?>
				</div>

				<?php if ( $index < $total - 1 && '' !== $next_anchor ) : ?>
					<div class="archive-meetings__separator">
						<?php
						$block_html = sprintf(
							'<!-- wp:custom-block-package/scroll-arrow {"targetId":"%s","direction":"down"} /-->',
							esc_js( $next_anchor )
						);
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block output.
						echo do_blocks( $block_html );
						?>
					</div>
				<?php endif; ?>

			</article>

			<?php
		endforeach;

		wp_reset_postdata();
	endif;
	?>

	<div class="archive-meetings__back-top">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block output.
		echo do_blocks( '<!-- wp:custom-block-package/scroll-arrow {"targetId":"zero","direction":"up"} /-->' );
		?>
	</div>

</main>

<?php
get_footer();
