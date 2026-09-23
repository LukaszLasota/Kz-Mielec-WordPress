<?php
/**
 * Map Location
 *
 * Decides where a map block points: the contact settings or its own pair.
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MapLocation
 *
 * One place for the rule, because two places need it: render.php draws the map
 * from it and the editor is told the same answer, so the preview shows what the
 * page will.
 *
 * The rule, by `locationSource`:
 * - "contact": the coordinates from the `kzmielec_contact` option;
 * - "custom": the block's own latitude/longitude, for a second map elsewhere;
 * - unset (every block saved before the attribute existed): contact while the
 *   block still holds the placeholder pair, custom otherwise. `block.json` merges
 *   the placeholder into every instance, so "nothing chosen" and "the placeholder
 *   chosen" look the same, and both meant "follow the settings" until now.
 *
 * The option is read directly rather than through the theme's `ContactData`, so
 * the plugin keeps working with any theme. Without a usable option the block's
 * own pair is used whatever the source says.
 */
class MapLocation {

	/**
	 * Placeholder pair shipped in block.json: the congregation's location.
	 *
	 * It has to be a true location, not a neutral one. It used to be a spot near
	 * Rzeszow, and before the editor was given the settings every map showed that
	 * town while being edited and the right one once published. Legacy blocks are
	 * also recognised by it (see uses_contact()), so changing it needs a migration.
	 */
	public const PLACEHOLDER_LAT = 50.299071;

	/**
	 * Placeholder longitude, see PLACEHOLDER_LAT.
	 */
	public const PLACEHOLDER_LNG = 21.4483254;

	/**
	 * Option the contact settings screen writes.
	 */
	public const OPTION = 'kzmielec_contact';

	/**
	 * Coordinates from the contact settings, if both are set and numeric.
	 *
	 * @return array{lat: float, lng: float}|null
	 */
	public static function from_contact(): ?array {
		$contact = get_option( self::OPTION, array() );
		if ( ! is_array( $contact ) ) {
			return null;
		}

		$lat = isset( $contact['latitude'] ) ? trim( (string) $contact['latitude'] ) : '';
		$lng = isset( $contact['longitude'] ) ? trim( (string) $contact['longitude'] ) : '';

		if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
			return null;
		}

		return array(
			'lat' => (float) $lat,
			'lng' => (float) $lng,
		);
	}

	/**
	 * Whether the block follows the contact settings.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return bool
	 */
	public static function uses_contact( array $attributes ): bool {
		$source = isset( $attributes['locationSource'] ) ? (string) $attributes['locationSource'] : '';
		if ( 'contact' === $source || 'custom' === $source ) {
			return 'contact' === $source;
		}

		$lat = isset( $attributes['latitude'] ) ? (float) $attributes['latitude'] : self::PLACEHOLDER_LAT;
		$lng = isset( $attributes['longitude'] ) ? (float) $attributes['longitude'] : self::PLACEHOLDER_LNG;

		return abs( $lat - self::PLACEHOLDER_LAT ) < 0.000001 && abs( $lng - self::PLACEHOLDER_LNG ) < 0.000001;
	}

	/**
	 * Coordinates the block is drawn at.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return array{lat: float, lng: float}
	 */
	public static function resolve( array $attributes ): array {
		if ( self::uses_contact( $attributes ) ) {
			$contact = self::from_contact();
			if ( null !== $contact ) {
				return $contact;
			}
		}

		return array(
			'lat' => isset( $attributes['latitude'] ) ? (float) $attributes['latitude'] : self::PLACEHOLDER_LAT,
			'lng' => isset( $attributes['longitude'] ) ? (float) $attributes['longitude'] : self::PLACEHOLDER_LNG,
		);
	}
}
