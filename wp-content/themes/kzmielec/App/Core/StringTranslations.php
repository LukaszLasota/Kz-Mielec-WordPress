<?php
/**
 * Panel-editable overrides for the theme's interface strings.
 *
 * @package Kzmielec
 */

declare(strict_types=1);

namespace Kzmielec\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lets the interface strings be corrected from the WordPress admin, without
 * giving up the catalogue files as the source of truth.
 *
 * The obvious way to do this — swapping every `esc_html_e( 'X', 'kzmielec' )` for
 * a custom wrapper that calls `pll__()` — is worse in two ways. It hides the
 * strings from `wp i18n make-pot`, which only scans known gettext functions, so
 * the POT stops being generated correctly. And `pll__()` returns the Polish
 * source whenever a string has not been typed into the panel, which would make
 * the panel a downgrade from the files rather than an addition to them.
 *
 * So the precedence runs the other way round: the `.mo` catalogue is the default
 * and correct answer, and the panel wins only where somebody has deliberately
 * entered something. Turning Polylang off collapses the filter to a pass-through
 * and the site keeps working on the files alone; turning it back on restores the
 * overrides. That is the fallback in both directions.
 */
class StringTranslations {

	/**
	 * Translation domain these overrides apply to.
	 */
	private const DOMAIN = 'kzmielec';

	/**
	 * Group the strings appear under in Languages → Translations.
	 */
	private const GROUP = 'Motyw kzmielec';

	/**
	 * Source string => label shown next to it in the admin.
	 *
	 * Only strings a visitor can actually read. The POT also holds 67 admin and
	 * editor strings — metabox labels, settings screens — which are seen solely
	 * by the person editing the site, who works in Polish. Registering those
	 * would pad the panel with rows nobody will ever fill in.
	 *
	 * This list doubles as the filter's fast path: a key lookup decides in O(1)
	 * whether a string is ours, so the `gettext` filter costs nothing on the
	 * several thousand strings Yoast and LiteSpeed push through it per request.
	 *
	 * @var array<string, string>
	 */
	public const STRINGS = array(
		// Header and navigation.
		'Przejdź do treści'                           => 'Nagłówek: link pomijający nawigację',
		'strona główna'                               => 'Nagłówek: opis linku logo',
		'Otwórz/zamknij menu'                         => 'Nagłówek: przycisk menu na telefonie',
		'Nawigacja główna'                            => 'Nagłówek: nazwa nawigacji dla czytników ekranu',
		'Menu przyklejone'                            => 'Nagłówek: nazwa menu przyklejonego',

		// Accessibility strip.
		'Ustawienia dostępności'                      => 'Belka dostępności: nazwa grupy',
		'Wybór języka'                                => 'Belka dostępności: nazwa przełącznika języka',
		'Rozmiar tekstu'                              => 'Belka dostępności: etykieta rozmiaru',
		'A — rozmiar standardowy'                     => 'Belka dostępności: rozmiar standardowy',
		'A+ — tekst powiększony'                      => 'Belka dostępności: rozmiar powiększony',
		'A++ — tekst największy'                      => 'Belka dostępności: rozmiar największy',
		'Wysoki kontrast'                             => 'Belka dostępności: kontrast (forma długa)',
		'Kontrast'                                    => 'Belka dostępności: kontrast (forma krótka na telefon)',

		// Meetings and beliefs.
		'Zaplanuj wizytę'                             => 'Spotkania: tytuł archiwum',
		'W co i jak wierzymy'                         => 'Wiara: tytuł sekcji',
		'Przewiń do nawigacji wiary'                  => 'Wiara: przycisk przewijania',

		// Search.
		'Szukaj na stronie'                           => 'Wyszukiwanie: nazwa formularza',
		'Czego szukasz?'                              => 'Wyszukiwanie: podpowiedź w polu',
		'Szukaj'                                      => 'Wyszukiwanie: przycisk',
		'Wyniki wyszukiwania: %s'                     => 'Wyszukiwanie: tytuł wyników',
		'Brak wyników dla %s. Spróbuj innej frazy.'   => 'Wyszukiwanie: brak wyników',
		'Aktualność'                                  => 'Wyszukiwanie: etykieta typu — wpis',
		'Strona'                                      => 'Wyszukiwanie: etykieta typu — strona',
		'Spotkanie'                                   => 'Wyszukiwanie: etykieta typu — spotkanie',

		// Pagination.
		'Paginacja'                                   => 'Paginacja: nazwa nawigacji',
		'Poprzednia strona'                           => 'Paginacja: poprzednia',
		'Następna strona'                             => 'Paginacja: następna',
		'« Poprzednia'                                => 'Paginacja wyników: poprzednia',
		'Następna »'                                  => 'Paginacja wyników: następna',
		'Brak postów.'                                => 'Lista wpisów: pusto',

		// 404.
		'Nie znaleziono strony'                       => '404: tytuł',
		'Wróć na stronę główną'                       => '404: powrót na stronę główną',
		'Strona, której szukasz, nie istnieje lub została przeniesiona. Spróbuj wyszukać poniżej albo wróć na stronę główną.' => '404: treść',

		// Contact details. The data itself lives in one option and is never translated;
		// only these words around it are. Placeholders must survive translation, which
		// is why each label says what its `%s` carries.
		'ul. %1$s, %2$s %3$s'                         => 'Kontakt: linia adresu (1 = ulica z numerem, 2 = kod, 3 = miasto)',
		'tel.: %s – pastor Zboru, Dariusz R. Hapoń'   => 'Kontakt: linia telefonu (%s = numer)',
		'Uwaga: z tego numeru nie odczytujemy smsów.' => 'Kontakt: uwaga o SMS-ach',
		'W celu kontaktu pisemnego prosimy użyć poczty email lub kontaktu ze Zborem poprzez messenger (facebook).' => 'Kontakt: uwaga o kontakcie pisemnym',
		'NIP: %s'                                     => 'Kontakt: linia NIP (%s = numer)',
		'email: %s'                                   => 'Kontakt: linia e-mail (%s = adres z odnośnikiem)',
		'konto: %s'                                   => 'Kontakt: linia konta (%s = numer)',

		// Footer.
		'Social media'                                => 'Stopka: nazwa sekcji z ikonami',
		'Copyright %1$s. Wszelkie prawa zastrzeżone &copy; %2$s.' => 'Stopka: nota o prawach autorskich',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'gettext', array( $this, 'prefer_panel_translation' ), 10, 3 );
	}

	/**
	 * Expose the strings in Languages → Translations.
	 *
	 * Admin-only: registration exists purely to draw the rows on that screen, so
	 * running it on the front end would be work with no output.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! is_admin() || ! function_exists( 'pll_register_string' ) ) {
			return;
		}

		foreach ( self::STRINGS as $string => $label ) {
			// Multiline for the one string long enough to need a textarea.
			pll_register_string( $label, $string, self::GROUP, strlen( $string ) > 80 );
		}
	}

	/**
	 * Let a panel entry override the catalogue, but never replace it with nothing.
	 *
	 * @param string $translation Translation WordPress resolved from the catalogue.
	 * @param string $text        Original source string.
	 * @param string $domain      Text domain the string belongs to.
	 * @return string
	 */
	public function prefer_panel_translation( $translation, $text, $domain ): string {
		$translation = (string) $translation;

		if ( self::DOMAIN !== $domain || ! isset( self::STRINGS[ (string) $text ] ) ) {
			return $translation;
		}

		if ( ! function_exists( 'pll__' ) ) {
			return $translation;
		}

		$panel = (string) pll__( (string) $text );

		/*
		 * `pll__()` hands back the source string when nothing has been entered, so
		 * an unedited row is indistinguishable from a row typed to equal the
		 * source. Treating both as "no override" is the right call: in either case
		 * the catalogue holds the better answer.
		 */
		return ( '' !== $panel && $panel !== (string) $text ) ? $panel : $translation;
	}
}
