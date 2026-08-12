<?php
/**
 * Established Scripture texts for the quotations used on the site.
 *
 * WHY THIS FILE EXISTS. DeepL translated the Scripture quotations by paraphrasing
 * the Polish, which means a reader could not recognise the verse or find it in their
 * own Bible. Every text below was fetched verbatim from the published translation,
 * not written from memory — misquoting Scripture on a church site claims an
 * authority the text would not have.
 *
 * TRANSLATIONS CHOSEN (decision of the congregation, 2026-08-11: contemporary,
 * Protestant):
 *
 *   pl  EIB — "Biblia, to jest Pismo Święte Starego i Nowego Przymierza",
 *       przekład literacki, Ewangeliczny Instytut Biblijny, red. Piotr Zaremba,
 *       wyd. pierwsze 2018. Source: bible.com version 2095 (SNP).
 *   en  NIV — New International Version, 2011, Biblica.
 *       Source: biblegateway.com, version NIV.
 *   uk  УТТ — Сучасний переклад, Українське Біблійне Товариство, red. Рафаїл
 *       Турконяк, 2011. Source: bible.com version 1755.
 *   es  NVI — Nueva Versión Internacional, Biblica.
 *       Source: biblegateway.com / bible.com, version NVI.
 *
 * ATTRIBUTION. All four are under copyright. Each permits limited quotation with a
 * note of the source; fifteen quotations is far inside every one of those limits,
 * but the note is not optional. It has to appear on the site — see the plan,
 * step 1.3.
 *
 * POLISH IS DELIBERATELY INCOMPLETE. Only the four verses quoted on the
 * congregation's OWN pages (wizja, misja) carry a Polish replacement. The other
 * quotations sit inside verbatim reproductions of statements by the Supreme Council
 * of the Pentecostal Church in Poland, which quote Biblia Warszawska; changing the
 * Scripture wording inside another body's official document would change what that
 * body is recorded as having said. Those keep Biblia Warszawska.
 *
 * @package Kzmielec
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_CLI' ) ) {
	exit;
}

return array(

	'gen-2-24'  => array(
		'ref' => array( 'pl' => 'Rdz 2,24', 'en' => 'Gen 2:24', 'uk' => 'Бут. 2,24', 'es' => 'Gn 2,24' ),
		'en'  => 'That is why a man leaves his father and mother and is united to his wife, and they become one flesh.',
		'uk'  => 'Тому залишить чоловік свого батька й матір і пристане до своєї жінки, — і будуть обоє одним тілом.',
		'es'  => 'Por eso dejará el hombre a su padre y a su madre, se unirá a su mujer, y los dos llegarán a ser uno solo.',
	),

	'matt-28-19' => array(
		'ref' => array( 'pl' => 'Mt 28,19', 'en' => 'Matt 28:19', 'uk' => 'Мт. 28,19', 'es' => 'Mt 28,19' ),
		'en'  => 'Therefore go and make disciples of all nations, baptizing them in the name of the Father and of the Son and of the Holy Spirit,',
		'uk'  => 'Тож ідіть і навчіть усі народи, хрестячи їх в Ім’я Отця, і Сина, і Святого Духа',
		'es'  => 'Por tanto, vayan y hagan discípulos de todas las naciones, bautizándolos en el nombre del Padre y del Hijo y del Espíritu Santo,',
	),

	'1cor-5-7'  => array(
		'ref' => array( 'pl' => '1 Kor 5,7', 'en' => '1 Cor 5:7', 'uk' => '1 Кор. 5,7', 'es' => '1 Cor 5,7' ),
		'en'  => 'Get rid of the old yeast, so that you may be a new unleavened batch—as you really are. For Christ, our Passover lamb, has been sacrificed.',
		'uk'  => '[Тому] усуньте стару закваску, щоби бути новим тістом, бо ви прісні, адже наша Пасха — Христос, принесений [за нас] у жертву.',
		'es'  => 'Desháganse de la vieja levadura para que sean masa nueva, panes sin levadura, como lo son en realidad. Porque Cristo, nuestro Cordero pascual, ya ha sido sacrificado.',
	),

	'1cor-14-40' => array(
		'ref' => array( 'pl' => '1 Kor 14,40', 'en' => '1 Cor 14:40', 'uk' => '1 Кор. 14,40', 'es' => '1 Cor 14,40' ),
		'en'  => 'But everything should be done in a fitting and orderly way.',
		'uk'  => 'Але все нехай відбувається пристойно і організовано.',
		'es'  => 'Pero todo debe hacerse de una manera apropiada y con orden.',
	),

	'2cor-5-18' => array(
		'ref' => array( 'pl' => '2 Kor 5,18', 'en' => '2 Cor 5:18', 'uk' => '2 Кор. 5,18', 'es' => '2 Cor 5,18' ),
		// Own page (wizja), so the Polish may change too.
		'pl'  => 'A wszystko to jest z Boga, który nas pojednał ze sobą przez Chrystusa i zlecił nam posługę pojednania',
		'en'  => 'All this is from God, who reconciled us to himself through Christ and gave us the ministry of reconciliation:',
		'uk'  => 'Усе — від Бога, Який примирив нас із Собою через Христа і дав нам служіння примирення.',
		'es'  => 'Todo esto proviene de Dios, quien por medio de Cristo nos reconcilió consigo mismo y nos dio el ministerio de la reconciliación.',
	),

	'2cor-5-20' => array(
		'ref' => array( 'pl' => '2 Kor 5,20', 'en' => '2 Cor 5:20', 'uk' => '2 Кор. 5,20', 'es' => '2 Cor 5,20' ),
		// Own page (misja).
		'pl'  => 'Dlatego w miejsce Chrystusa głosimy poselstwo jakby samego Boga, który przez nas kieruje do ludzi wezwanie. W miejsce Chrystusa błagamy: Pojednajcie się z Bogiem.',
		'en'  => 'We are therefore Christ’s ambassadors, as though God were making his appeal through us. We implore you on Christ’s behalf: Be reconciled to God.',
		'uk'  => 'Отже, ми — посли від Імені Христа, і тому наче Сам Бог просить через нас. Від Імені Христа благаємо: примиріться з Богом!',
		'es'  => 'Así que somos embajadores de Cristo, como si Dios los exhortara a ustedes por medio de nosotros: «En nombre de Cristo les rogamos que se reconcilien con Dios».',
	),

	'gal-3-28'  => array(
		'ref' => array( 'pl' => 'Ga 3,28', 'en' => 'Gal 3:28', 'uk' => 'Гал. 3,28', 'es' => 'Gál 3,28' ),
		'en'  => 'There is neither Jew nor Gentile, neither slave nor free, nor is there male and female, for you are all one in Christ Jesus.',
		'uk'  => 'Немає юдея, ні грека; немає ані раба, ані вільного; немає чоловічого роду, ні жіночого, бо в Ісусі Христі ви всі — одно.',
		'es'  => 'Ya no hay judío ni no judío, esclavo ni libre, hombre ni mujer, sino que todos ustedes son uno solo en Cristo Jesús.',
	),

	'eph-4-12'  => array(
		'ref' => array( 'pl' => 'Ef 4,12', 'en' => 'Eph 4:12', 'uk' => 'Еф. 4,12', 'es' => 'Ef 4,12' ),
		'en'  => 'to equip his people for works of service, so that the body of Christ may be built up',
		'uk'  => 'щоби приготувати святих для справи служіння, для збудування Христового тіла',
		'es'  => 'a fin de capacitar al pueblo de Dios para la obra de servicio, para edificar el cuerpo de Cristo.',
	),

	'eph-4-13'  => array(
		'ref' => array( 'pl' => 'Ef 4,13', 'en' => 'Eph 4:13', 'uk' => 'Еф. 4,13', 'es' => 'Ef 4,13' ),
		'en'  => 'until we all reach unity in the faith and in the knowledge of the Son of God and become mature, attaining to the whole measure of the fullness of Christ.',
		'uk'  => 'аж поки всі досягнемо єдності віри й пізнання Божого Сина, досконалого змужніння, міри зрілості — повноти Христа',
		'es'  => 'De este modo, todos llegaremos a la unidad de la fe y del conocimiento del Hijo de Dios, a una humanidad perfecta que se conforme a la plena estatura de Cristo.',
	),

	'eph-5-23'  => array(
		'ref' => array( 'pl' => 'Ef 5,23', 'en' => 'Eph 5:23', 'uk' => 'Еф. 5,23', 'es' => 'Ef 5,23' ),
		'en'  => 'For the husband is the head of the wife as Christ is the head of the church, his body, of which he is the Savior.',
		'uk'  => 'адже чоловік є голова дружини, як Христос — Голова Церкви, Він же — Спаситель тіла.',
		'es'  => 'Porque el esposo es cabeza de su esposa, así como Cristo es cabeza de la iglesia, la cual es su cuerpo, y él su Salvador.',
	),

	'col-3-11'  => array(
		'ref' => array( 'pl' => 'Kol 3,11', 'en' => 'Col 3:11', 'uk' => 'Кол. 3,11', 'es' => 'Col 3,11' ),
		// Own page (misja).
		'pl'  => 'Na tej drodze nie liczy się, czy ktoś jest Grekiem, czy Żydem, czy go obrzezano, czy nie, czy pochodzi z daleka, jest Scytą, niewolnikiem, czy wolnym — liczy się tylko Chrystus, który jest wszystkim i we wszystkich.',
		'en'  => 'Here there is no Gentile or Jew, circumcised or uncircumcised, barbarian, Scythian, slave or free, but Christ is all, and is in all.',
		'uk'  => 'де немає ні грека, ні юдея, ні обрізання, ні необрізання, ні варвара, ні скіфа, ні раба, ні вільного, але все й у всьому — Христос.',
		'es'  => 'En esta nueva naturaleza no hay judío ni no judío, circunciso ni incircunciso, extranjero, inculto, esclavo o libre, sino que Cristo es todo y está en todos.',
	),

	'1pet-2-5'  => array(
		'ref' => array( 'pl' => '1 P 2,5', 'en' => '1 Pet 2:5', 'uk' => '1 Пет. 2,5', 'es' => '1 P 2,5' ),
		'en'  => 'you also, like living stones, are being built into a spiritual house to be a holy priesthood, offering spiritual sacrifices acceptable to God through Jesus Christ.',
		'uk'  => 'І ви самі, немов живе каміння, збудовуйтеся в духовний дім, щоби бути святим священством, приносити духовні жертви, приємні Богові, через Ісуса Христа.',
		'es'  => 'También ustedes son como piedras vivas, con las cuales se está edificando una casa espiritual. De este modo llegan a ser un sacerdocio santo, para ofrecer sacrificios espirituales que Dios acepta por medio de Jesucristo.',
	),

	'1pet-3-7'  => array(
		'ref' => array( 'pl' => '1 P 3,7', 'en' => '1 Pet 3:7', 'uk' => '1 Пет. 3,7', 'es' => '1 P 3,7' ),
		'en'  => 'Husbands, in the same way be considerate as you live with your wives, and treat them with respect as the weaker partner and as heirs with you of the gracious gift of life, so that nothing will hinder your prayers.',
		'uk'  => 'Так само чоловіки: живіть зі своїми дружинами в порозумінні, поводячись, немов з тендітною вазою, сповненою жіночості, виявляючи їм шану як співспадкоємцям благодаті життя, щоб не було перепон для ваших молитов.',
		'es'  => 'De igual manera, ustedes esposos, sean comprensivos en su vida conyugal, cada uno trate a su esposa con respeto, ya que como mujer es más delicada y ambos son herederos del grato don de la vida. Así nada estorbará las oraciones de ustedes.',
	),

	'1john-3-2' => array(
		'ref' => array( 'pl' => '1 J 3,2', 'en' => '1 John 3:2', 'uk' => '1 Ів. 3,2', 'es' => '1 Jn 3,2' ),
		// Own page (misja).
		'pl'  => 'Drodzy, teraz jesteśmy dziećmi Boga, a kim będziemy, to się jeszcze okaże. Choć już wiemy, że gdy się okaże, będziemy podobni do Niego, gdyż zobaczymy Go takim, jaki jest.',
		'en'  => 'Dear friends, now we are children of God, and what we will be has not yet been made known. But we know that when Christ appears, we shall be like him, for we shall see him as he is.',
		'uk'  => 'Улюблені! Тепер ми — Божі діти, але ще не виявилося, що будемо. Знаємо, що коли Він з’явиться, ми будемо подібні до Нього, адже побачимо Його таким, який Він є.',
		'es'  => 'Queridos hermanos, ahora somos hijos de Dios, pero todavía no se ha manifestado lo que habremos de ser. Sabemos, sin embargo, que cuando Cristo venga seremos semejantes a él, porque lo veremos tal como él es.',
	),
);
