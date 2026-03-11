<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; } ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<link rel="preload" href="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/webfont/cinzel-v26-latin-ext.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<header class="site-header">
	<div class="site-header__container">
		<nav class="site-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary Navigation', 'church' ); ?>">
			<?php $logo_tag = is_front_page() ? 'h1' : 'p'; ?>
			<<?php echo tag_escape( $logo_tag ); ?> class="site-logo" title="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="site-logo__link">
					<?php
					$logo_id     = get_option( 'my_custom_logo_setting_id' );
					$logo_url    = '';
					$logo_width  = '';
					$logo_height = '';
					if ( $logo_id ) {
						$logo_size = 'thumbnail';
						$logo_data = wp_get_attachment_image_src( $logo_id, $logo_size );
						if ( $logo_data ) {
							$logo_url    = $logo_data[0];
							$logo_width  = $logo_data[1];
							$logo_height = $logo_data[2];
						}
					} else {
											$logo_url = get_option( 'my_custom_logo_setting' );
					}
					?>
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="site-logo__image" fetchpriority="high" decoding="async"
					<?php
					if ( $logo_width && $logo_height ) {
						echo 'width="' . esc_attr( (string) $logo_width ) . '" height="' . esc_attr( (string) $logo_height ) . '"';}
					?>
					>
				</a>
			</<?php echo tag_escape( $logo_tag ); ?>>

			<button class="hamburger" aria-controls="primary-menu" aria-expanded="false">
				<span class="hamburger__sr-only"><?php esc_html_e( 'Open/close menu', 'church' ); ?></span>
				<span class="hamburger__box">
					<span class="hamburger__inner"></span>
				</span>
			</button>

			<div class="site-nav__navigation" id="primary-menu" aria-hidden="true">
				<?php
				if ( has_nav_menu( 'primary' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'menu_id'        => 'primary-menu-items',
							'menu_class'     => 'site-nav__menu',
							'container'      => '',
						)
					);
				}
				?>
			</div>
		</nav>
	</div>
</header>
