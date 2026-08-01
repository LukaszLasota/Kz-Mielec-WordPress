<?php
/**
 * Modern image formats
 *
 * Serves AVIF or WebP when a converted file sits next to the original, and the
 * original otherwise. Nothing here converts anything: the markup adapts to
 * whatever is on disk, so a missing sibling is simply not offered.
 *
 * @package Kzmielec\Core
 */

namespace Kzmielec\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\FilterHookInterface;

/**
 * Class ModernImages
 *
 * WordPress can generate sub-sizes but it will not serve a different format for
 * the same image, and `srcset` cannot express one — a browser picks a *width*
 * from a srcset, not a codec. `<picture>` is the mechanism that can: sources are
 * tried in order and the first supported type wins, so AVIF goes before WebP and
 * the untouched `<img>` stays as the last resort for anything older.
 *
 * The alternative — rewriting extensions at the server and trusting the `Accept`
 * header — moves the decision out of the repository and breaks the moment a CDN
 * caches one variant under both names. This keeps it in the markup, where it is
 * inspectable.
 *
 * Both filters are needed: `wp_content_img_tag` covers images an editor placed in
 * content, `wp_get_attachment_image` covers everything a template or block
 * renders, including the tiles in custom-block-package. Filtering the second is
 * also how the theme reaches plugin markup without the plugin knowing about it.
 */
class ModernImages implements FilterHookInterface {

	/**
	 * Formats to offer, best first — a browser takes the first type it supports.
	 *
	 * @var array<string, string>
	 */
	private const FORMATS = array(
		'avif' => 'image/avif',
		'webp' => 'image/webp',
	);

	/**
	 * Results of `file_exists()`, keyed by absolute path.
	 *
	 * A page with sixteen tiles asks about the same handful of files repeatedly
	 * once every srcset candidate is considered; the disk should be asked once.
	 *
	 * @var array<string, bool>
	 */
	private array $exists = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_filter();
	}

	/**
	 * Register the filters.
	 *
	 * @return void
	 */
	public function register_add_filter(): void {
		add_filter( 'wp_content_img_tag', array( $this, 'wrap_content_image' ) );
		add_filter( 'wp_get_attachment_image', array( $this, 'wrap_attachment_image' ) );
		// A block that builds its own <picture> for art direction — the hero is one
		// — is skipped by the two filters above, so its sources are enhanced in place.
		add_filter( 'render_block', array( $this, 'enhance_block_picture' ) );
	}

	/**
	 * Wrap an image an editor placed in post content.
	 *
	 * @param string $html Image tag.
	 * @return string
	 */
	public function wrap_content_image( string $html ): string {
		return $this->wrap( $html );
	}

	/**
	 * Wrap an image a template or block rendered.
	 *
	 * @param string $html Image tag.
	 * @return string
	 */
	public function wrap_attachment_image( string $html ): string {
		return $this->wrap( $html );
	}

	/**
	 * Add format sources inside a `<picture>` a block built itself.
	 *
	 * The hero block emits `<source media=…>` per breakpoint for art direction, so
	 * wrapping it again would be wrong: the format alternatives have to sit beside
	 * each existing source, keeping its `media` and `sizes`, and before it, because
	 * a browser takes the first source that both matches and is supported.
	 *
	 * @param string $html Rendered block markup.
	 * @return string
	 */
	public function enhance_block_picture( string $html ): string {
		if ( is_admin() || false === strpos( $html, '<picture' ) || false !== strpos( $html, 'type="image/' ) ) {
			return $html;
		}

		// Only inside the element: a block can also hold plain <img> tags — the hero
		// has overlay logos — and a <source> outside a <picture> is invalid markup
		// that no browser would use.
		$result = preg_replace_callback(
			'#<picture\b[^>]*>.*?</picture>#is',
			array( $this, 'enhance_one_picture' ),
			$html
		);

		return is_string( $result ) ? $result : $html;
	}

	/**
	 * Prepend format sources to every source and img inside one `<picture>`.
	 *
	 * @param array<int, string> $matches Regex match, element 0 is the whole element.
	 * @return string
	 */
	private function enhance_one_picture( array $matches ): string {
		$result = preg_replace_callback(
			'/<(source|img)\s[^>]*>/i',
			array( $this, 'prepend_format_sources' ),
			$matches[0]
		);

		return is_string( $result ) ? $result : $matches[0];
	}

	/**
	 * Build format sources for one `<source>` or `<img>` and return them before it.
	 *
	 * @param array<int, string> $matches Regex match, element 0 is the whole tag.
	 * @return string
	 */
	private function prepend_format_sources( array $matches ): string {
		$tag    = $matches[0];
		$srcset = $this->attribute( $tag, 'srcset' );
		$src    = $this->attribute( $tag, 'src' );
		$origin = '' !== $srcset ? $srcset : $src;

		if ( '' === $origin || $this->is_svg( $origin ) ) {
			return $tag;
		}

		$media   = $this->attribute( $tag, 'media' );
		$sizes   = $this->attribute( $tag, 'sizes' );
		$sources = '';

		foreach ( self::FORMATS as $extension => $mime ) {
			$candidate = '' !== $srcset
				? $this->convert_srcset( $srcset, $extension )
				: $this->convert_url( $src, $extension );

			if ( '' === $candidate ) {
				continue;
			}

			$sources .= sprintf(
				'<source type="%s" srcset="%s"%s%s>',
				esc_attr( $mime ),
				esc_attr( $candidate ),
				'' !== $media ? ' media="' . esc_attr( $media ) . '"' : '',
				'' !== $sizes ? ' sizes="' . esc_attr( $sizes ) . '"' : ''
			);
		}

		return $sources . $tag;
	}

	/**
	 * Add `<source>` elements for every format that exists on disk.
	 *
	 * @param string $html Image tag.
	 * @return string
	 */
	private function wrap( string $html ): string {
		if ( '' === $html || false !== strpos( $html, '<picture' ) || is_admin() ) {
			return $html;
		}

		$src = $this->attribute( $html, 'src' );

		// An SVG has nothing to convert to, and a remote file is not ours to check.
		if ( '' === $src || $this->is_svg( $src ) || '' === $this->path_for( $src ) ) {
			return $html;
		}

		$srcset  = $this->attribute( $html, 'srcset' );
		$sizes   = $this->attribute( $html, 'sizes' );
		$sources = '';

		foreach ( self::FORMATS as $extension => $mime ) {
			$candidate = '' !== $srcset
				? $this->convert_srcset( $srcset, $extension )
				: $this->convert_url( $src, $extension );

			if ( '' === $candidate ) {
				continue;
			}

			$sources .= sprintf(
				'<source type="%s" srcset="%s"%s>',
				esc_attr( $mime ),
				esc_attr( $candidate ),
				'' !== $sizes ? ' sizes="' . esc_attr( $sizes ) . '"' : ''
			);
		}

		if ( '' === $sources ) {
			return $html;
		}

		return '<picture>' . $sources . $html . '</picture>';
	}

	/**
	 * Rewrite every candidate in a srcset, keeping only those that exist.
	 *
	 * A partial srcset is worse than none: the browser would pick a width whose
	 * file is missing and show nothing, so this returns empty unless every
	 * candidate converted.
	 *
	 * @param string $srcset    Original srcset attribute.
	 * @param string $extension Target extension.
	 * @return string Converted srcset, or an empty string.
	 */
	private function convert_srcset( string $srcset, string $extension ): string {
		$converted = array();

		foreach ( explode( ',', $srcset ) as $candidate ) {
			$candidate = trim( $candidate );

			if ( '' === $candidate ) {
				continue;
			}

			$parts = preg_split( '/\s+/', $candidate );

			if ( ! is_array( $parts ) || '' === $parts[0] ) {
				return '';
			}

			$url = $this->convert_url( $parts[0], $extension );

			if ( '' === $url ) {
				return '';
			}

			$descriptor  = isset( $parts[1] ) ? ' ' . $parts[1] : '';
			$converted[] = $url . $descriptor;
		}

		return array() === $converted ? '' : implode( ', ', $converted );
	}

	/**
	 * Swap the extension on a URL, if the resulting file is on disk.
	 *
	 * @param string $url       Original URL.
	 * @param string $extension Target extension.
	 * @return string Converted URL, or an empty string when there is no such file.
	 */
	private function convert_url( string $url, string $extension ): string {
		$path = $this->path_for( $url );

		if ( '' === $path ) {
			return '';
		}

		$converted_path = preg_replace( '/\.(jpe?g|png)$/i', '.' . $extension, $path );

		if ( ! is_string( $converted_path ) || $converted_path === $path ) {
			return '';
		}

		if ( ! $this->file_exists( $converted_path ) ) {
			return '';
		}

		$converted_url = preg_replace( '/\.(jpe?g|png)$/i', '.' . $extension, $url );

		return is_string( $converted_url ) ? $converted_url : '';
	}

	/**
	 * Map an uploads URL to its path on disk.
	 *
	 * Anything outside the uploads directory — a plugin asset, another domain —
	 * returns empty, because this cannot know what is next to it.
	 *
	 * @param string $url Image URL.
	 * @return string Absolute path, or an empty string.
	 */
	private function path_for( string $url ): string {
		$uploads = wp_get_upload_dir();
		$baseurl = (string) ( $uploads['baseurl'] ?? '' );
		$basedir = (string) ( $uploads['basedir'] ?? '' );

		if ( '' === $baseurl || '' === $basedir ) {
			return '';
		}

		// Protocol-relative and http/https forms of the same path both occur.
		$normalised = preg_replace( '#^https?:#i', '', $url );
		$base       = preg_replace( '#^https?:#i', '', $baseurl );

		if ( ! is_string( $normalised ) || ! is_string( $base ) || 0 !== strpos( $normalised, $base ) ) {
			return '';
		}

		return $basedir . substr( $normalised, strlen( $base ) );
	}

	/**
	 * Cached `file_exists()`.
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	private function file_exists( string $path ): bool {
		if ( ! isset( $this->exists[ $path ] ) ) {
			$this->exists[ $path ] = file_exists( $path );
		}

		return $this->exists[ $path ];
	}

	/**
	 * Whether a URL points at an SVG.
	 *
	 * @param string $url Image URL.
	 * @return bool
	 */
	private function is_svg( string $url ): bool {
		return (bool) preg_match( '/\.svgz?(\?|#|$)/i', $url );
	}

	/**
	 * Read one attribute out of an image tag.
	 *
	 * @param string $html Image tag.
	 * @param string $name Attribute name.
	 * @return string Value, or an empty string.
	 */
	private function attribute( string $html, string $name ): string {
		if ( 1 !== preg_match( '/\s' . preg_quote( $name, '/' ) . '="([^"]*)"/i', $html, $matches ) ) {
			return '';
		}

		return $matches[1];
	}
}
