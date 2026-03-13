<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; } ?>
</div><!-- /.wrapper -->
<footer class="footer" role="contentinfo">
	<div class="footer__inner">
		<div class="footer__copyright">
			<?php if ( is_active_sidebar( 'footer-text' ) ) : ?>
				<?php dynamic_sidebar( 'footer-text' ); ?>
			<?php else : ?>
				<p>
					<?php
					printf(
						/* translators: %1$s: site name, %2$s: current year */
						wp_kses(
							__( 'Copyright %1$s. Wszelkie prawa zastrzezone &copy; %2$s.', 'kzmielec' ),
							array( 'span' => array() )
						),
						esc_html( get_bloginfo( 'name' ) ),
						esc_html( gmdate( 'Y' ) )
					);
					?>
				</p>
			<?php endif; ?>
		</div>

		<nav class="footer__social" aria-label="<?php esc_attr_e( 'Social media', 'kzmielec' ); ?>">
			<?php if ( is_active_sidebar( 'footer-social' ) ) : ?>
				<?php dynamic_sidebar( 'footer-social' ); ?>
			<?php else : ?>
				<?php
				$social_links = array(
					array(
						'option' => 'kzmielec_social_facebook',
						'title'  => 'Facebook',
						'icon'   => 'stopka-f.png',
					),
					array(
						'option' => 'kzmielec_social_instagram',
						'title'  => 'Instagram',
						'icon'   => 'stopka-i.png',
					),
					array(
						'option' => 'kzmielec_social_youtube',
						'title'  => 'YouTube',
						'icon'   => 'stopka-y.png',
					),
					array(
						'option' => 'kzmielec_social_flickr',
						'title'  => 'Flickr',
						'icon'   => 'stopka-fl.png',
					),
				);

				foreach ( $social_links as $link ) :
					$url = get_option( $link['option'], '' );
					if ( $url ) :
						?>
						<a href="<?php echo esc_url( $url ); ?>"
							aria-label="<?php
								printf(
									/* translators: %s: social media name */
									esc_attr__( '%s (otworzy nowe okno)', 'kzmielec' ),
									esc_attr( $link['title'] )
								);
							?>"
							target="_blank"
							rel="noopener noreferrer">
							<img class="footer__image" src="<?php echo esc_url( get_template_directory_uri() . '/assets/media/' . $link['icon'] ); ?>" alt="" width="32" height="32" loading="lazy">
						</a>
						<?php
					endif;
				endforeach;
				?>
			<?php endif; ?>
		</nav>
	</div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
