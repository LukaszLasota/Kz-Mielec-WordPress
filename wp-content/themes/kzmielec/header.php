<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; } ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preload" href="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/webfont/cinzel-v26-latin-ext.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<a class="skip-link" href="#primary"><?php esc_html_e( 'Przejdz do tresci', 'kzmielec' ); ?></a>
<div class="wrapper">
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
$logo_tag = is_front_page() ? 'h1' : 'p';
$logo_img = '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" class="site-logo__image" fetchpriority="high" decoding="async"';
if ( $logo_width && $logo_height ) {
	$logo_img .= ' width="' . esc_attr( (string) $logo_width ) . '" height="' . esc_attr( (string) $logo_height ) . '"';
}
$logo_img .= '>';

$allowed_img = array(
	'img' => array(
		'src'           => true,
		'alt'           => true,
		'class'         => true,
		'width'         => true,
		'height'        => true,
		'fetchpriority' => true,
		'decoding'      => true,
	),
);

$menu_items = array();
$current_url = trailingslashit( home_url( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) ) );
if ( has_nav_menu( 'primary' ) ) {
	$locations  = get_nav_menu_locations();
	$menu_obj   = wp_get_nav_menu_object( $locations['primary'] );
	if ( $menu_obj ) {
		$menu_items = wp_get_nav_menu_items( $menu_obj->term_id );
	}
}
?>

<header class="site-header" id="zero">
	<div class="menu">
		<<?php echo tag_escape( $logo_tag ); ?> class="site-logo">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="site-logo__link" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — <?php esc_attr_e( 'strona glowna', 'kzmielec' ); ?>">
				<?php echo wp_kses( $logo_img, $allowed_img ); ?>
			</a>
		</<?php echo tag_escape( $logo_tag ); ?>>

		<div class="three">
			<button class="hamburger" id="hamburger-main" aria-controls="primary-menu" aria-expanded="false">
				<span class="hamburger__sr-only"><?php esc_html_e( 'Otworz/zamknij menu', 'kzmielec' ); ?></span>
				<span class="line" aria-hidden="true"></span>
				<span class="line" aria-hidden="true"></span>
				<span class="line" aria-hidden="true"></span>
			</button>
		</div>

		<nav class="nav" id="primary-menu" aria-label="<?php esc_attr_e( 'Nawigacja glowna', 'kzmielec' ); ?>">
			<?php if ( $menu_items ) : ?>
				<ul class="nav__ul">
					<?php foreach ( $menu_items as $item ) :
						$is_current = ( trailingslashit( $item->url ) === $current_url );
					?>
						<li class="nav__li"><a href="<?php echo esc_url( $item->url ); ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $item->title ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</nav>
	</div>

	<div class="menu fixed" aria-hidden="true">
		<div class="three">
			<button class="hamburger" aria-controls="fixed-menu" aria-expanded="false" tabindex="-1">
				<span class="hamburger__sr-only"><?php esc_html_e( 'Otworz/zamknij menu', 'kzmielec' ); ?></span>
				<span class="line" aria-hidden="true"></span>
				<span class="line" aria-hidden="true"></span>
				<span class="line" aria-hidden="true"></span>
			</button>
		</div>

		<nav class="nav" id="fixed-menu" aria-label="<?php esc_attr_e( 'Menu przyklejone', 'kzmielec' ); ?>">
			<?php if ( $menu_items ) : ?>
				<ul class="nav__ul">
					<?php
					$half = (int) ceil( count( $menu_items ) / 2 );
					$i    = 0;
					foreach ( $menu_items as $item ) :
						if ( $i === $half ) :
							?>
							<li class="nav__li nav__li--logo" aria-hidden="true">
								<a href="<?php echo esc_url( home_url( '/' ) ); ?>" tabindex="-1">
									<?php echo wp_kses( $logo_img, $allowed_img ); ?>
								</a>
							</li>
							<?php
						endif;
						?>
						<li class="nav__li"><a href="<?php echo esc_url( $item->url ); ?>" tabindex="-1"><?php echo esc_html( $item->title ); ?></a></li>
						<?php
						$i++;
					endforeach;
					?>
				</ul>
			<?php endif; ?>
		</nav>
	</div>
</header>
