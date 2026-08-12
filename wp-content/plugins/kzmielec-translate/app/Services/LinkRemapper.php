<?php
/**
 * Points internal links in translated content at the right language.
 *
 * @package KzmielecTranslate
 */

declare(strict_types=1);

namespace KzmielecTranslate\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrites internal hrefs so a translated page links to translated pages.
 *
 * URLs are correctly never translated — a slug is not prose — but that leaves
 * them pointing where they pointed in Polish. Measured on the Ukrainian law page:
 * nine "Читати далі" buttons whose href was `/w-sprawie-wieczerzy-panskiej/`, so
 * a Ukrainian reader following any of them landed on Polish. The page looked
 * perfectly translated; only the exits led out of the language.
 *
 * What is deliberately left alone:
 *
 * - `/wp-content/...` and other file paths. The PDFs on the law page are the
 *   actual Polish statutes and church regulations; there is no Ukrainian version
 *   of a Polish act of parliament, and pretending otherwise would be worse than
 *   linking the original.
 * - Anything pointing off-site.
 * - Fragment-only links (`#one`), which stay within the current page.
 * - A path with no matching post, and a post with no translation — both keep the
 *   original target rather than becoming a dead link.
 */
class LinkRemapper {

	/**
	 * Path prefixes that are files or endpoints, never translatable content.
	 *
	 * @var array<int, string>
	 */
	private const SKIP_PREFIXES = array(
		'/wp-content',
		'/wp-json',
		'/wp-includes',
		'/wp-admin',
		'/wp-login',
		'/feed',
		'/xmlrpc',
	);

	/**
	 * Rewrite every internal link in a document for one language.
	 *
	 * @param string $content Post content, already translated.
	 * @param string $lang    Target language slug.
	 * @return array{content: string, remapped: int, skipped: int}
	 */
	public static function remap( string $content, string $lang ): array {
		if ( '' === trim( $content ) || ! function_exists( 'pll_get_post' ) ) {
			return array(
				'content'  => $content,
				'remapped' => 0,
				'skipped'  => 0,
			);
		}

		$home      = untrailingslashit( (string) get_option( 'home' ) );
		$remapped  = 0;
		$skipped   = 0;

		$content = (string) preg_replace_callback(
			'#href="([^"]+)"#',
			static function ( array $m ) use ( $home, $lang, &$remapped, &$skipped ): string {
				$url  = $m[1];
				$path = $url;

				// Absolute but ours: reduce to a path. Anything else is off-site.
				if ( preg_match( '#^https?://#i', $url ) ) {
					if ( 0 !== strpos( $url, $home ) ) {
						return $m[0];
					}

					$path = (string) substr( $url, strlen( $home ) );
				}

				if ( '' === $path || 0 === strpos( $path, '#' ) || 0 !== strpos( $path, '/' ) ) {
					return $m[0];
				}

				foreach ( self::SKIP_PREFIXES as $prefix ) {
					if ( 0 === strpos( $path, $prefix ) ) {
						return $m[0];
					}
				}

				// Keep the fragment and query, they are not part of the lookup.
				$fragment = '';
				$query    = '';

				if ( false !== strpos( $path, '#' ) ) {
					list( $path, $fragment ) = explode( '#', $path, 2 );
					$fragment                = '#' . $fragment;
				}

				if ( false !== strpos( $path, '?' ) ) {
					list( $path, $query ) = explode( '?', $path, 2 );
					$query                = '?' . $query;
				}

				$source_id = self::post_for_path( $path );

				if ( $source_id <= 0 ) {
					++$skipped;

					return $m[0];
				}

				$target_id = (int) pll_get_post( $source_id, $lang );

				if ( $target_id <= 0 || $target_id === $source_id ) {
					++$skipped;

					return $m[0];
				}

				$nowy = get_permalink( $target_id );

				if ( ! is_string( $nowy ) || '' === $nowy ) {
					++$skipped;

					return $m[0];
				}

				++$remapped;

				return 'href="' . esc_url( $nowy ) . $query . $fragment . '"';
			},
			$content
		);

		return array(
			'content'  => $content,
			'remapped' => $remapped,
			'skipped'  => $skipped,
		);
	}

	/**
	 * Resolve a site path to a post id.
	 *
	 * Pages first, then the translated post types, because a bare
	 * `/some-slug/` on this site is a page far more often than anything else.
	 *
	 * @param string $path Path beginning with a slash.
	 * @return int
	 */
	private static function post_for_path( string $path ): int {
		$slug = trim( $path, '/' );

		if ( '' === $slug ) {
			return 0;
		}

		$page = get_page_by_path( $slug, OBJECT, 'page' );

		if ( $page instanceof \WP_Post ) {
			return (int) $page->ID;
		}

		// Nested or non-page: let WordPress resolve the full URL.
		$id = url_to_postid( untrailingslashit( (string) get_option( 'home' ) ) . '/' . $slug . '/' );

		return (int) $id;
	}
}
