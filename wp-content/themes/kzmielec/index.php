<?php
/**
 *
 * The template for displaying blog posts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main">
	<div class="editor-content">
		<?php
		$posts_page_id = get_option( 'page_for_posts' );

		if ( $posts_page_id ) {
			$posts_page = get_post( $posts_page_id );
			if ( $posts_page ) {
				echo apply_filters( 'the_content', $posts_page->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content filter output.
			}
		}
		?>
	</div>

	<?php get_template_part( 'template-parts/content', 'posts' ); ?>

</main>

<?php get_footer(); ?>
