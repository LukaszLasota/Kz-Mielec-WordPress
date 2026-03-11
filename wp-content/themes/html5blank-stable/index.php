<?php get_header(); ?>

	<main role="main">
		<section class="banner">
		
		<div class="banner_image">
			<figure class="banner_img-first">
				<picture >
                	<source srcset="<?php echo get_template_directory_uri(); ?>/img/1m.jpg" media="(max-width: 480px)">
                	<source srcset="<?php echo get_template_directory_uri(); ?>/img/1s.jpg" media="(max-width: 800px)">
                	<img srcset="<?php echo get_template_directory_uri(); ?>/img/1.jpg" alt="Zdjęcie górka Cyranowska">
				</picture>
			</figure>
			<figure class="banner_img-secend"><img srcset="<?php echo get_template_directory_uri(); ?>/img/3.png" alt="Bliscy Boga, Sobie, Innym"></figure>
		</div>
			<div class="center__main">
				<div class="black-circle">
					<a href="#one">
						<figure><img srcset="<?php echo get_template_directory_uri(); ?>/img/strzalki/3.png" alt=""></figure>
					</a>
				</div>
			</div>
		</section>
		<section class="media" id="one">
			<h2 class="section-head"><span>Aktualności</span></h2>
			<div class="media__one">
				<iframe class="fb-big"src="https://www.facebook.com/plugins/page.php?href=https://www.facebook.com/Kzmielec/&#038;tabs=timeline&#038;width=600&#038;height=700&#038;small_header=true&#038;adapt_container_width=true&#038;hide_cover=false&#038;show_facepile=true&#038;appId=134471550057034" width="600" height="700" style="border:none;overflow:hidden" scrolling="no" data-adapt-container-width="true" frameborder="0" allowTransparency="true" allow="encrypted-media"></iframe>

				<iframe class="fb-small"src="https://www.facebook.com/plugins/page.php?href=https://www.facebook.com/Kzmielec/&#038;tabs=timeline&#038;width=320&#038;height=700&#038;small_header=true&#038;adapt_container_width=true&#038;hide_cover=false&#038;show_facepile=true&#038;appId=134471550057034" width="320" height="700" style="border:none;overflow:hidden" scrolling="no" data-adapt-container-width="true" frameborder="0" allowTransparency="true" allow="encrypted-media"></iframe>
				</div>
			
			<div class="media__one">
			<div class="sidebar-widget">
				<?php if(!function_exists('dynamic_sidebar') || !dynamic_sidebar('widget-area-1')) ?>
			</div>
			</div>
			<div class="center__main">
				<div class="black-circle">
					<a href="#two">
						<figure><img srcset="<?php echo get_template_directory_uri(); ?>/img/strzalki/3.png" alt=""></figure>
					</a>
				</div>
			</div>
		</section>
		<section class="vb" id="two">
				<h2 class="section-head"><span>Zaplanuj wizytę</span></h2>
				<div class="vb__item">
					<a href="<?php echo home_url( '/zaplanuj-wizyte/#10' ); ?>">
					<figure><img srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/1.png" alt=""></figure>
					</a>
					<p class="vb__paragraph">Nabożeństwa główne</p>
					<p class="vb__paragraph">Niedziela 10.30</p>
				</div>
				
				<div class="vb__item">
					<a href="<?php echo home_url( '/zaplanuj-wizyte/#11' ); ?>">
					<div class="vb__image">
					<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/2.png" alt=""></figure>
					<figure ><img  class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/2s.png" alt=""></figure>
					</div>
					</a>
					<p class="vb__paragraph">Mała kawka</p>
					
				</div>

				<!-- <div class="vb__item">
					<a href="<?php echo home_url( '/zaplanuj-wizyte/#12' ); ?>">
					<div class="vb__image">
					<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/3.png" alt=""></figure>
					<figure ><img  class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/3s.png" alt=""></figure>
					</div>
					</a>
					<p class="vb__paragraph">Klęcząc zwyciężamy</p>
					<p class="vb__paragraph">Piątek 18.00</p>
				</div> -->
				<div class="vb__item">
					<a href="<?php echo home_url( '/zaplanuj-wizyte/#12' ); ?>">
					<div class="vb__image">
					<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/4.png" alt=""></figure>
					<figure ><img  class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/4s.png" alt=""></figure>
					</div>
					</a>
					<p class="vb__paragraph">studium Słowa i modlitwa</p>
					<p class="vb__paragraph">Piątek 18.00</p>
				</div>
				<!-- <div class="vb__item">
					<a href="<?php echo home_url( '/zaplanuj-wizyte/#14' ); ?>">
					<div class="vb__image">
						<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/5.png" alt=""></figure>
						<figure ><img  class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/5s.png" alt=""></figure>
						</div>
					</a>
					<p class="vb__paragraph">kurs alfa</p>
				</div>
				<div class="vb__item">
					<a href="<?php echo home_url( '/zaplanuj-wizyte/#15' ); ?>">
					<div class="vb__image">
						<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/6.png" alt=""></figure>
						<figure ><img  class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/6s.png" alt=""></figure>
						</div>
					</a>
					<p class="vb__paragraph">nowe życie</p>
				</div>
				<div class="vb__item">
					<a href="<?php echo home_url( '/zaplanuj-wizyte/#16' ); ?>">
					<div class="vb__image">
						<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/7.png" alt=""></figure>
						<figure ><img  class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/7s.png" alt=""></figure>
						</div>
					</a>
					<p class="vb__paragraph">więcej niż fan</p>
				</div>
				<div class="vb__item">
					<a href="<?php echo home_url( '/zaplanuj-wizyte/#17' ); ?>">
					<div class="vb__image">
						<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/8.png" alt=""></figure>
						<figure ><img  class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wizyty/8s.png" alt=""></figure>
						</div>
					</a>
					<p class="vb__paragraph">projekt prawda</p> -->
				<!-- </div> -->

				<div class="center__main">
				<div class="black-circle">
					<a href="#three">
						<figure><img srcset="<?php echo get_template_directory_uri(); ?>/img/strzalki/3.png" alt=""></figure>
					</a>
				</div>
				</div>
		</section>	
		<section class="vb" id="three">
				<h2 class="section-head"><span>W co i jak wierzymy</span></h2>
				<div class="vb__item">
					<a href="<?php echo home_url( '/w-co-wierzymy' ); ?>">
					<div class="vb__image">
					<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/1.png" alt=""></figure>
					<div class="vb__image--black"></div>
					<figure><img class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/1s.png" alt=""></figure>
					</div>
					</a>
					<p class="vb__paragraph">w co wierzymy</p>
				</div>
				<div class="vb__item">
					<a href="<?php echo home_url( '/misja' ); ?>">
					<div class="vb__image">
					<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/2.png" alt=""></figure>
					<div class="vb__image--black"></div>
					<figure><img class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/2s.png" alt=""></figure>
					</div>
					</a>
					<p class="vb__paragraph">misja</p>
				</div>
				<div class="vb__item">
					<a href="<?php echo home_url( '/wizja' ); ?>">	
					<div class="vb__image">
					<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/3.png" alt=""></figure>
					<div class="vb__image--black"></div>
					<figure><img class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/3s.png" alt=""></figure>
					</div>
					</a>
					<p class="vb__paragraph">wizja</p>
				</div>
				<div class="vb__item">
				   <a href="<?php echo home_url( '/wartosci' ); ?>">
					<div class="vb__image">
					<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/4.png" alt=""></figure>
					<div class="vb__image--black"></div>
					<figure ><img  class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/4s.png" alt=""></figure>
					</div>
					</a>
					<p class="vb__paragraph">wartości</p>
				</div>
				<div class="vb__item">
					<a href="<?php echo home_url( '/historia' ); ?>">
					<div class="vb__image">
					<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/5.png" alt=""></figure>
					<div class="vb__image--black"></div>
					<figure ><img  class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/5s.png" alt=""></figure>
					</div>
					</a>
					<p class="vb__paragraph">historia</p>
				</div>
				<div class="vb__item">
					<a href="<?php echo home_url( '/roznica-wyznan' ); ?>">
					<div class="vb__image">
					<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/6.png" alt=""></figure>
					<div class="vb__image--black"></div>
					<figure ><img  class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/6s.png" alt=""></figure>
					</div>
					</a>
					<p class="vb__paragraph">różnice wyznań</p>
				</div>
				<div class="vb__item">
					<a href="<?php echo home_url( '/prawo' ); ?>">
					<div class="vb__image">
					<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/7.png" alt=""></figure>
					<div class="vb__image--black"></div>
					<figure ><img  class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/7s.png" alt=""></figure>
					</div>
					</a>
					<p class="vb__paragraph">prawo</p>
				</div>
				<div class="vb__item">
					<a href="<?php echo home_url( '/rodo' ); ?>">
					<div class="vb__image">
					<figure><img class="vb__image--one" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/8.png" alt=""></figure>
					<div class="vb__image--black"></div>
					<figure ><img  class="vb__image--two" srcset="<?php echo get_template_directory_uri(); ?>/img/wiara/8s.png" alt=""></figure>
					</div>
					</a>
					<p class="vb__paragraph">RODO</p>
				</div>
				
				<div class="center">
				<div class="black-circle">
					<a href="#four">
						<figure><img srcset="<?php echo get_template_directory_uri(); ?>/img/strzalki/3.png" alt=""></figure>
					</a>
				</div>
				</div>
		</section>
		<section class="find" id="four">
				<h2 class="section-head"><span>Znajdź nas</span></h2>
				
				<p class="find__paragraph">ul. Przemysłowa 2, 39-300 Mielec</p>
				<p class="find__paragraph">tel.: 669 189 992 - pastor Zboru, Dariusz R. Hapoń </br> Uwaga: z tego numeru nie odczytujemy smsów. </br>W celu kontaktu pisemnego prosimy użyć poczty email lub kontaktu ze Zborem poprzez messenger (facebook).</p>
				
				<p class="find__paragraph">NIP: 817-18-40-461</p>
				<p class="find__paragraph">email: <a href="mailto:zbor@kzmielec.pl">zbor@kzmielec.pl</a></p>
				<p class="find__paragraph find__paragraph-last">konto: 63 8642 1168 2016 6812 9206 0001</p>
				
				<div id="map"></div>
				
				<div class="center__main">
				<div class="black-circle">
					<a href="#zero">
						<figure><img srcset="<?php echo get_template_directory_uri(); ?>/img/strzalki/4.png" alt=""></figure>
					</a>
				</div>
				</div>

				

				
    


		</section>
			


	</main>



<?php get_footer(); ?>
