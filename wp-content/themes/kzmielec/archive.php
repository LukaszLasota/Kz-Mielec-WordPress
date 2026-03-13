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
	<h1 class="archive-title"><?php echo esc_html( get_the_archive_title() ); ?></h1>
	<?php get_template_part( 'template-parts/content', 'posts' ); ?>
</main>

<?php get_footer(); ?>
