<?php
/**
 * Hand-written meta descriptions for every page, in all four languages.
 *
 * WHY. Before this, not one of the 88 descriptions was written. Yoast fell through
 * to `YoastFallbacks::fallback_description()`, which cuts the first 155 characters
 * out of the page content — so the description of the home page, in every language,
 * was the navigation menu followed by the street address and the pastor's phone
 * number. Seven descriptions per language were under 120 characters and three were
 * duplicates of each other.
 *
 * The foreign versions are WRITTEN, not translated. A search-engine description is
 * not a sentence from the page; it is a separate short text that has to carry the
 * phrases someone would actually type in that language. Translating the Polish
 * description word for word would inherit Polish search habits.
 *
 * Length target 120-158 characters: under 120 wastes the space Google gives, over
 * 158 gets cut mid-word on desktop. Every entry here is inside that window, checked
 * by the script that writes them.
 *
 * Keyed by the POLISH post id. The script resolves each translation through
 * Polylang, so adding a language later needs no change here beyond the texts.
 *
 * @package Kzmielec
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_CLI' ) ) {
	exit;
}

return array(

	// Strona główna.
	131 => array(
		'pl' => 'Kościół Zielonoświątkowy Zbór w Mielcu. Nabożeństwo w niedziele o 10:30, ul. Przemysłowa 2. Poznaj wspólnotę, w co wierzymy, i zaplanuj pierwszą wizytę.',
		'en' => 'Pentecostal church in Mielec, Poland. Sunday service at 10:30, 2 Przemysłowa Street. Meet the congregation, see what we believe and plan your first visit.',
		'uk' => 'Пентекостальна церква в Мельці, Польща. Богослужіння щонеділі о 10:30, вул. Przemysłowa 2. Знайомтеся зі спільнотою та плануйте перший візит до нас.',
		'es' => 'Iglesia pentecostal en Mielec, Polonia. Culto los domingos a las 10:30, calle Przemysłowa 2. Conoce la congregación, nuestra fe y planifica tu visita.',
	),

	// Różnica wyznań — porównanie zielonoświątkowe / rzymskokatolickie.
	83  => array(
		'pl' => 'Czym różni się wiara zielonoświątkowa od rzymskokatolickiej? Porównanie 37 tematów: Bóg, zbawienie, sakramenty, kult, święta, eschatologia i etyka.',
		'en' => 'How does Pentecostal faith differ from Roman Catholic teaching? A side-by-side comparison of 37 topics: God, salvation, sacraments, worship and ethics.',
		'uk' => 'Чим пентекостальна віра відрізняється від римо-католицької? Порівняння 37 тем: Бог, спасіння, таїнства, богослужіння, свята, етика та останні часи.',
		'es' => '¿En qué se diferencia la fe pentecostal de la enseñanza católica romana? Comparación de 37 temas: Dios, salvación, sacramentos, culto, fiestas y ética.',
	),

	// W co wierzymy.
	2   => array(
		'pl' => 'Wyznanie wiary zboru: Biblia jako natchnione Słowo Boże, Trójca Święta, zbawienie z łaski przez wiarę, chrzest wiary i chrzest w Duchu Świętym.',
		'en' => 'What we believe: the Bible as the inspired Word of God, the Holy Trinity, salvation by grace through faith, baptism of faith and baptism in the Spirit.',
		'uk' => 'У що ми віримо: Біблія як натхнене Слово Боже, Свята Тройця, спасіння благодаттю через віру, хрещення віри та хрещення Святим Духом.',
		'es' => 'En qué creemos: la Biblia como Palabra de Dios inspirada, la Santísima Trinidad, la salvación por gracia mediante la fe y el bautismo en el Espíritu.',
	),

	// Wizja.
	77  => array(
		'pl' => 'Bliscy Boga, bliscy sobie, bliscy innym. Wizja zboru w Mielcu: przybliżać ludzi do Boga i budować wspólnotę, która wychodzi do potrzebujących nadziei.',
		'en' => 'Close to God, close to each other, close to others. The vision of the Mielec congregation: to draw people to God and build a community that carries hope.',
		'uk' => 'Близькі до Бога, близькі одне одному, близькі до інших. Візія громади в Мельці: наближати людей до Бога й будувати спільноту, яка несе надію.',
		'es' => 'Cerca de Dios, cerca unos de otros, cerca de los demás. La visión de la congregación de Mielec: acercar a las personas a Dios y llevar esperanza.',
	),

	// Misja.
	65  => array(
		'pl' => 'Wzrost, wspólnota, wpływ. Misja zboru w Mielcu: rozwijać relację z Bogiem, budować relacje między ludźmi i nieść nadzieję tam, gdzie jest potrzebna.',
		'en' => 'Growth, community, influence. The mission of the Mielec congregation: to deepen relationship with God, build relationships and bring hope where needed.',
		'uk' => 'Зростання, спільнота, вплив. Місія громади в Мельці: розвивати стосунки з Богом, будувати взаємини між людьми та нести надію туди, де вона потрібна.',
		'es' => 'Crecimiento, comunidad, influencia. La misión de la congregación de Mielec: profundizar la relación con Dios, construir vínculos y llevar esperanza.',
	),

	// Wartości.
	79  => array(
		'pl' => 'Wartości zboru w Mielcu: kultura uwielbienia, dojrzewanie i rozwój w wolności, wzajemne usługiwanie. Czym kierujemy się w codziennym życiu wspólnoty.',
		'en' => 'The values of the Mielec congregation: a culture of worship, growing to maturity in freedom, and serving one another in the everyday life of the church.',
		'uk' => 'Цінності громади в Мельці: культура прославлення, зростання до зрілості у свободі та взаємне служіння в щоденному житті церкви.',
		'es' => 'Los valores de la congregación de Mielec: una cultura de adoración, el crecimiento hacia la madurez en libertad y el servicio mutuo en la iglesia.',
	),

	// Historia zboru.
	81  => array(
		'pl' => 'Historia zboru w Mielcu: od Stacji Misyjnej w 1993 roku i Misji Namiotowej, przez spotkania domowe, do powołania samodzielnego Zboru 8 marca 1994 roku.',
		'en' => 'The history of the Mielec congregation: from the 1993 mission station and tent mission, through house meetings, to a congregation of its own in 1994.',
		'uk' => 'Історія громади в Мельці: від місійної станції 1993 року та намету євангелізації, через домашні зібрання, до створення самостійної громади в 1994 році.',
		'es' => 'La historia de la congregación de Mielec: de la estación misionera de 1993 y la misión en tienda a una congregación propia el 8 de marzo de 1994.',
	),

	// Prawo — ustawa o stosunku Państwa do Kościoła.
	88  => array(
		'pl' => 'Ustawa o stosunku Państwa do Kościoła Zielonoświątkowego w RP: sytuacja prawna i majątkowa Kościoła, jego struktura, działalność i uprawnienia.',
		'en' => 'The Polish Act on the relationship between the State and the Pentecostal Church: the legal and property status of the Church, its structure and rights.',
		'uk' => 'Закон про відносини держави та Пентекостальної церкви в Польщі: правовий і майновий статус церкви, її структура, діяльність і повноваження.',
		'es' => 'La ley polaca sobre la relación entre el Estado y la Iglesia Pentecostal: situación jurídica y patrimonial de la Iglesia, su estructura y derechos.',
	),

	// Polityka prywatności i RODO.
	90  => array(
		'pl' => 'Polityka prywatności zboru w Mielcu: kto jest administratorem danych, w jakim celu je przetwarzamy i jakie prawa przysługują Ci na podstawie RODO.',
		'en' => 'Privacy policy of the Mielec congregation: who controls your personal data, why we process it and what rights the GDPR gives you over that data.',
		'uk' => 'Політика приватності громади в Мельці: хто є розпорядником персональних даних, з якою метою ми їх обробляємо та які права дає вам GDPR.',
		'es' => 'Política de privacidad de la congregación de Mielec: quién es el responsable de los datos, con qué fin los tratamos y qué derechos le da el RGPD.',
	),

	// Polityka ochrony dzieci.
	307 => array(
		'pl' => 'Polityka ochrony dzieci przed krzywdzeniem: zasady pracy z dziećmi w Służbie Katechetycznej, obowiązki pracowników i wolontariuszy, tryb zgłaszania.',
		'en' => 'Child protection policy: the rules for working with children in the catechetical ministry, the duties of staff and volunteers, and how to report concerns.',
		'uk' => 'Політика захисту дітей від насильства: правила роботи з дітьми в катехитичній службі, обов’язки працівників і волонтерів, порядок повідомлення.',
		'es' => 'Política de protección de la infancia: normas para el trabajo con menores en el ministerio catequético, deberes del personal y cómo dar aviso.',
	),

	// Chrzest wiary.
	101 => array(
		'pl' => 'Stanowisko Naczelnej Rady Kościoła w sprawie chrztu wiary i błogosławieństwa dzieci: znaczenie zanurzenia, warunki chrztu i miejsce dzieci w Kościele.',
		'en' => 'The Supreme Church Council on the baptism of faith and the blessing of children: the meaning of immersion, who may be baptised, and children in church.',
		'uk' => 'Позиція Головної Ради Церкви щодо хрещення віри та благословення дітей: значення занурення, умови хрещення й місце дітей у церкві.',
		'es' => 'Postura del Consejo Supremo de la Iglesia sobre el bautismo de fe y la bendición de los niños: el sentido de la inmersión y el lugar de los niños.',
	),

	// Zjawiska towarzyszące duchowemu ożywieniu.
	103 => array(
		'pl' => 'Stanowisko Naczelnej Rady Kościoła w sprawie zjawisk towarzyszących duchowemu ożywieniu: jak rozpoznawać i porządkować doświadczenia w zgromadzeniu.',
		'en' => 'The Supreme Church Council on phenomena accompanying spiritual revival: how such experiences are to be discerned and kept orderly in the congregation.',
		'uk' => 'Позиція Головної Ради Церкви щодо явищ, які супроводжують духовне пробудження: як їх розпізнавати та впорядковувати в зібранні.',
		'es' => 'Postura del Consejo Supremo de la Iglesia sobre los fenómenos que acompañan el avivamiento: cómo discernirlos y mantener el orden en la asamblea.',
	),

	// Małżeństwo, rozwód, planowanie rodziny.
	106 => array(
		'pl' => 'Stanowisko Naczelnej Rady Kościoła w sprawie małżeństwa, rozwodu, powtórnego małżeństwa i planowania rodziny — nauczanie oparte na Piśmie Świętym.',
		'en' => 'The Supreme Church Council on marriage, divorce, remarriage and family planning: Pentecostal teaching on each, grounded in the Scriptures.',
		'uk' => 'Позиція Головної Ради Церкви щодо шлюбу, розлучення, повторного шлюбу та планування сім’ї — вчення, засноване на Святому Письмі.',
		'es' => 'Postura del Consejo Supremo de la Iglesia sobre el matrimonio, el divorcio, las segundas nupcias y la planificación familiar, según las Escrituras.',
	),

	// Organizacje parakościelne.
	108 => array(
		'pl' => 'Stanowisko Naczelnej Rady Kościoła w sprawie organizacji parakościelnych: jaka jest ich rola wobec Kościoła i na jakich zasadach z nimi współpracować.',
		'en' => 'The Supreme Church Council on parachurch organisations: what place they hold in relation to the Church and on what terms cooperation is possible.',
		'uk' => 'Позиція Головної Ради Церкви щодо парацерковних організацій: яка їхня роль стосовно Церкви та на яких засадах можлива співпраця з ними.',
		'es' => 'Postura del Consejo Supremo de la Iglesia sobre las organizaciones paraeclesiales: su papel respecto a la Iglesia y las bases de la cooperación.',
	),

	// Ruch wstawienniczy.
	110 => array(
		'pl' => 'Stanowisko Naczelnej Rady Kościoła w sprawie tzw. ruchu wstawienniczego: miejsce modlitwy i postu oraz ostrzeżenia wobec praktyk masowych konferencji.',
		'en' => 'The Supreme Church Council on the intercessory movement: the place of prayer and fasting, and cautions about the practices of mass conferences.',
		'uk' => 'Позиція Головної Ради Церкви щодо так званого заступницького руху: місце молитви й посту та застереження щодо практик масових конференцій.',
		'es' => 'Postura del Consejo Supremo de la Iglesia sobre el movimiento de intercesión: el lugar de la oración y el ayuno y cautelas sobre las grandes conferencias.',
	),

	// Służba kobiet.
	115 => array(
		'pl' => 'Stanowisko Naczelnej Rady Kościoła w sprawie służby kobiet w Kościele: równy udział w służbie charyzmatycznej i porządek przywództwa we wspólnocie.',
		'en' => 'The Supreme Church Council on the ministry of women in the Church: their equal share in charismatic ministry and the order of leadership in the church.',
		'uk' => 'Позиція Головної Ради Церкви щодо служіння жінок у Церкві: рівна участь у харизматичному служінні та порядок керівництва у спільноті.',
		'es' => 'Postura del Consejo Supremo de la Iglesia sobre el ministerio de las mujeres: su participación en el ministerio carismático y el orden del liderazgo.',
	),

	// Służba uwolnienia.
	117 => array(
		'pl' => 'Stanowisko Naczelnej Rady Kościoła w sprawie służby uwolnienia: zwycięstwo Chrystusa nad złymi duchami, granice tej służby i ostrzeżenia przed nadużyciami.',
		'en' => 'The Supreme Church Council on deliverance ministry: Christ’s victory over evil spirits, the limits of such ministry and warnings against its misuse.',
		'uk' => 'Позиція Головної Ради Церкви щодо служіння визволення: перемога Христа над злими духами, межі цього служіння та застереження від зловживань.',
		'es' => 'Postura del Consejo Supremo de la Iglesia sobre el ministerio de liberación: la victoria de Cristo, los límites del ministerio y advertencias de abuso.',
	),

	// Wieczerza Pańska.
	119 => array(
		'pl' => 'Stanowisko Naczelnej Rady Kościoła w sprawie Wieczerzy Pańskiej: jej ustanowienie, znaczenie chleba i wina oraz duchowa obecność Chrystusa w niej.',
		'en' => 'The Supreme Church Council on the Lord’s Supper: how it was instituted, what the bread and wine signify, and the spiritual presence of Christ in it.',
		'uk' => 'Позиція Головної Ради Церкви щодо Вечері Господньої: її встановлення, значення хліба й вина та духовна присутність Христа в ній.',
		'es' => 'Postura del Consejo Supremo de la Iglesia sobre la Cena del Señor: su institución, el significado del pan y el vino y la presencia de Cristo en ella.',
	),

	// Nabożeństwo Główne.
	204 => array(
		'pl' => 'Nabożeństwo Główne w każdą niedzielę o 10:30 w Mielcu, ul. Przemysłowa 2. Uwielbienie, kazanie i modlitwa — najważniejsze spotkanie tygodnia w zborze.',
		'en' => 'Main service every Sunday at 10:30 in Mielec, 2 Przemysłowa Street. Worship, preaching and prayer — the most important gathering of our week.',
		'uk' => 'Головне богослужіння щонеділі о 10:30 у Мельці, вул. Przemysłowa 2. Прославлення, проповідь і молитва — головне зібрання тижня в нашій громаді.',
		'es' => 'Culto principal todos los domingos a las 10:30 en Mielec, calle Przemysłowa 2. Alabanza, predicación y oración: nuestra reunión más importante.',
	),

	// Mała Kawka.
	205 => array(
		'pl' => 'Mała Kawka — nasza kawiarenka otwarta po każdym spotkaniu, zwłaszcza po niedzielnym nabożeństwie. Czas na rozmowę przy kawie i herbacie. Zapraszamy.',
		'en' => 'Coffee and Chat — our little café, open after every meeting and especially after the Sunday service. Time to talk over coffee or tea. Everyone welcome.',
		'uk' => 'Кава та розмова — наша кав’ярня, відкрита після кожного зібрання, особливо після недільного богослужіння. Час на розмову за кавою чи чаєм.',
		'es' => 'Café y conversación: nuestra pequeña cafetería, abierta tras cada reunión y sobre todo tras el culto del domingo. Un rato de charla con café o té.',
	),

	// Studium Słowa i modlitwa.
	206 => array(
		'pl' => 'Studium Słowa i modlitwa — piątkowe spotkania w mniejszych grupach. Rozważamy Biblię i modlimy się w swobodnej atmosferze wzajemnego usługiwania.',
		'en' => 'Bible study and prayer — our Friday meetings in smaller groups. We read the Bible together and pray, in a relaxed setting of serving one another.',
		'uk' => 'Вивчення Слова та молитва — п’ятничні зібрання в невеликих групах. Разом розмірковуємо над Біблією та молимося у вільній атмосфері служіння.',
		'es' => 'Estudio de la Palabra y oración: reuniones de los viernes en grupos pequeños. Leemos la Biblia juntos y oramos en un ambiente de servicio mutuo.',
	),
);
