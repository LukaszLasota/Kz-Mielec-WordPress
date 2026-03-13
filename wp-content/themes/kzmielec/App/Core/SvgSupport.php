<?php
/**
 * SVG Support class
 *
 * Enables SVG file uploads in WordPress Media Library.
 * WARNING: Only enable this if you trust all users who can upload media.
 * SVG files can contain malicious JavaScript code.
 *
 * @package Kzmielec
 */

namespace Kzmielec\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kzmielec\Interfaces\ActionHookInterface;
use Kzmielec\Interfaces\FilterHookInterface;

/**
 * Class SvgSupport
 *
 * Adds support for SVG file uploads with basic security checks.
 */
class SvgSupport implements ActionHookInterface, FilterHookInterface {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
		$this->register_add_filter();
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'sanitize_svg_upload' ) );
	}

	/**
	 * Register WordPress action hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'admin_head', array( $this, 'fix_svg_thumbnail_display' ) );
	}

	/**
	 * Register WordPress filter hooks.
	 *
	 * @return void
	 */
	public function register_add_filter(): void {
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'check_svg_filetype' ), 10, 4 );
		add_filter( 'upload_mimes', array( $this, 'add_svg_mime_types' ) );
	}

	/**
	 * Add SVG and SVGZ to allowed MIME types.
	 *
	 * Only administrators can upload SVG files for security.
	 *
	 * @param array<string, string> $mimes Allowed MIME types.
	 * @return array<string, string> Modified MIME types.
	 */
	public function add_svg_mime_types( array $mimes ): array {
		// Only allow SVG uploads for administrators.
		if ( ! current_user_can( 'manage_options' ) ) {
			return $mimes;
		}

		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';

		return $mimes;
	}

	/**
	 * Check and allow SVG file type during upload.
	 *
	 * @param array<string, mixed>|null  $data     File data array.
	 * @param string                     $file     Full path to the file.
	 * @param string                     $filename The name of the file.
	 * @param array<string, string>|null $mimes    Allowed MIME types.
	 * @return array<string, mixed> Modified file data.
	 */
	public function check_svg_filetype( ?array $data, string $file, string $filename, ?array $mimes ): array {
		// Only allow SVG uploads for administrators.
		if ( ! current_user_can( 'manage_options' ) ) {
			return $data ?? array();
		}

		$filetype = wp_check_filetype( $filename, $mimes );

		return array(
			'ext'             => $filetype['ext'],
			'type'            => $filetype['type'],
			'proper_filename' => isset( $data['proper_filename'] ) ? $data['proper_filename'] : false,
		);
	}

	/**
	 * Sanitize SVG file uploads using DOM-based parsing.
	 *
	 * Uses enshrined/svg-sanitize library for proper SVG sanitization
	 * that handles script tags, event handlers, javascript: URIs,
	 * foreignObject, xlink:href, data URIs, and entity encoding bypasses.
	 *
	 * @param array<string, mixed> $file File data array from upload handler.
	 * @return array<string, mixed> Sanitized file data.
	 */
	public function sanitize_svg_upload( array $file ): array {
		if ( 'image/svg+xml' !== $file['type'] && ! str_ends_with( $file['name'], '.svg' ) ) {
			return $file;
		}

		$content = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $content ) {
			return $file;
		}

		$sanitizer = new \enshrined\svgSanitize\Sanitizer();
		$clean_svg = $sanitizer->sanitize( $content );

		if ( false === $clean_svg ) {
			$file['error'] = __( 'This SVG file could not be sanitized and was rejected.', 'kzmielec' );
			return $file;
		}

		file_put_contents( $file['tmp_name'], $clean_svg ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		return $file;
	}

	/**
	 * Fix SVG thumbnail display in Media Library.
	 *
	 * Adds CSS to properly display SVG thumbnails.
	 *
	 * @return void
	 */
	public function fix_svg_thumbnail_display(): void {
		?>
		<style type="text/css">
			td.media-icon img[src$=".svg"],
			img[src$=".svg"].attachment-post-thumbnail {
				width: 100% !important;
				height: auto !important;
			}
		</style>
		<?php
	}
}
