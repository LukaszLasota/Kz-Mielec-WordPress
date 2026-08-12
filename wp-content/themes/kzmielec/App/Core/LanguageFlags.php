<?php
/**
 * Inline SVG flags for the language switcher.
 *
 * @package Kzmielec
 */

declare(strict_types=1);

namespace Kzmielec\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Four flags, drawn inline rather than fetched.
 *
 * Polylang ships PNG flags and exposes their URLs, but they are unusable here for
 * two measured reasons: it returns them over `http://` on a site served over
 * HTTPS, which the browser blocks as mixed content, and they are 16×11 raster
 * images that blur on any high-density screen.
 *
 * Inline SVG costs no request, cannot be blocked, and stays sharp at any size.
 * The shapes are deliberately simplified — at the strip's 12px scale the Spanish
 * coat of arms and the exact Union Jack proportions are invisible anyway, and
 * simpler paths keep the document small.
 *
 * A flag is decoration, never the label. It carries `aria-hidden` and sits beside
 * the language code, because a flag denotes a country and not a language: English
 * is not only Britain and Spanish is not only Spain. The code is what a reader
 * relies on and what the accessible name begins with.
 */
class LanguageFlags {

	/**
	 * Viewport shared by every flag, 3:2 like most national flags.
	 */
	private const VIEWBOX = '0 0 30 20';

	/**
	 * Return the inline SVG for a language slug, or an empty string.
	 *
	 * @param string $slug Polylang language slug (pl, en, uk, es).
	 * @return string
	 */
	public static function get( string $slug ): string {
		$shapes = self::shapes( $slug );

		if ( '' === $shapes ) {
			return '';
		}

		return sprintf(
			'<svg class="a11y-bar__lang-flag" viewBox="%s" width="18" height="12" aria-hidden="true" focusable="false">%s</svg>',
			esc_attr( self::VIEWBOX ),
			$shapes
		);
	}

	/**
	 * The shapes for one flag.
	 *
	 * @param string $slug Language slug.
	 * @return string
	 */
	private static function shapes( string $slug ): string {
		switch ( $slug ) {
			case 'pl':
				// White over red.
				return '<rect width="30" height="10" fill="#fff"/><rect y="10" width="30" height="10" fill="#dc143c"/>';

			case 'uk':
				// Blue over yellow.
				return '<rect width="30" height="10" fill="#005bbb"/><rect y="10" width="30" height="10" fill="#ffd500"/>';

			case 'es':
				// Red, yellow, red — the middle band is twice the others.
				return '<rect width="30" height="5" fill="#aa151b"/><rect y="5" width="30" height="10" fill="#f1bf00"/><rect y="15" width="30" height="5" fill="#aa151b"/>';

			case 'en':
				/*
				 * Union Jack, simplified: blue field, white then red diagonals,
				 * white then red upright cross. Drawn with plain lines rather than
				 * the true counterchanged saltire — at 18px the difference is not
				 * visible and the path stays a fraction of the size.
				 */
				return '<rect width="30" height="20" fill="#012169"/>'
					. '<path d="M0 0 30 20M30 0 0 20" stroke="#fff" stroke-width="4"/>'
					. '<path d="M0 0 30 20M30 0 0 20" stroke="#c8102e" stroke-width="2"/>'
					. '<path d="M15 0V20M0 10H30" stroke="#fff" stroke-width="6"/>'
					. '<path d="M15 0V20M0 10H30" stroke="#c8102e" stroke-width="3.5"/>';

			default:
				return '';
		}
	}
}
