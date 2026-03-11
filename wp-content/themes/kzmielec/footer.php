<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; } ?>
<footer class="footer">
	<div class="footer__container container">

		<div class="footer__menu footer__menu--1">
			<?php
			if ( is_active_sidebar( 'footer-header-1' ) ) :
				?>
				<?php dynamic_sidebar( 'footer-header-1' ); ?>
				<?php
			else :
				?>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer-one',
						'fallback_cb'    => false,
					)
				);
				?>
			<?php endif; ?>
		</div>

		<div class="footer__menu footer__menu--2">
			<?php
			if ( is_active_sidebar( 'footer-header-2' ) ) :
				?>
				<?php dynamic_sidebar( 'footer-header-2' ); ?>
				<?php
			else :
				?>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer-two',
						'fallback_cb'    => false,
					)
				);
				?>
			<?php endif; ?>
		</div>

		<div class="footer__contact">
			<?php
			if ( is_active_sidebar( 'footer-header-3' ) ) :
				?>
				<?php dynamic_sidebar( 'footer-header-3' ); ?>
				<?php
			else :
				?>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer-three',
						'fallback_cb'    => false,
					)
				);
				?>
			<?php endif; ?>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
