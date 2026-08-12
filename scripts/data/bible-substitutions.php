<?php
/**
 * Scripture substitution pairs: DeepL's paraphrase -> the text of a published translation.
 *
 * Extracted from `scripts/substitute-bible-quotes.php` so that the same table can be read
 * by the substitution script AND by the test `scripts/tests/kzt-bible-quotes.php`. Without
 * it the test had to guess what the script inserts, and guessed wrong: it assumed whole
 * verses, while six of these entries are short phrases quoted inside a sentence.
 *
 * The top-level keys are the post ids from the time the texts were collected. They are NOT
 * used for lookup — the script flattens the table and searches every post of the language
 * concerned. They remain as a record of where each fragment stood back then.
 *
 * Translation sources and the attribution owed: `scripts/data/bible-quotes.php`.
 *
 * @package Kzmielec
 */

return array(

	// --- 115: w sprawie służby kobiet w Kościele ---
	115 => array(
		'en' => array(
			array(
				'“There is neither Jew nor Greek, there is neither slave nor free, there is neither male nor female; for you are all one in Christ Jesus.”',
				'“There is neither Jew nor Gentile, neither slave nor free, nor is there male and female, for you are all one in Christ Jesus.”',
			),
			array(
				'‘heirs of the grace of life’',
				'“heirs of the gracious gift of life”',
			),
			array(
				'‘In the same way, you husbands, live with your wives in an understanding way, showing them honour, as the weaker vessel, since they are heirs of the grace of life, so that your prayers may not be hindered.’',
				'“Husbands, in the same way be considerate as you live with your wives, and treat them with respect as the weaker partner and as heirs with you of the gracious gift of life, so that nothing will hinder your prayers.”',
			),
			array(
				'‘And you yourselves, as living stones, are being built up as a spiritual house, a holy priesthood, to offer spiritual sacrifices acceptable to God through Jesus Christ.’',
				'“you also, like living stones, are being built into a spiritual house to be a holy priesthood, offering spiritual sacrifices acceptable to God through Jesus Christ.”',
			),
			array(
				'‘(...) for the husband is the head of the wife, as Christ is the Head of the Church, the body of which He is the Saviour.’',
				'“(...) for the husband is the head of the wife as Christ is the head of the church, his body, of which he is the Savior.”',
			),
		),
		'uk' => array(
			array( '«спадкоємицями благодаті життя»', '«співспадкоємицями благодаті життя»' ),
			array(
				'«Так само й ви, чоловіки, поводьтеся з ними з розумінням, як із слабшою статтю, і шануйте їх, оскільки й вони є спадкоємицями благодаті життя, щоб ваші молитви не зустрічали перешкод»',
				'«Так само чоловіки: живіть зі своїми дружинами в порозумінні, поводячись, немов з тендітною вазою, сповненою жіночості, виявляючи їм шану як співспадкоємцям благодаті життя, щоб не було перепон для ваших молитов»',
			),
			array(
				'«Немає вже ні юдея, ні грека, немає раба, ні вільного, немає чоловіка, ні жінки; бо ви всі одне в Ісусі Христі»',
				'«Немає юдея, ні грека; немає ані раба, ані вільного; немає чоловічого роду, ні жіночого, бо в Ісусі Христі ви всі — одно»',
			),
			array(
				'«І ви самі, як живі камені, будуйте себе в духовний дім, у святе священство, щоб приносити духовні жертви, приємні Богові через Ісуса Христа»',
				'«І ви самі, немов живе каміння, збудовуйтеся в духовний дім, щоби бути святим священством, приносити духовні жертви, приємні Богові, через Ісуса Христа»',
			),
			array(
				'«(...) бо чоловік є головою дружини, як і Христос — Голова Церкви, тіла, Спасителем якого Він є»',
				'«(...) адже чоловік є голова дружини, як Христос — Голова Церкви, Він же — Спаситель тіла»',
			),
		),
		'es' => array(
			array( '«herederas de la gracia de la vida»', '«herederas del grato don de la vida»' ),
			array(
				'«Del mismo modo, vosotros, maridos, tratadlas con comprensión, como a un género más débil, y honradlas, ya que también ellas son herederas de la gracia de la vida, para que vuestras oraciones no se vean obstaculizadas»',
				'«De igual manera, ustedes esposos, sean comprensivos en su vida conyugal, cada uno trate a su esposa con respeto, ya que como mujer es más delicada y ambos son herederos del grato don de la vida. Así nada estorbará las oraciones de ustedes»',
			),
			array(
				'«Ya no hay judío ni griego; no hay esclavo ni libre; no hay hombre ni mujer, pues todos vosotros sois uno en Jesucristo»',
				'«Ya no hay judío ni no judío, esclavo ni libre, hombre ni mujer, sino que todos ustedes son uno solo en Cristo Jesús»',
			),
			array(
				'«Y vosotros mismos, como piedras vivas, edificaos como casa espiritual, como sacerdocio santo, para ofrecer sacrificios espirituales agradables a Dios por medio de Jesucristo»',
				'«También ustedes son como piedras vivas, con las cuales se está edificando una casa espiritual. De este modo llegan a ser un sacerdocio santo, para ofrecer sacrificios espirituales que Dios acepta por medio de Jesucristo»',
			),
			array(
				'«(...) porque el marido es cabeza de la mujer, así como Cristo es cabeza de la Iglesia, cuerpo del cual él es el Salvador»',
				'«(...) porque el esposo es cabeza de su esposa, así como Cristo es cabeza de la iglesia, la cual es su cuerpo, y él su Salvador»',
			),
		),
	),

	// --- 103: w sprawie zjawisk towarzyszących duchowemu ożywieniu ---
	103 => array(
		'en' => array(
			array( '‘Let all things be done decently and in order’', '“But everything should be done in a fitting and orderly way”' ),
		),
		'uk' => array(
			array( '«А все нехай відбувається гідно й упорядковано»', '«Але все нехай відбувається пристойно і організовано»' ),
		),
		'es' => array(
			array( '«Que todo se haga con dignidad y orden»', '«Pero todo debe hacerse de una manera apropiada y con orden»' ),
		),
	),

	// --- 77: wizja (own page, Polish changes too) ---
	77 => array(
		'pl' => array(
			array(
				'„A wszystko to jest z Boga, który nas pojednał z sobą przez Chrystusa i poruczył nam służbę pojednania”',
				'„A wszystko to jest z Boga, który nas pojednał ze sobą przez Chrystusa i zlecił nam posługę pojednania”',
			),
		),
		'en' => array(
			array(
				'‘And all this is from God, who has reconciled us to himself through Christ and has entrusted to us the ministry of reconciliation’',
				'“All this is from God, who reconciled us to himself through Christ and gave us the ministry of reconciliation”',
			),
		),
		'uk' => array(
			array(
				'«А все це від Бога, який примирив нас із Собою через Христа і доручив нам служіння примирення»',
				'«Усе — від Бога, Який примирив нас із Собою через Христа і дав нам служіння примирення»',
			),
		),
		'es' => array(
			array(
				'«Y todo esto proviene de Dios, quien nos reconcilió consigo mismo por medio de Cristo y nos encomendó el ministerio de la reconciliación»',
				'«Todo esto proviene de Dios, quien por medio de Cristo nos reconcilió consigo mismo y nos dio el ministerio de la reconciliación»',
			),
		),
	),

	// --- 65: misja (own page, Polish changes too) ---
	65 => array(
		'pl' => array(
			array(
				'„Umiłowani, teraz dziećmi Bożymi jesteśmy, ale jeszcze się nie objawiło, czym będziemy. Lecz wiemy, że gdy się objawi, będziemy do niego podobni, gdyż ujrzymy go takim, jakim jest.”',
				'„Drodzy, teraz jesteśmy dziećmi Boga, a kim będziemy, to się jeszcze okaże. Choć już wiemy, że gdy się okaże, będziemy podobni do Niego, gdyż zobaczymy Go takim, jaki jest.”',
			),
			array(
				'„W odnowieniu tym nie ma Greka ani Żyda, obrzezania ani nieobrzezania, cudzoziemca, Scyty, niewolnika, wolnego, lecz Chrystus jest wszystkim i we wszystkich.”',
				'„Na tej drodze nie liczy się, czy ktoś jest Grekiem, czy Żydem, czy go obrzezano, czy nie, czy pochodzi z daleka, jest Scytą, niewolnikiem, czy wolnym — liczy się tylko Chrystus, który jest wszystkim i we wszystkich.”',
			),
			array(
				'„Dlatego w miejsce Chrystusa poselstwo sprawujemy, jak gdyby przez nas Bóg upominał; w miejsce Chrystusa prosimy: Pojednajcie się z Bogiem.”',
				'„Dlatego w miejsce Chrystusa głosimy poselstwo jakby samego Boga, który przez nas kieruje do ludzi wezwanie. W miejsce Chrystusa błagamy: Pojednajcie się z Bogiem.”',
			),
		),
		'en' => array(
			array(
				'“Beloved, we are now children of God, but what we shall be has not yet been revealed. Yet we know that when it is revealed, we shall be like him, for we shall see him as he is.”',
				'“Dear friends, now we are children of God, and what we will be has not yet been made known. But we know that when Christ appears, we shall be like him, for we shall see him as he is.”',
			),
			array(
				'‘In this new creation there is neither Greek nor Jew, neither circumcised nor uncircumcised, neither barbarian, Scythian, slave nor free, but Christ is all and in all.’',
				'“Here there is no Gentile or Jew, circumcised or uncircumcised, barbarian, Scythian, slave or free, but Christ is all, and is in all.”',
			),
			array(
				'‘We are therefore Christ’s ambassadors, as though God were making his appeal through us; we implore you on Christ’s behalf: Be reconciled to God.’',
				'“We are therefore Christ’s ambassadors, as though God were making his appeal through us. We implore you on Christ’s behalf: Be reconciled to God.”',
			),
		),
		'uk' => array(
			array(
				'«Улюблені, зараз ми є дітьми Божими, але ще не відкрилося, чим ми будемо. Проте ми знаємо, що коли це відкриється, ми будемо подібні до Нього, бо побачимо Його таким, яким Він є»',
				'«Улюблені! Тепер ми — Божі діти, але ще не виявилося, що будемо. Знаємо, що коли Він з’явиться, ми будемо подібні до Нього, адже побачимо Його таким, який Він є»',
			),
			array(
				'«У цьому оновленні немає ні грека, ні юдея, ні обрізаного, ні необрізаного, ні чужинця, ні скіфа, ні раба, ні вільного, але Христос є всім і в усіх»',
				'«де немає ні грека, ні юдея, ні обрізання, ні необрізання, ні варвара, ні скіфа, ні раба, ні вільного, але все й у всьому — Христос»',
			),
			array(
				'«Тому ми виступаємо посланцями від імені Христа, ніби сам Бог закликає через нас; від імені Христа благаємо: примиріться з Богом»',
				'«Отже, ми — посли від Імені Христа, і тому наче Сам Бог просить через нас. Від Імені Христа благаємо: примиріться з Богом»',
			),
		),
		'es' => array(
			array(
				'«Queridos, ahora somos hijos de Dios, pero aún no se ha revelado lo que seremos. Sin embargo, sabemos que, cuando se revele, seremos semejantes a él, pues le veremos tal como es»',
				'«Queridos hermanos, ahora somos hijos de Dios, pero todavía no se ha manifestado lo que habremos de ser. Sabemos, sin embargo, que cuando Cristo venga seremos semejantes a él, porque lo veremos tal como él es»',
			),
			array(
				'«En esta renovación ya no hay griego ni judío, circunciso ni incircunciso, extranjero, escita, esclavo ni libre, sino que Cristo lo es todo y está en todos»',
				'«En esta nueva naturaleza no hay judío ni no judío, circunciso ni incircunciso, extranjero, inculto, esclavo o libre, sino que Cristo es todo y está en todos»',
			),
			/*
			 * The published NVI text carries its own «…» around the appeal. Nested
			 * inside the outer «…» that would be unreadable, so the inner pair
			 * becomes “…” — the standard second level in Spanish typography.
			 */
			array(
				'«Por eso, actuamos como enviados de Cristo, como si Dios exhortara a través de nosotros; en nombre de Cristo os suplicamos: reconciliaos con Dios»',
				'«Así que somos embajadores de Cristo, como si Dios los exhortara a ustedes por medio de nosotros: “En nombre de Cristo les rogamos que se reconcilien con Dios”»',
			),
		),
	),

	// --- 119: w sprawie Wieczerzy Pańskiej ---
	119 => array(
		'en' => array(
			/*
			 * The apostrophe here is stored as the entity `&#x27;`, not as a
			 * character — the rest of this document uses a literal `’`. Either way
			 * it renders as the same glyph as the closing single quote, so the
			 * reader could not tell where the quotation ended. The replacement uses
			 * the literal `’` like the surrounding text.
			 */
			array( '‘the Lord&#x27;s Supper’', '“the Lord’s Supper”' ),
			array( '‘our Passover’', '“our Passover lamb”' ),
		),
		'es' => array(
			array( '«nuestra Pascua»', '«nuestro Cordero pascual»' ),
		),
	),

	// --- 108: w sprawie stosunku do organizacji parakościelnych ---
	108 => array(
		'en' => array(
			array( '‘make disciples’', '“make disciples”' ),
			array( '‘equip the saints for the work of ministry’', '“equip his people for works of service”' ),
			array( '‘to grow up into the fullness of Christ’', '“attaining to the whole measure of the fullness of Christ”' ),
		),
		'uk' => array(
			array( '«готувати святих до служіння»', '«приготувати святих для справи служіння»' ),
		),
		'es' => array(
			array( '«preparar a los santos para la obra del ministerio»', '«capacitar al pueblo de Dios para la obra de servicio»' ),
			array( '«alcanzar la plenitud de Cristo»', '«alcanzar la plena estatura de Cristo»' ),
		),
	),

	// --- 106: w sprawie małżeństwa, rozwodu, powtórnego małżeństwa ---
	106 => array(
		'en' => array(
			array( '‘they shall become one flesh’', '“they become one flesh”' ),
		),
		'uk' => array(
			array( '«вони стануть одним тілом»', '«будуть обоє одним тілом»' ),
		),
		'es' => array(
			array( '«serán una sola carne»', '«los dos llegarán a ser uno solo»' ),
		),
	),
);
