<?php
/**
 * The template for displaying search results as a uniform card grid.
 *
 * @package Kzmielec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// Resolve the site logo once — used as a placeholder for results with no
// featured image, so every card keeps the same 16:9 media block.
$kz_logo_id  = get_option( 'my_custom_logo_setting_id' );
$kz_logo_url = '';
if ( $kz_logo_id ) {
	$kz_logo_src = wp_get_attachment_image_src( $kz_logo_id, 'medium' );
	if ( $kz_logo_src ) {
		$kz_logo_url = $kz_logo_src[0];
	}
} else {
	$kz_logo_url = (string) get_option( 'my_custom_logo_setting' );
}

// Friendly per-type labels for the result badge.
$kz_type_labels = array(
	'post'     => __( 'Aktualność', 'kzmielec' ),
	'page'     => __( 'Strona', 'kzmielec' ),
	'meetings' => __( 'Spotkanie', 'kzmielec' ),
);
?>

<main id="primary" class="site-main search-results-page">
	<header class="search-results-page__header">
		<h1 class="archive-title">
			<?php
			printf(
				/* translators: %s: search query (already quoted). */
				esc_html__( 'Wyniki wyszukiwania: %s', 'kzmielec' ),
				'„' . esc_html( get_search_query() ) . '”'
			);
			?>
		</h1>
		<?php get_search_form(); ?>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="search-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				$kz_type  = get_post_type();
				$kz_label = isset( $kz_type_labels[ $kz_type ] ) ? $kz_type_labels[ $kz_type ] : '';
				?>
				<article class="search-card">
					<a class="search-card__link" href="<?php the_permalink(); ?>">
						<div class="search-card__media">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php
								the_post_thumbnail(
									'blog-card',
									array(
										'class'   => 'search-card__image',
										'loading' => 'lazy',
									)
								);
								?>
							<?php else : ?>
								<span class="search-card__placeholder">
									<?php if ( $kz_logo_url ) : ?>
										<img src="<?php echo esc_url( $kz_logo_url ); ?>" alt="" loading="lazy" class="search-card__placeholder-img" />
									<?php endif; ?>
								</span>
							<?php endif; ?>
						</div>
						<div class="search-card__body">
							<?php if ( $kz_label ) : ?>
								<span class="search-card__type"><?php echo esc_html( $kz_label ); ?></span>
							<?php endif; ?>
							<h2 class="search-card__title"><?php the_title(); ?></h2>
							<p class="search-card__excerpt">
								<?php echo esc_html( wp_trim_words( get_the_excerpt(), 22, '…' ) ); ?>
							</p>
						</div>
					</a>
				</article>
			<?php endwhile; ?>
		</div>

		<?php
		the_posts_pagination(
			array(
				'mid_size'  => 1,
				'prev_text' => esc_html__( '« Poprzednia', 'kzmielec' ),
				'next_text' => esc_html__( 'Następna »', 'kzmielec' ),
			)
		);
		?>
	<?php else : ?>
		<p class="search-results-page__empty">
			<?php
			printf(
				/* translators: %s: search query (already quoted). */
				esc_html__( 'Brak wyników dla %s. Spróbuj innej frazy.', 'kzmielec' ),
				'„' . esc_html( get_search_query() ) . '”'
			);
			?>
		</p>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
