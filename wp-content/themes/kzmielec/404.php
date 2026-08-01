<?php
/**
 * The template for displaying 404 (not found) pages.
 *
 * @package Kzmielec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main page__content-container section-block error-404">
	<section class="error-404__inner">
		<p class="error-404__code" aria-hidden="true">404</p>
		<h1 class="page__title error-404__title">
			<?php esc_html_e( 'Nie znaleziono strony', 'kzmielec' ); ?>
		</h1>
		<p class="error-404__text">
			<?php esc_html_e( 'Strona, której szukasz, nie istnieje lub została przeniesiona. Spróbuj wyszukać poniżej albo wróć na stronę główną.', 'kzmielec' ); ?>
		</p>

		<?php get_search_form(); ?>

		<div class="error-404__actions">
			<a class="error-404__button" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span><?php esc_html_e( 'Wróć na stronę główną', 'kzmielec' ); ?></span>
			</a>
		</div>
	</section>
</main>

<?php get_footer(); ?>
