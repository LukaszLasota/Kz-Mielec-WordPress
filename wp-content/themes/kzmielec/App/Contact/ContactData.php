<?php
/**
 * The one place the congregation's contact details are read from.
 *
 * @package Kzmielec
 */

declare(strict_types=1);

namespace Kzmielec\Contact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads the single option holding the contact data and derives the machine formats.
 *
 * Everything that shows an address, a phone number, the tax number, the e-mail or the
 * bank account reads it from here: the five bound paragraphs on the four language
 * versions of the front page, the JSON-LD graph and the meetings archive description.
 * Nothing else calls `get_option()` for this data, so the shape of the option has
 * exactly one consumer and can change in one place.
 *
 * Only data that is IDENTICAL in every language lives here. The words around the data,
 * and personal names — which Ukrainian legitimately transliterates, and does so in two
 * places on this site — belong to the translation catalogue instead.
 *
 * The reason this class exists at all: before it, the five values lived in four
 * independent copies of the front page plus a fifth hardcoded in the theme, and they had
 * already drifted. Every visible copy of the e-mail address read `zbor@kzmielec.ddev.site`
 * — a dead address left behind by the domain search-replace that follows a database copy
 * from production — while only the machine-readable copy was right.
 */
class ContactData {

	/**
	 * Option holding every field.
	 */
	public const OPTION = 'kzmielec_contact';

	/**
	 * Country calling code prefixed to the phone number for machine readers.
	 *
	 * Not a field: a field would imply the congregation might move to another country.
	 */
	private const CALLING_CODE = '+48';

	/**
	 * Field defaults — the real values.
	 *
	 * A wiped option, a fresh install or a key added in a later version therefore
	 * renders correct contact details rather than blanks. Same reasoning as the fallback
	 * text kept inside the bound paragraphs: degrade to correct, never to empty.
	 *
	 * @var array<string, string>
	 */
	public const DEFAULTS = array(
		'street'    => 'Przemysłowa 2',
		'postcode'  => '39-300',
		'city'      => 'Mielec',
		'phone'     => '669 189 992',
		'nip'       => '817-18-40-461',
		'email'     => 'zbor@kzmielec.pl',
		'iban'      => '63 8642 1168 2016 6812 9206 0001',
		// The map's coordinates belong here for the same reason as the street: they are
		// the same in every language and were stored four times over, once per version of
		// the front page. The map block reads them from this option directly rather than
		// through this class, so the plugin stays independent of the theme.
		'latitude'  => '50.299071',
		'longitude' => '21.4483254',
	);

	/**
	 * Every field, stored values on top of the defaults.
	 *
	 * A field stored empty or as whitespace counts as absent, because an administrator
	 * clearing a box by accident should not blank the address on four pages at once.
	 *
	 * @return array<string, string>
	 */
	public static function all(): array {
		$stored = get_option( self::OPTION, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$data = self::DEFAULTS;

		foreach ( array_keys( self::DEFAULTS ) as $key ) {
			$value = isset( $stored[ $key ] ) ? trim( (string) $stored[ $key ] ) : '';

			if ( '' !== $value ) {
				$data[ $key ] = $value;
			}
		}

		return $data;
	}

	/**
	 * One field, or an empty string for a key that does not exist.
	 *
	 * @param string $key Field name.
	 * @return string
	 */
	public static function get( string $key ): string {
		$data = self::all();

		return $data[ $key ] ?? '';
	}

	/**
	 * Phone number in E.164, for `tel:` links and for structured data.
	 *
	 * Digits only, so the spacing that makes the number readable on screen cannot leak
	 * into a machine-readable field.
	 *
	 * @return string
	 */
	public static function phone_e164(): string {
		$digits = preg_replace( '/\D+/', '', self::get( 'phone' ) );
		$digits = is_string( $digits ) ? $digits : '';

		return '' === $digits ? '' : self::CALLING_CODE . $digits;
	}

	/**
	 * Value for a `tel:` href, or an empty string when there is no number.
	 *
	 * @return string
	 */
	public static function phone_href(): string {
		$e164 = self::phone_e164();

		return '' === $e164 ? '' : 'tel:' . $e164;
	}

	/**
	 * Value for a `mailto:` href, or an empty string when there is no address.
	 *
	 * @return string
	 */
	public static function email_href(): string {
		$email = self::get( 'email' );

		return '' === $email ? '' : 'mailto:' . $email;
	}
}
