<?php
/**
 * Template Name: Szablon dla stron
 * The template for displaying all pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>

<main id="primary" class="site-main page__content-container section-block">
	<section>
		<?php the_content(); ?>
	</section>
</main>

<?php
get_footer();
