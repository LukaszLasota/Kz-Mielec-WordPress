<!doctype html>
<html <?php language_attributes(); ?> class="no-js">
	<head>
		<meta charset="<?php bloginfo('charset'); ?>">
		<title><?php wp_title(''); ?><?php if(wp_title('', false)) { echo ' :'; } ?> <?php bloginfo('name'); ?></title>

		<link href="//www.google-analytics.com" rel="dns-prefetch">
        <link href="<?php echo get_template_directory_uri(); ?>/img/icons/favicon.png" rel="shortcut icon">
        
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		
		
		
		<?php wp_head(); ?>
		
		<script>
        // conditionizr.com
        // configure environment tests
        conditionizr.config({
            assets: '<?php echo get_template_directory_uri(); ?>',
            tests: {}
		});
		
		</script>
		<!-- Global site tag (gtag.js) - Google Analytics -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=UA-153597386-1"></script>
		<script>
 			 window.dataLayer = window.dataLayer || [];
  			function gtag(){dataLayer.push(arguments);}
  			gtag('js', new Date());

  			gtag('config', 'UA-153597386-1');
		</script>

	</head>
	<body <?php body_class(); ?>>

		<!-- wrapper -->
		<div class="wrapper">

			<!-- header -->
			<header class="header clear" role="banner" id="zero">
				
			<div class="menu">
				
				<h1 class="logo" title="Kościół Zielonoświątkowy Zbór w Mielcu"><a href="<?php echo home_url(); ?>">
					<figure><img src="<?php echo get_template_directory_uri(); ?>/img/logo.png" alt="Kościół Zielonoświątkowy Zbór w Mielcu"></figure>
				</a></h1>

				<div class="three col">
					<div class="hamburger" id="hamburger-1">
						<span class="line"></span>
						<span class="line"></span>
						<span class="line"></span>
					</div>
				</div>
				
				<nav class="nav">
					<ul class="nav__ul">
						<li class="nav__li"><a href="<?php echo home_url('/#one'); ?>">Aktualności</a></li>
						<li class="nav__li"><a href="<?php echo home_url('/#two'); ?>">Zaplanuj wizytę</a></li>
						<li class="nav__li"><a href="<?php echo home_url('/#three'); ?>">W co i jak wierzymy</a></li>
						<li class="nav__li"><a href="<?php echo home_url('/#four'); ?>">Znajdź nas</a></li>
					</ul>
				</nav>
					
			</div>
			
			<div class="menu fixed">
				<div class="three col">
					<div class="hamburger" id="hamburger-1">
						<span class="line"></span>
						<span class="line"></span>
						<span class="line"></span>
					</div>
				</div>
			
				<nav class="nav">
					<ul class="nav__ul">
						<li class="nav__li"><a href="<?php echo home_url('/#one'); ?>">Aktualności</a></li>
						<li class="nav__li"><a href="<?php echo home_url('/#two'); ?>">Zaplanuj wizytę</a></li>
						<li class="nav__li nav__li--logo">
							<a href="<?php echo home_url(); ?>">
								<figure><img src="<?php echo get_template_directory_uri(); ?>/img/logo.png" alt="Kościół Zielonoświątkowy Zbór w Mielcu"></figure>
							</a>
						</li>
						<li class="nav__li"><a href="<?php echo home_url('/#three'); ?>">W co i jak wierzymy</a></li>
						<li class="nav__li"><a href="<?php echo home_url('/#four'); ?>">Znajdź nas</a></li>
					</ul>
				</nav>
					
			</div>
			</header>
			<!-- /header -->
