<?php
/**
 * Data import helpers for Comparison of Religions.
 *
 * Contains comparison_of_religions_run_import() for hardcoded initial data and
 * comparison_of_religions_run_json_import() for importing from uploaded JSON files.
 *
 * Usage (WP-CLI only):
 *   wp eval-file wp-content/plugins/comparison-of-religions/tools/import-html-data.php
 *
 * @package ComparisonOfReligions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Allow running directly via WP-CLI (skip if loaded via require).
if ( defined( 'WP_CLI' ) && WP_CLI && ! defined( 'COR_REIMPORT' ) ) {
	comparison_of_religions_run_import();
}

/**
 * Run the full data import.
 */
function comparison_of_religions_run_import(): void {
	$taxonomy  = 'comparison_category';
	$post_type = 'comparison_topic';

	// ── 1. Create taxonomy terms ──
	$categories = [
		[
			'name'  => 'Bóg',
			'order' => 1,
		],
		[
			'name'  => 'Kanon wiary',
			'order' => 2,
		],
		[
			'name'  => 'Dar zbawienia',
			'order' => 3,
		],
		[
			'name'  => 'Liturgia',
			'order' => 4,
		],
		[
			'name'  => 'Sakramenty',
			'order' => 5,
		],
		[
			'name'  => 'Kościół',
			'order' => 6,
		],
		[
			'name'  => 'Kult i święta',
			'order' => 7,
		],
		[
			'name'  => 'Eschatologia',
			'order' => 8,
		],
		[
			'name'  => 'Wychowanie i etyka',
			'order' => 9,
		],
	];

	$term_ids = [];
	foreach ( $categories as $cat ) {
		$existing = term_exists( $cat['name'], $taxonomy );
		if ( $existing ) {
			$term_ids[ $cat['name'] ] = (int) $existing['term_id'];
		} else {
			$result = wp_insert_term( $cat['name'], $taxonomy );
			if ( is_wp_error( $result ) ) {
				echo 'ERROR creating term ' . esc_html( $cat['name'] ) . ': ' . esc_html( $result->get_error_message() ) . "\n";
				continue;
			}
			$term_ids[ $cat['name'] ] = (int) $result['term_id'];
		}
		update_term_meta( $term_ids[ $cat['name'] ], 'sort_order', $cat['order'] );
		echo 'Term: ' . esc_html( $cat['name'] ) . ' (ID: ' . esc_html( (string) $term_ids[ $cat['name'] ] ) . ")\n";
	}

	// ── 2. Define all comparison data ──
	$data = comparison_of_religions_get_data();

	// ── 3. Create CPT posts ──
	$created = 0;
	foreach ( $data as $entry ) {
		$cat_name = $entry['category'];
		if ( ! isset( $term_ids[ $cat_name ] ) ) {
			echo 'SKIP: category \'' . esc_html( $cat_name ) . "' not found\n";
			continue;
		}

		$post_id = wp_insert_post(
			[
				'post_type'   => $post_type,
				'post_title'  => $entry['title'],
				'post_status' => 'publish',
			]
		);

		if ( is_wp_error( $post_id ) ) {
			echo 'ERROR creating post \'' . esc_html( $entry['title'] ) . '\': ' . esc_html( $post_id->get_error_message() ) . "\n";
			continue;
		}

		// Assign taxonomy term.
		wp_set_object_terms( $post_id, $term_ids[ $cat_name ], $taxonomy );

		// Save churches meta.
		update_post_meta( $post_id, 'churches', $entry['churches'] );
		update_post_meta( $post_id, 'sort_order', $entry['sort_order'] );

		++$created;
		echo 'Post: ' . esc_html( $entry['title'] ) . ' (ID: ' . esc_html( (string) $post_id ) . ') → ' . esc_html( $cat_name ) . "\n";
	}

	echo "\nDone! Created " . esc_html( (string) $created ) . " posts.\n";
}

/**
 * Return all comparison data as a PHP array.
 *
 * Each entry: [category, title, sort_order, churches => [{church_name, description}]]
 */
function comparison_of_religions_get_data(): array {
	$k = 'Kościół Rzymskokatolicki';
	$z = 'Kościół Zielonoświątkowy';

	return [
		// ── Bóg ──
		[
			'category'   => 'Bóg',
			'title'      => 'Bóg',
			'sort_order' => 1,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Trójca Święta. Bóg Trójjedyny: Ojciec, Syn i Duch</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Trójca Święta. Bóg Trójjedyny: Ojciec, Syn i Duch</p>',
				],
			],
		],
		[
			'category'   => 'Bóg',
			'title'      => 'Ojciec',
			'sort_order' => 2,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Źródło życia, Suwerenny, Miłosierny, Sprawiedliwy</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Źródło życia, Suwerenny, Miłosierny, Sprawiedliwy</p>',
				],
			],
		],
		[
			'category'   => 'Bóg',
			'title'      => 'Jezus',
			'sort_order' => 3,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Mesjasz, Pan, Zbawiciel Jednorodzony Syn Boży. Jako jedna osoba posiada dwie natury, które się jednak nie mieszają. Jest w pełni Bogiem i w pełni Człowiekiem.</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Mesjasz, Pan, Zbawiciel Jednorodzony Syn Boży. Jako jedna osoba posiada dwie natury, które się jednak nie mieszają. Jest w pełni Bogiem i w pełni Człowiekiem.</p>',
				],
			],
		],
		[
			'category'   => 'Bóg',
			'title'      => 'Duch Święty',
			'sort_order' => 4,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Osoba Boska, godna czci; Zesłanie Ducha Świętego w dniu Pięćdziesiątnicy (początek Kościoła)</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Osoba Boska, godna czci; Zesłanie Ducha Świętego w dniu Pięćdziesiątnicy (początek Kościoła)</p>',
				],
			],
		],

		// ── Kanon wiary ──
		[
			'category'   => 'Kanon wiary',
			'title'      => 'Pismo Święte',
			'sort_order' => 1,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Nowy Testament: 27 ksiąg<br>Stary Testament: 39 ksiąg<br>+7 ksiąg deuterokanonicznych<br>(kanon aleksandryjski)</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Nowy Testament: 27 ksiąg<br>Stary Testament: 39 ksiąg<br>(kanon palestyński)</p>',
				],
			],
		],
		[
			'category'   => 'Kanon wiary',
			'title'      => 'Źródła Objawienia',
			'sort_order' => 2,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Pismo Święte<br>Tradycja<br>Magisterium Kościoła<br>(Nauczycielski Urząd Kościoła)</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Pismo Święte</p>',
				],
			],
		],

		// ── Dar zbawienia ──
		[
			'category'   => 'Dar zbawienia',
			'title'      => 'Zbawienie',
			'sort_order' => 1,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Jezus sprawcą zbawienia. Dar usprawiedliwienia ofiarowany przez Boga z łaski</p><p>Aby otrzymać zbawienie, w chwili śmierci należy być w stanie łaski uświęcającej</p><p>Należy trwać w jedności z Kościołem</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Jezus sprawcą zbawienia. Dar usprawiedliwienia ofiarowany przez Boga z łaski</p><p>Dar należy przyjąć przez wiarę i w niej trwać (zaufać Bogu). Podstawą jest uznanie własnej grzeszności i wyznanie grzechów Bogu oraz zaufanie Chrystusowi, czyli uznanie wystarczalności dzieła zbawienia dokonanego przez Jezusa na krzyżu.</p><p>Dar zbawienia udzielany niezależnie od przynależności do określonego wyznania</p>',
				],
			],
		],

		// ── Liturgia ──
		[
			'category'   => 'Liturgia',
			'title'      => 'Liturgia główna',
			'sort_order' => 1,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Msza jako bezkrwawa ofiara (pozostająca w ścisłym związku z aktem jedynej ofiary złożonej na Krzyżu)</p><p>Sprawowana przez wyświęconego w Kościele kapłana</p><p>Liturgia rozbudowana. Istnieje konieczność zachowania odpowiednich rytów, formuł modlitewnych</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Liturgia (publiczna służba Bogu) rozumiana jako ofiara uwielbienia i dziękczynienia</p><p>Sprawuje ją ogół wiernych (kapłaństwo powszechne) pod przewodnictwem osoby ordynowanej</p><p>Duża spontaniczność, czynny udział wiernych. Z modlitw recytowanych zasadniczo: Ojcze nasz, pieśni, Psalmy, Wyznanie wiary. Modlitwa wspólnotowa, improwizowana i spontaniczna, duża rola śpiewu (uwielbianie). Oprócz liturgii modlitwy i Słowa sprawowana jest Wieczerza Pańska</p>',
				],
			],
		],

		// ── Sakramenty ──
		[
			'category'   => 'Sakramenty',
			'title'      => 'Ilość sakramentów',
			'sort_order' => 1,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Siedem: chrzest, Eucharystia, bierzmowanie, pokuta, małżeństwo, kapłaństwo, namaszczenie chorych</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Dwa: chrzest i eucharystia</p>',
				],
			],
		],
		[
			'category'   => 'Sakramenty',
			'title'      => 'Istota sakramentów',
			'sort_order' => 2,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Działają ex opere operato. Ich skuteczność polega na udzieleniu łaski mocą samej czynności. Wiara przyjmującego sakrament, choć wymagana, nie jest konieczna, jak w przypadku chrztu dzieci (mówi się wówczas o wierze Kościoła, rodziców chrzestnych...)</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Skuteczność sakramentu uzależniona od wiary przyjmującego. Stąd wykluczona jest zasada ex opere operato</p>',
				],
			],
		],
		[
			'category'   => 'Sakramenty',
			'title'      => 'Chrzest',
			'sort_order' => 3,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>W imię Ojca i Syna, i Ducha Świętego.</p><p>Gładzi grzech pierworodny i wszystkie grzechy do chrztu.</p><p>Konieczny do zbawienia</p><p>Udzielany zasadniczo dzieciom (niemowlętom)</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>W imię Ojca i Syna, i Ducha Świętego.</p><p>Chrzest na odpuszczenie grzechów</p><p>Chrzest jest przykazaniem Pańskim, a jego konieczność nie podlega dyskusji. Chrzest uznaje się za końcowy akt procesu narodzenia na nowo.</p><p>Chrzest nie tylko wskazuje na nawrócenie i inne rzeczywistości duchowe (takie jak: obmycie i uwolnienie z grzechów, oczyszczenie sumienia ze wszystkich obciążających win, zmartwychwstanie do nowego życia itp.), ale je poświadcza i w wymiarze duchowym urzeczywistnia.</p><p>Udzielany jest tym, którzy się upamiętali, nawrócili i uwierzyli w Jezusa, jako osobistego Pana i Zbawiciela (istotna rola doświadczenia spotkania z Bogiem). Może być udzielony dzieciom, jednak dopiero, gdy są one świadome wiary.</p>',
				],
			],
		],
		[
			'category'   => 'Sakramenty',
			'title'      => 'Eucharystia',
			'sort_order' => 4,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Sprawowana pod dwiema postaciami<br>Udzielana wiernym zazwyczaj pod jedną postacią (chleb/opłatek)</p><p>Eucharystia jako ofiara</p><p>Wiara w przeistoczenie (transsubstancjacja), w wyniku której chleb i wino zostają przeistoczone w Ciało i Krew</p><p>Adoracji Chleba eucharystycznego służą specjalne nabożeństwa oraz Święto Bożego Ciała (Święto Ciała i Krwi Pańskiej)</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Sprawowana pod dwiema postaciami<br>Udzielana wiernym pod dwiema postaciami</p><p>Wieczerza Pańska jako wspólnota (koinonia)</p><p>Wiara w duchową obecność Chrystusa w chlebie i winie, które dla przyjmujących są komunią z Ciałem i Krwią Chrystusa</p><p>Chleba konsekrowanego Słowem Bożym i modlitwą nie adoruje się w żadnej postaci</p>',
				],
			],
		],
		[
			'category'   => 'Sakramenty',
			'title'      => 'Wyznawanie grzechów',
			'sort_order' => 5,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Dokonuje się poprzez sakrament pojednania (spowiedź), absolucji udziela kapłan</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Wierny jest zobowiązany do wyznawania grzechów Bogu oraz pojednania z bliźnimi. Praktykowana jest spowiedź powszechna (w czasie nabożeństwa). Istnieje możliwość pomocy duszpasterskiej w przypadku wyrażenia woli wyznania win. Rozmowa indywidualna zakończona jest modlitwą/prośbą o absolucję.</p>',
				],
			],
		],
		[
			'category'   => 'Sakramenty',
			'title'      => 'Bierzmowanie',
			'sort_order' => 6,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Sakrament umocnienia wiernego (namaszczenia krzyżmem - chrisma), który wcześniej przyjął chrzest, pojednał się z Bogiem w sakramencie pokuty i przystąpił do Eucharystii</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Wierny po nawróceniu (często przed chrztem) podczas modlitwy wspólnotowej lub indywidualnej otrzymuje namaszczenie Ducha Świętego (chrzest w Duchu), który objawia się w postaci darów łaski (charyzmatów)</p>',
				],
			],
		],
		[
			'category'   => 'Sakramenty',
			'title'      => 'Namaszczenie chorych',
			'sort_order' => 7,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Do udzielania sakramentu używany poświęcony olej</p><p>Sakrament udzielany przez kapłana chorym, określany także jako „ostatnie namaszczenie”</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Modlitwie o chorych często towarzyszą oleje służące do namaszczania, ale nie jest to warunek konieczny do sprawowania takiej modlitwy.</p><p>Modlitwa o chorych (i potrzebujących) jest częstym punktem liturgii. Organizuje się specjalne nabożeństwa, na których modlitwa w tej intencji jest punktem centralnym.</p>',
				],
			],
		],
		[
			'category'   => 'Sakramenty',
			'title'      => 'Kapłaństwo',
			'sort_order' => 8,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Powszechne kapłaństwo. Podział na kler i lud</p><p>Celibat obowiązuje wszystkich duchownych</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Ordynowani do służby mężczyźni, (pastorzy), odpowiadają za sprawowanie nabożeństwa</p><p>Celibat nie jest nakazany. Postuluje się, aby pastor był mężem i ojcem</p>',
				],
			],
		],
		[
			'category'   => 'Sakramenty',
			'title'      => 'Małżeństwo',
			'sort_order' => 9,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Związek mężczyzny i kobiety<br>Sakrament nierozerwalny. Istnieje prawna droga do uznania małżeństwa za zawarte nieważnie, czyli orzeczenie o nieważności zawarcia sakramentu małżeństwa</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Związek mężczyzny i kobiety<br>Rozwód dopuszczalny w przypadku wskazania na grzech wszeteczeństwa (niewierności) jednej ze stron</p>',
				],
			],
		],

		// ── Kościół ──
		[
			'category'   => 'Kościół',
			'title'      => 'Struktura',
			'sort_order' => 1,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Kościół rzeczywistością widzialną i niewidzialną (mistyczną). Na czele Kościoła stoi Jezus Chrystus, Głowa i fundament Kościoła</p><p>W strukturze Kościoła widzialnego głową jest biskup rzymski (papież), jako Vicarius Iesu Christi (następca Jezusa Chrystusa).</p><p>Struktura Kościoła jest monarchiczna (feudalna)</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Kościół rzeczywistością widzialną i niewidzialną (mistyczną). Na czele Kościoła stoi Jezus Chrystus, Głowa i fundament Kościoła</p><p>W strukturze ruchu zielonoświątkowego nie występuje żaden urząd, który można porównać z instytucją papiestwa. Podkreśla się natomiast, że Następcą Chrystusa na ziemi (Vicarius) jest Duch Święty.</p><p>Zbory-parafie posiadają dużą autonomię w sprawach personalnych (powołania na urząd pastora, starszych zboru, w sprawach dyscyplinarnych wobec członków wspólnoty, w sprawach finansowych)</p>',
				],
			],
		],
		[
			'category'   => 'Kościół',
			'title'      => 'Przystąpienie do kościoła',
			'sort_order' => 2,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Bramą Kościoła jest chrzest</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Bramą prowadzącą do Kościoła jest doświadczenie osobistego nawrócenia i zaufanie Jezusowi jako swojemu Panu i Zbawicielowi. Finalnym aktem nowych narodzin jest chrzest, który pieczętuje odpuszczenie grzechów i włącza wiernego w Kościół.</p>',
				],
			],
		],
		[
			'category'   => 'Kościół',
			'title'      => 'Wykluczenie z kościoła',
			'sort_order' => 3,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Kościół ma prawo ekskomuniki, z którego może korzystać w określonych okolicznościach papież i biskupi</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Zbór ma prawo ekskomuniki. Wyklucza się ze zboru osobę, która dopuściła się rażących uchybień w sferze moralności lub błędnej doktryny, a wcześniejsze napomnienia nie przynosiły efektu</p>',
				],
			],
		],
		[
			'category'   => 'Kościół',
			'title'      => 'Locum kościoła',
			'sort_order' => 4,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Kościoły i kaplice są zdobione</p><p>Istotną rolę pełnią w nich obrazy i rzeźby, liczne ołtarze</p><p>Centralnym punktem jest ołtarz i krucyfiks oraz tabernakulum, w którym przechowywany jest Chleb eucharystyczny.</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Brak wskazań co do wystroju kaplicy. Najczęściej są proste i surowe</p><p>Kaplice pozbawione wizerunków świętych i ołtarzy</p><p>Istotną funkcję pełni kazalnica, stół komunijny oraz krzyż. Prezbiterium dostępne dla wiernych. Kaplice są ogrzewane, wierni zdejmują wierzchnie okrycia (szatnia)</p>',
				],
			],
		],
		[
			'category'   => 'Kościół',
			'title'      => 'Stosunek Kościoła do państwa',
			'sort_order' => 5,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Wcześniej istniał ścisły związek Kościoła z państwem. Obecnie Kościół stoi na straży moralności, wyraża też swoją opinię w innych istotnych dziedzinach życia społecznego.</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Podkreśla się rozdział Kościoła od państwa, zwracając uwagę na fakt, że władza pochodzi od Boga, ale sposób rządzenia jest wymysłem człowieka</p>',
				],
			],
		],

		// ── Kult i święta ──
		[
			'category'   => 'Kult i święta',
			'title'      => 'Kult',
			'sort_order' => 1,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Centralnym podmiotem kultu jest Bóg, ku któremu kierowane są modlitwy i którego wierni adorują.</p><p>Wskazane jest, aby codziennie sprawowana była msza, a wierni wezwani są do brania w niej udziału. Oprócz mszy wierni mają do dyspozycji wiele innych form liturgicznych, różnych w zależności od okresu liturgicznego (nabożeństwa ku czci świętych, szczególnie Matki Bożej; droga krzyżowa, gorzkie żale, kółka różańcowe, etc.). Wierni posiadają wiele możliwości wyrażania swojej pobożności, dopasowanej do potrzeb i wrażliwości.</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Centralnym podmiotem kultu jest Bóg, ku któremu kierowane są modlitwy i którego wierni adorują.</p><p>Wierni spotykają się na nabożeństwach głównych w niedzielę oraz w inne dni tygodnia. Inne nabożeństwa mają charakter spotkań modlitewnych, uwielbiających, rozważań biblijnych, nauczających, grup wsparcia. Nie ma konieczności, aby spotkania te prowadzone były przez osobę ordynowaną (duchownego).</p>',
				],
			],
		],
		[
			'category'   => 'Kult i święta',
			'title'      => 'Pobożność indywidualna',
			'sort_order' => 2,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Pielęgnowanie życia duchowego w oparciu sakramenty. Modlitwa na podstawie pacierza. Zasadniczo dominują formuły wyuczone lub czytane (brewiarz). Pobożność ta ma często związek z kultem świętych (np. patronów) i Matki Chrystusa.</p><p>Zachowywanie dni postnych (środa, piątek)</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Centrum życia duchowego wiernego stanowi czytanie i rozważanie Słowa Bożego oraz modlitwa. Dominuje modlitwa improwizowana, a także modlitwa na podstawie Psałterza (Psalmy). Istotną rolę odgrywają dary Ducha Świętego, szczególnie dar języków, zalecany do modlitwy indywidualnej. Post jest traktowany jako osobiste ćwiczenie w pobożności, często oznacza całkowite powstrzymanie się od spożywania posiłków (dzień, kilka lub nawet kilkanaście dni).</p><p>Kościół nie narzuca postu, choć nieraz zaleca go w określonej, wspólnej intencji. Nie ma jednak żadnych konsekwencji dla tych, którzy go nie zachowują.</p>',
				],
			],
		],
		[
			'category'   => 'Kult i święta',
			'title'      => 'Matka Chrystusa',
			'sort_order' => 3,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Otoczona jest szczególnym kultem.</p><p>Matka Boża (Theotokos</p><p>Niepokalana Dziewica (poza Jezusem nie miała więcej dzieci)</p><p>Orędowniczka, Królowa Nieba, Pocieszycielka. Kierowane są do niej modlitwy (szczególnym rodzajem jest różaniec). Wiara w Jej niepokalane poczęcie, (urodzenie bez grzechu pierworodnego) wieczne dziewictwo, wniebowzięcie.</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Maria stanowi wzór postępowania i przykład do naśladowania dla wierzącego. Wierni kultem otaczają wyłącznie Boga (Ojca, Syna i Ducha Świętego), ku któremu kierują modlitwy. Z tego powodu Matka Pana nie jest otoczona żadnym specjalnym kultem, a formy kultu innych osób, poza Bogiem, określa się jako bałwochwalstwo.</p><p>Część środowiska przyjmuje tytuł Matka Boża (Theotokos), choć jednocześnie czyni się to w nawiązaniu do Jezusa Chrystusa, jako Wcielonego Boga.</p><p>Uznaje się jej dziewictwo w chwili poczęcia i narodzin Jezusa. Nie przeczy się możliwości posiadania przez Nią innych dzieci poza Jezusem</p><p>Odrzuca się nie mające potwierdzenia w Biblii tytuły oraz nie podziela się wiary w niepokalane poczęcie i wniebowzięcie.</p>',
				],
			],
		],
		[
			'category'   => 'Kult i święta',
			'title'      => 'Święci',
			'sort_order' => 4,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Określenie święty używane jest na oznaczenie osoby, która po śmierci została uznana za godną życia wiecznego i przebywa już w niebie (proces kanonizacyjny).</p><p>Wstawiają się za wiernymi, żyjącymi na ziemi. Można, a nawet powinno się szukać u nich wstawiennictwa i pomocy, ponieważ żyjąc już w bezpośrednim oglądaniu Stwórcy mogą wypraszać u Niego łaski dla Kościoła.</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Terminem święci określany jest ogół wierzących w Chrystusa. Pojęcie święty nie jest zarezerwowane dla umarłych, którzy żyli nienaganną wiarą.</p><p>Odrzuca się przyczynne modlitwy do świętych jako bezprzedmiotowe. Kult świętych traktowany jest jako przejaw bałwochwalstwa.</p>',
				],
			],
		],
		[
			'category'   => 'Kult i święta',
			'title'      => 'Kult zmarłych',
			'sort_order' => 5,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Umarli otoczeni są modlitwą, uprasza się u Boga dla nich łaski, w tej intencji odprawia się msze.</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Zmarli nie potrzebują modlitwy, ponieważ ich los został już postanowiony przez Boga i jest nieodwołalny.</p>',
				],
			],
		],
		[
			'category'   => 'Kult i święta',
			'title'      => 'Święta',
			'sort_order' => 6,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Niedziela</p><p>Centrum roku liturgicznego stanowi Wielkanoc, którą poprzedza czterdziestodniowy okres postu, w którym szczególną rolę odgrywa Triduum Paschalne. W okresie wielkanocnym obowiązkiem wiernego jest przystąpienie do sakramentu pokuty i eucharystii</p><p>Duża ilość świąt i dni świątecznych.</p><p>Przy okazji świąt istnieje możliwość otrzymania odpustów.</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Niedziela</p><p>Największym świętem jest Wielkanoc, co nie oznacza, że związane są z tym jakieś szczególne obowiązki wiernego. Podkreślony bywa anamnetyczny charakter świętowania. Nabożeństwo Wielkanocne poprzedza liturgia Wielkiego Piątku (ustawowe prawo do dnia wolnego), w którym wierni gromadzą się na modlitwie i rozważaniu wydarzenia Krzyża. Niektórzy wierni praktykują tego dnia post.</p><p>Mała ilość świąt. Najważniejsze to: Boże Narodzenie (święto Narodzenia Pańskiego) i Zesłanie Ducha Świętego (Zielone Święta; ustawowe prawo do dnia wolnego); od pozostałych nabożeństw różnią się najczęściej kazaniem (okolicznościowe). Oprócz tego zbory obchodzą własne święta. Najbardziej powszechne jest Święto Dziękczynienia (przełom września/października), a także rocznica powstania zboru i inne. Mocno podkreśla się, że każdy dzień dla chrześcijanina jest święty i świętem.</p><p>Święta nie mają związku z udzielaniem szczególnych łask Bożych.</p>',
				],
			],
		],

		// ── Eschatologia ──
		[
			'category'   => 'Eschatologia',
			'title'      => 'Życie po śmierci',
			'sort_order' => 1,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Życie duszy po śmierci ciała. Sąd indywidualny, który następuje zaraz po śmierci.</p><p>W przekonaniu powszechnym (choć nie w teologii) życie wieczne rozpoczyna się w chwili śmierci w łasce uświęcającej.</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Życie duszy po śmierci ciała. Sąd indywidualny, który następuje zaraz po śmierci.</p><p>Życie wieczne rozpoczyna się w momencie przyjęcia daru zbawienia. Wierzący w Chrystusa zostali osądzeni, gdy upamiętali się, nawrócili i przyjęli dar zbawienia. Osądzone muszą zostać ich uczynki, aby mogła zostać wyznaczona nagroda lub wymierzona kara, która jednak nie oznacza odrzucenia (potępienia).</p>',
				],
			],
		],
		[
			'category'   => 'Eschatologia',
			'title'      => 'Piekło i niebo',
			'sort_order' => 2,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Wiara w niebo (życie wieczne) i piekło (stan oddalenia od Boga).</p><p>Sąd ostateczny: wymiar powszechny</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Wiara w niebo (życie wieczne) i piekło (stan oddalenia od Boga).</p><p>Sąd ostateczny, w którym sądzeni będą wszyscy żywi i umarli, którzy z różnych powodów nie poznali (nie przyjęli) Jezusa jako Pana i Zbawiciela.</p>',
				],
			],
		],
		[
			'category'   => 'Eschatologia',
			'title'      => 'Czyściec i odpusty',
			'sort_order' => 3,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Czyściec (proces oczyszczenia, nie miejsce), przedsionek nieba. Czyściec jest przewidziany dla tych, którzy umarli w stanie łaski uświęcającej, ale chwili śmierci nie mieli uporządkowanego stosunku do rzeczy stworzonych. Ze stanem czyśćca związane są odpusty, które przyczyniają się do zmniejszenia lub do całkowitego usunięcia oczyszczającej kary czyśćca.</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Odrzucenie wiary w czyściec i rolę odpustów. Bóg wraz z winami darował grzesznikowi również karę, jaka mu się należała za popełnione grzechy.</p>',
				],
			],
		],

		// ── Wychowanie i etyka ──
		[
			'category'   => 'Wychowanie i etyka',
			'title'      => 'Wychowanie religijne',
			'sort_order' => 1,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Obowiązek wychowania religijnego spoczywa w pierwszej kolejności na rodzicach i Kościele.</p><p>Dzieci uczestniczące w nauczaniu religii są po chrzcie.</p><p>Zajęcia religii prowadzone przez katechetów w szkołach</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Obowiązek wychowania religijnego spoczywa na rodzicach i Kościele.</p><p>Dzieci uczestniczące w nauczaniu religii są (najczęściej) przed chrztem; do rzadkości należą chrzty przed ukończeniem 10 roku życia; nawet po ukończeniu katechizacji część młodzieży nie przystępuje do chrztu.</p><p>Zajęcia religii (Szkółka Niedzielna) prowadzone przez katechetów w szkołach, choć zdecydowanie częściej, z powodu małej ilości dzieci, w punktach katechetycznych zarejestrowanych przy szkołach.</p>',
				],
			],
		],
		[
			'category'   => 'Wychowanie i etyka',
			'title'      => 'Aborcja',
			'sort_order' => 2,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Aborcja jest grzechem.</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Aborcja jest grzechem.</p>',
				],
			],
		],
		[
			'category'   => 'Wychowanie i etyka',
			'title'      => 'Eutanazja',
			'sort_order' => 3,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Eutanazja jest grzechem.</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Eutanazja jest grzechem.</p>',
				],
			],
		],
		[
			'category'   => 'Wychowanie i etyka',
			'title'      => 'Homoseksualizm',
			'sort_order' => 4,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Homoseksualizm jest grzechem.</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Homoseksualizm jest grzechem.</p>',
				],
			],
		],
		[
			'category'   => 'Wychowanie i etyka',
			'title'      => 'Stosunek do antykoncepcji',
			'sort_order' => 5,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Akceptacja metod naturalnych.</p><p>Negacja innych metod</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Akceptacja metod naturalnych.</p><p>Stosowanie innych metod i środków pozostawiona jest sumieniu wiernych (nie są zakazane), pod warunkiem jednak, że środki antykoncepcyjne nie prowadzą do śmierci embrionu (człowieka) oraz nie wpływają negatywnie na płód i matkę.</p>',
				],
			],
		],
		[
			'category'   => 'Wychowanie i etyka',
			'title'      => 'Stosunek do służby wojskowej',
			'sort_order' => 6,
			'churches'   => [
				[
					'church_name' => $k,
					'description' => '<p>Służba wojskowa nie jest złem. Należy bronić swojej ojczyzny i swojego życia, nawet za cenę zabicia nieprzyjaciół-agresorów.</p>',
				],
				[
					'church_name' => $z,
					'description' => '<p>Pełnienie służby wojskowej pozostawia się sumieniu wiernych.</p>',
				],
			],
		],
	];
}

/**
 * Import data from a JSON structure.
 *
 * Expected JSON format:
 * {
 *   "categories": [ {"name": "Bóg", "order": 1}, ... ],
 *   "topics": [
 *     {
 *       "category": "Bóg",
 *       "title": "Ojciec",
 *       "sort_order": 1,
 *       "churches": [ {"church_name": "...", "description": "<p>...</p>"}, ... ]
 *     }
 *   ]
 * }
 *
 * @param array $json_data Decoded JSON data.
 */
function comparison_of_religions_run_json_import( array $json_data ): void {
	$taxonomy  = 'comparison_category';
	$post_type = 'comparison_topic';

	if ( empty( $json_data['categories'] ) || empty( $json_data['topics'] ) ) {
		echo "ERROR: JSON musi zawierać klucze 'categories' i 'topics'.\n";
		return;
	}

	// ── 1. Create taxonomy terms ──
	$term_ids = [];
	foreach ( $json_data['categories'] as $cat ) {
		$cat_name  = sanitize_text_field( $cat['name'] ?? '' );
		$cat_order = absint( $cat['order'] ?? 0 );

		if ( '' === $cat_name ) {
			continue;
		}

		$existing = term_exists( $cat_name, $taxonomy );
		if ( $existing ) {
			$term_ids[ $cat_name ] = (int) $existing['term_id'];
		} else {
			$result = wp_insert_term( $cat_name, $taxonomy );
			if ( is_wp_error( $result ) ) {
				echo 'ERROR term ' . esc_html( $cat_name ) . ': ' . esc_html( $result->get_error_message() ) . "\n";
				continue;
			}
			$term_ids[ $cat_name ] = (int) $result['term_id'];
		}
		update_term_meta( $term_ids[ $cat_name ], 'sort_order', $cat_order );
		echo 'Term: ' . esc_html( $cat_name ) . ' (ID: ' . esc_html( (string) $term_ids[ $cat_name ] ) . ")\n";
	}

	// ── 2. Create CPT posts ──
	$created = 0;
	foreach ( $json_data['topics'] as $entry ) {
		$cat_name   = sanitize_text_field( $entry['category'] ?? '' );
		$title      = sanitize_text_field( $entry['title'] ?? '' );
		$sort_order = absint( $entry['sort_order'] ?? 0 );

		if ( '' === $title || ! isset( $term_ids[ $cat_name ] ) ) {
			echo 'SKIP: ' . esc_html( $title ) . ' (kategoria \'' . esc_html( $cat_name ) . "' nie znaleziona)\n";
			continue;
		}

		$post_id = wp_insert_post(
			[
				'post_type'   => $post_type,
				'post_title'  => $title,
				'post_status' => 'publish',
			]
		);

		if ( is_wp_error( $post_id ) ) {
			echo 'ERROR post ' . esc_html( $title ) . ': ' . esc_html( $post_id->get_error_message() ) . "\n";
			continue;
		}

		wp_set_object_terms( $post_id, $term_ids[ $cat_name ], $taxonomy );

		// Sanitize churches data.
		$churches = [];
		if ( is_array( $entry['churches'] ?? null ) ) {
			foreach ( $entry['churches'] as $church ) {
				$churches[] = [
					'church_name' => sanitize_text_field( $church['church_name'] ?? '' ),
					'description' => wp_kses_post( $church['description'] ?? '' ),
				];
			}
		}

		update_post_meta( $post_id, 'churches', $churches );
		update_post_meta( $post_id, 'sort_order', $sort_order );

		++$created;
		echo 'Post: ' . esc_html( $title ) . ' (ID: ' . esc_html( (string) $post_id ) . ') → ' . esc_html( $cat_name ) . "\n";
	}

	echo "\nGotowe! Utworzono " . esc_html( (string) $created ) . " postów.\n";
}

/**
 * Export existing comparison data to JSON-ready array.
 *
 * @return array Data suitable for json_encode().
 */
function comparison_of_religions_export_data(): array {
	$taxonomy  = 'comparison_category';
	$post_type = 'comparison_topic';

	// Export categories.
	$terms      = get_terms(
		[
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		]
	);
	$categories = [];
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$categories[] = [
				'name'  => $term->name,
				'order' => (int) get_term_meta( $term->term_id, 'sort_order', true ),
			];
		}
	}

	// Export topics.
	$posts  = get_posts(
		[
			'post_type'      => $post_type,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		]
	);
	$topics = [];
	foreach ( $posts as $post ) {
		$terms_list = wp_get_object_terms( $post->ID, $taxonomy );
		$cat_name   = ! empty( $terms_list ) && ! is_wp_error( $terms_list ) ? $terms_list[0]->name : '';

		$churches_meta = get_post_meta( $post->ID, 'churches', true );
		$topics[]      = [
			'category'   => $cat_name,
			'title'      => $post->post_title,
			'sort_order' => (int) get_post_meta( $post->ID, 'sort_order', true ),
			'churches'   => is_array( $churches_meta ) ? $churches_meta : [],
		];
	}

	return [
		'categories' => $categories,
		'topics'     => $topics,
	];
}
