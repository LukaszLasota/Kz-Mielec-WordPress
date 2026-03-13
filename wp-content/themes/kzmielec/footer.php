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
						wp_kses(
							/* translators: %1$s: site name, %2$s: current year */
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

		<?php if ( is_active_sidebar( 'footer-social' ) ) : ?>
			<nav class="footer__social" aria-label="<?php esc_attr_e( 'Social media', 'kzmielec' ); ?>">
				<?php dynamic_sidebar( 'footer-social' ); ?>
			</nav>
		<?php endif; ?>
	</div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
