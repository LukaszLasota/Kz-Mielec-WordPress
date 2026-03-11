<?php
/**
 * The template for displaying the front page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>

<main id="primary" class="site-main">
	<section>
		<?php the_content(); ?>
	</section>
</main>

<?php
get_footer();
