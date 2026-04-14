<?php
/**
 * The template for displaying the front page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>

<main id="primary" class="site-main is-layout-constrained">
	<?php the_content(); ?>
</main>

<?php
get_footer();
