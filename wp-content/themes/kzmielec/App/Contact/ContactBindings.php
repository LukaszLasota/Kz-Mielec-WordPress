<?php
/**
 * Feeds the contact data into block content through the core Block Bindings API.
 *
 * @package Kzmielec
 */

declare(strict_types=1);

namespace Kzmielec\Contact;

use Kzmielec\Interfaces\ActionHookInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the `kzmielec/contact` binding source.
 *
 * A bound paragraph stores a pointer instead of the data, so one edit in the settings
 * screen reaches all four language versions at once. The text left inside the paragraph
 * is a fallback: core keeps it whenever this source returns `null`, which is what happens
 * for an unknown key — the page can therefore never render an empty line.
 *
 * Values come from `ContactData`; the words around them come from the translation
 * catalogue, so a translator controls word order through `sprintf` placeholders and the
 * data itself cannot be translated by accident. That distinction is the whole point: an
 * address rendered as "2 Przemysłowa Street" or «вул. Промислова, 2» does not exist in
 * the Polish postal system, and both had happened here before the data was separated
 * from the words.
 */
class ContactBindings implements ActionHookInterface {

	/**
	 * Source name used in `metadata.bindings` in block markup.
	 */
	public const SOURCE = 'kzmielec/contact';

	/**
	 * Keys this source answers, in the order the paragraphs appear on the page.
	 *
	 * @var array<int, string>
	 */
	public const KEYS = array( 'address', 'phone', 'nip', 'email', 'iban' );

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
	}

	/**
	 * Register WordPress action hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'init', array( $this, 'register_source' ) );
		add_action( 'init', array( $this, 'register_phone_text' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_script' ) );
	}

	/**
	 * Register the source with core.
	 *
	 * @return void
	 */
	public function register_source(): void {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		register_block_bindings_source(
			self::SOURCE,
			array(
				'label'              => __( 'Dane kontaktowe zboru', 'kzmielec' ),
				'get_value_callback' => array( $this, 'value' ),
			)
		);
	}

	/**
	 * Core's callback: resolve one bound attribute.
	 *
	 * @param array<string, mixed> $source_args    Arguments from the block markup.
	 * @param mixed                $block_instance Block instance, unused here.
	 * @param string               $attribute_name Attribute being bound.
	 * @return string|null
	 */
	public function value( $source_args, $block_instance, string $attribute_name ): ?string {
		unset( $block_instance );

		/*
		 * Only text content is bound here. Refusing everything else means a stray
		 * binding on some other attribute leaves the block untouched instead of writing
		 * a sentence into, say, an image URL.
		 */
		if ( 'content' !== $attribute_name ) {
			return null;
		}

		$key = isset( $source_args['key'] ) ? (string) $source_args['key'] : '';

		return self::line( $key );
	}

	/**
	 * One ready-to-render line, or `null` for an unknown key.
	 *
	 * Values are escaped before they reach the translated format string; core then runs
	 * the result through `wp_kses_post()`, which is what lets the e-mail line carry a
	 * link.
	 *
	 * @param string $key One of self::KEYS.
	 * @return string|null
	 */
	public static function line( string $key ): ?string {
		$data = ContactData::all();

		switch ( $key ) {
			case 'address':
				return sprintf(
					/* translators: 1: street and number, 2: postal code, 3: town. The street name is data and keeps its Polish form in every language; only the word "street" is translated. */
					__( 'ul. %1$s, %2$s %3$s', 'kzmielec' ),
					esc_html( $data['street'] ),
					esc_html( $data['postcode'] ),
					esc_html( $data['city'] )
				);

			case 'phone':
				return self::phone_block( $data );

			case 'nip':
				// REGON goes on the same paragraph, so no page content had to change
				// to add it.
				$lines = array(
					/* translators: %s: tax identification number. */
					sprintf( __( 'NIP: %s', 'kzmielec' ), esc_html( $data['nip'] ) ),
				);
				if ( '' !== $data['regon'] ) {
					/* translators: %s: REGON, the Polish business register number. */
					$lines[] = sprintf( __( 'REGON: %s', 'kzmielec' ), esc_html( $data['regon'] ) );
				}

				return implode( '<br>', $lines );

			case 'email':
				$link = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( ContactData::email_href() ),
					esc_html( $data['email'] )
				);

				return sprintf(
					/* translators: %s: e-mail address, already wrapped in a link. */
					__( 'email: %s', 'kzmielec' ),
					$link
				);

			case 'iban':
				return sprintf(
					/* translators: %s: bank account number. */
					__( 'konto: %s', 'kzmielec' ),
					esc_html( $data['iban'] )
				);
		}

		return null;
	}

	/**
	 * Label of the phone text in Languages -> Translations.
	 */
	private const PHONE_TEXT_LABEL = 'Kontakt: tekst przy telefonie ({telefon} = numer)';

	/**
	 * Group it appears under, next to the theme's other strings.
	 */
	private const PHONE_TEXT_GROUP = 'Motyw kzmielec';

	/**
	 * Expose the current phone text in Languages -> Translations.
	 *
	 * Registered from the stored value, so the row always holds what the settings
	 * screen holds; a reworded text becomes a new row to translate.
	 *
	 * @return void
	 */
	public function register_phone_text(): void {
		if ( ! is_admin() || ! function_exists( 'pll_register_string' ) ) {
			return;
		}

		$text = ContactData::get( 'phone_text' );
		if ( '' !== $text ) {
			pll_register_string( self::PHONE_TEXT_LABEL, $text, self::PHONE_TEXT_GROUP, true );
		}
	}

	/**
	 * The phone number with the text around it, as set on the settings screen.
	 *
	 * The text is data since 2026-09-23 (it used to be three gettext strings), so it
	 * can be reworded without a code change. `{telefon}` stands for the number, each
	 * line becomes a line. The `<br>` tags are joined here, in code: while these
	 * sentences lived inside translated content, the machine translator moved the tags
	 * around, and markup that never reaches a translator cannot be damaged by one.
	 *
	 * Another language takes its version from Languages -> Translations. Without an
	 * entry there - the text was reworded and nobody translated it yet - it shows just
	 * the number rather than Polish sentences on an English page: degrade to correct.
	 *
	 * @param array<string, string> $data Contact data.
	 * @return string
	 */
	private static function phone_block( array $data ): string {
		$number = esc_html( $data['phone'] );
		/* translators: %s: phone number. Used when there is no text around the number, or no translation of it yet. */
		$minimal = sprintf( __( 'tel.: %s', 'kzmielec' ), $number );

		$text = self::phone_text_in_language( $data['phone_text'] );
		if ( null === $text ) {
			return $minimal;
		}

		$split = preg_split( '/\R/u', $text );
		$lines = array();
		foreach ( is_array( $split ) ? $split : array() as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$lines[] = str_replace( '{telefon}', $number, esc_html( $line ) );
			}
		}

		return $lines ? implode( '<br>', $lines ) : $minimal;
	}

	/**
	 * Whether each non-default language has a translation of the current phone text.
	 *
	 * For the settings screen, which has to say plainly when a reworded text left the
	 * other languages showing only the number.
	 *
	 * @return array<string, array{name: string, done: bool}> Keyed by language slug.
	 */
	public static function phone_text_translations(): array {
		$source = ContactData::get( 'phone_text' );
		if ( '' === $source || ! function_exists( 'pll_languages_list' ) || ! function_exists( 'pll_translate_string' ) ) {
			return array();
		}

		$default = function_exists( 'pll_default_language' ) ? pll_default_language( 'slug' ) : 'pl';
		$slugs   = (array) pll_languages_list( array( 'fields' => 'slug' ) );
		$names   = (array) pll_languages_list( array( 'fields' => 'name' ) );
		$status  = array();

		foreach ( $slugs as $i => $slug ) {
			$slug = (string) $slug;
			if ( $slug === $default ) {
				continue;
			}
			$translated      = (string) pll_translate_string( $source, $slug );
			$status[ $slug ] = array(
				'name' => (string) ( $names[ $i ] ?? $slug ),
				'done' => '' !== trim( $translated ) && $translated !== $source,
			);
		}

		return $status;
	}

	/**
	 * Admin address of Polylang's string translations, filtered to this text.
	 *
	 * @return string
	 */
	public static function phone_text_translations_url(): string {
		return add_query_arg(
			array(
				'page'  => 'mlang_strings',
				'group' => self::PHONE_TEXT_GROUP,
				's'     => 'telefon',
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * The phone text in the language being rendered, or null when there is none.
	 *
	 * The language comes from the locale, not from Polylang's current language, so it
	 * follows with_locale() when the editor or a script renders another language.
	 *
	 * @param string $source Polish text from the settings.
	 * @return string|null
	 */
	private static function phone_text_in_language( string $source ): ?string {
		if ( '' === $source ) {
			return null;
		}

		if ( ! function_exists( 'PLL' ) || ! function_exists( 'pll_translate_string' ) || ! isset( PLL()->model ) ) {
			return $source;
		}

		$language = PLL()->model->get_language( determine_locale() );
		$default  = function_exists( 'pll_default_language' ) ? pll_default_language( 'slug' ) : 'pl';

		if ( ! $language || $language->slug === $default ) {
			return $source;
		}

		$translated = (string) pll_translate_string( $source, $language->slug );

		return ( '' !== trim( $translated ) && $translated !== $source ) ? $translated : null;
	}

	/**
	 * Run a callback with the theme's catalogue loaded for another locale.
	 *
	 * `switch_to_locale()` cannot be used here, and the reason is worth writing down
	 * because the failure is silent. It refuses any locale absent from
	 * `get_available_languages()`, which reports only locales with core translation files
	 * in `wp-content/languages` — this installation has those for Polish alone. Worse,
	 * `WP_Locale_Switcher` captures that list in its constructor, at
	 * `wp-settings.php:729`, before the theme's `functions.php` is even read, so a theme
	 * cannot extend it with a filter. The call simply returns `false`, `__()` hands back
	 * the source string, and every line comes out in Polish while the catalogue sitting
	 * next to it holds the right words.
	 *
	 * Ordinary front-end requests never come here: Polylang sets the locale for the whole
	 * request. This is for the two places that render one language while another is
	 * current — values prepared for the block editor, and the script that writes
	 * per-language fallback text into the pages.
	 *
	 * @param string   $locale   Target locale, e.g. `uk`. Empty or current means no switch.
	 * @param callable $callback Runs with that locale in force.
	 * @return mixed Whatever the callback returns.
	 */
	public static function with_locale( string $locale, callable $callback ) {
		if ( '' === $locale || determine_locale() === $locale ) {
			return $callback();
		}

		$force = static function () use ( $locale ) {
			return $locale;
		};

		add_filter( 'locale', $force, 99 );
		add_filter( 'determine_locale', $force, 99 );

		/*
		 * `load_theme_textdomain()` resolves the file through `determine_locale()`, which
		 * the filters above have just redirected.
		 *
		 * The `true` is load-bearing. Without it WordPress records the domain in
		 * `$l10n_unloaded` and refuses to load it again, so only the FIRST switch in a
		 * process takes effect — a script walking four languages would write Polish into
		 * three of them and report success. Measured, not guessed.
		 */
		unload_textdomain( 'kzmielec', true );
		load_theme_textdomain( 'kzmielec', get_template_directory() . '/languages' );

		try {
			return $callback();
		} finally {
			remove_filter( 'locale', $force, 99 );
			remove_filter( 'determine_locale', $force, 99 );

			// Reloadable here too, or the NEXT call to this method finds the domain on
			// the unloaded list and quietly serves Polish.
			unload_textdomain( 'kzmielec', true );
			load_theme_textdomain( 'kzmielec', get_template_directory() . '/languages' );
		}
	}

	/**
	 * Load the editor-side registration of the same source, with resolved values.
	 *
	 * A source registered only in PHP renders correctly on the front end but leaves the
	 * editor showing the paragraph's fallback text, so an administrator would see stale
	 * data and reasonably try to correct it by hand.
	 *
	 * @return void
	 */
	public function enqueue_editor_script(): void {
		$relative = 'assets/js/contact-bindings.js';
		$path     = get_theme_file_path( $relative );

		if ( ! file_exists( $path ) ) {
			return;
		}

		$handle = 'kzmielec-contact-bindings';

		wp_enqueue_script(
			$handle,
			get_theme_file_uri( $relative ),
			array( 'wp-blocks', 'wp-i18n' ),
			(string) filemtime( $path ),
			true
		);

		wp_add_inline_script(
			$handle,
			'window.kzmielecContact = ' . wp_json_encode( $this->editor_values() ) . ';',
			'before'
		);
	}

	/**
	 * The five lines, in the language of the post open in the editor.
	 *
	 * The admin runs in the administrator's own language, which has nothing to do with
	 * the language of the page being edited, so the language is resolved here rather than
	 * in the browser. With Polylang inactive there is one language and it is Polish —
	 * which is also what the gettext keys themselves say, so nothing has to load for the
	 * Polish version to come out right.
	 *
	 * @return array<string, string>
	 */
	private function editor_values(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading which post is open, not acting on submitted data.
		$post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;
		$locale  = '';

		if ( $post_id > 0 && function_exists( 'pll_get_post_language' ) ) {
			// Documented as a string, but the same call answers with `false` for a post
			// no language has been assigned to, and with an array for other fields.
			$found = pll_get_post_language( $post_id, 'locale' );

			if ( is_string( $found ) ) {
				$locale = $found;
			}
		}

		return self::with_locale(
			$locale,
			static function (): array {
				$values = array();

				foreach ( self::KEYS as $key ) {
					$values[ $key ] = (string) self::line( $key );
				}

				return $values;
			}
		);
	}
}
