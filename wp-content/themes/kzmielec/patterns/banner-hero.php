<?php
/**
 * Title: Banner hero
 * Slug: kzmielec/banner-hero
 * Categories: featured
 * Description: Full-width hero banner with responsive background photo, motto overlay and scroll arrow.
 */

$theme_uri = get_template_directory_uri();
?>

<!-- wp:html -->
<section class="pattern-banner-hero">
	<div class="pattern-banner-hero__image">
		<figure class="pattern-banner-hero__photo">
			<picture>
				<source
					srcset="<?php echo esc_url( $theme_uri ); ?>/assets/media/1m.jpg"
					media="(max-width: 480px)"
					width="673"
					height="926"
				>
				<source
					srcset="<?php echo esc_url( $theme_uri ); ?>/assets/media/1s.jpg"
					media="(max-width: 800px)"
					width="1135"
					height="610"
				>
				<img
					src="<?php echo esc_url( $theme_uri ); ?>/assets/media/1.jpg"
					alt="<?php esc_attr_e( 'Zdjęcie górki Cyranowskiej', 'kzmielec' ); ?>"
					width="1998"
					height="1073"
					fetchpriority="high"
				>
			</picture>
		</figure>
		<figure class="pattern-banner-hero__motto">
			<img
				src="<?php echo esc_url( $theme_uri ); ?>/assets/media/3.png"
				alt="<?php esc_attr_e( 'Bliscy Boga, Sobie, Innym', 'kzmielec' ); ?>"
				width="1968"
				height="805"
				loading="lazy"
			>
		</figure>
	</div>
	<div class="center__main">
		<div class="black-circle">
			<a href="#one" aria-label="<?php esc_attr_e( 'Przewiń do treści', 'kzmielec' ); ?>">
				<figure>
					<img
						src="<?php echo esc_url( $theme_uri ); ?>/assets/media/strzalki/3.png"
						alt=""
						width="365"
						height="365"
						loading="lazy"
					>
				</figure>
			</a>
		</div>
	</div>
</section>
<!-- /wp:html -->
