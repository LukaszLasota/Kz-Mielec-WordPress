<?php
/**
 * Facebook Image Store
 *
 * Keeps resized WebP copies of the feed's images on this server.
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FacebookImageStore
 *
 * The Graph API hands out `full_picture` at up to 720px as JPEG, from fbcdn
 * addresses that are signed and expire. Serving that straight cost ~150KB per
 * post on a card shown at ~300px, and a page cached for days kept a link that
 * had already died - the same failure the Instagram feed had.
 *
 * So on every refresh each image is fetched once, cropped to 16:9, scaled down
 * to the width set on the settings screen and stored as WebP under uploads. The
 * crop matches `.facebook-feed__image` in style.scss (`aspect-ratio: 16 / 9`,
 * `object-fit: cover`, centred): the card never shows more than that, so the rest
 * of a tall photo was bytes nobody saw. Change one and change the other.
 *
 * A file that already exists is reused, which makes a routine refresh download
 * only new posts. A download that fails leaves the Facebook address in place, so
 * the worst case is the old behaviour, never a missing image.
 */
class FacebookImageStore {

	/**
	 * Option key for the target image width. 0 means "use Facebook's original".
	 */
	public const OPTION_WIDTH = 'cbp_fb_image_width';

	/**
	 * Default target width: twice the ~300px card on a phone.
	 */
	public const DEFAULT_WIDTH = 600;

	/**
	 * Widths offered on the settings screen.
	 *
	 * @var array<int, int>
	 */
	public const WIDTHS = array( 480, 600, 800, 0 );

	/**
	 * Avatar edge in pixels: the 3rem avatar at 3.5x covers every phone density.
	 */
	private const AVATAR_SIZE = 168;

	/**
	 * Directory under uploads.
	 */
	private const DIR = 'cbp-facebook-feed';

	/**
	 * WebP quality.
	 */
	private const QUALITY = 80;

	/**
	 * Seconds one refresh may spend downloading.
	 *
	 * The first refresh after enabling this has up to 50 images to fetch, and a
	 * cold cache can make that refresh happen inside a visitor's request. Past
	 * this budget the remaining posts keep their Facebook address and the next
	 * refresh carries on where this one stopped. Posts come newest first, so the
	 * ones on screen are converted first. Measured locally: ~0.45s per image.
	 */
	private const TIME_BUDGET = 5;

	/**
	 * Moment the download budget started, as microtime(true).
	 *
	 * @var float
	 */
	private float $started;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->started = microtime( true );
	}

	/**
	 * Configured target width.
	 *
	 * @return int 0 for "original".
	 */
	public static function get_width(): int {
		$width = (int) get_option( self::OPTION_WIDTH, self::DEFAULT_WIDTH );

		return in_array( $width, self::WIDTHS, true ) ? $width : self::DEFAULT_WIDTH;
	}

	/**
	 * Replace each post's image with a local copy where one can be made.
	 *
	 * The Facebook address is kept under `image_remote`, so a later refresh can
	 * rebuild the copy at a different width without calling the API for it.
	 *
	 * @param array<int, array<string, mixed>> $posts Parsed posts.
	 * @return array<int, array<string, mixed>>
	 */
	public function localize_posts( array $posts ): array {
		$width = self::get_width();
		$keep  = array();

		foreach ( $posts as $index => $post ) {
			$remote = isset( $post['image_remote'] ) ? (string) $post['image_remote'] : ( isset( $post['image'] ) ? (string) $post['image'] : '' );
			if ( '' === $remote || 0 === $width ) {
				continue;
			}

			$height = (int) round( $width * 9 / 16 );
			$name   = 'post-' . sanitize_file_name( isset( $post['id'] ) ? (string) $post['id'] : md5( $remote ) ) . '-' . $width . 'x' . $height;
			$local  = $this->store( $remote, $name, $width, $height );
			if ( null === $local ) {
				continue;
			}

			$keep[]                          = $local['file'];
			$posts[ $index ]['image']        = $local['url'];
			$posts[ $index ]['image_remote'] = $remote;
			$posts[ $index ]['image_width']  = $local['width'];
			$posts[ $index ]['image_height'] = $local['height'];
		}

		$this->prune( $keep, 'post-' );

		return $posts;
	}

	/**
	 * Local copy of the page's avatar, or the given address if none can be made.
	 *
	 * @param string $remote Avatar address from the Graph API.
	 * @return string
	 */
	public function localize_avatar( string $remote ): string {
		if ( '' === $remote ) {
			return $remote;
		}

		// Named after the picture's path, not its signed query string, so a new
		// profile picture gets a new file and an unchanged one is not fetched again.
		$path  = (string) wp_parse_url( $remote, PHP_URL_PATH );
		$local = $this->store( $remote, 'avatar-' . md5( $path ) . '-' . self::AVATAR_SIZE, self::AVATAR_SIZE, self::AVATAR_SIZE );
		if ( null === $local ) {
			return $remote;
		}

		$this->prune( array( $local['file'] ), 'avatar-' );

		return $local['url'];
	}

	/**
	 * Return the stored copy, creating it first if needed and still in budget.
	 *
	 * @param string $remote Source address.
	 * @param string $name   File name without extension.
	 * @param int    $width  Target width.
	 * @param int    $height Target height; the image is cropped to this ratio.
	 * @return array{file: string, url: string, width: int, height: int}|null
	 */
	private function store( string $remote, string $name, int $width, int $height ): ?array {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return null;
		}

		$dir  = trailingslashit( $uploads['basedir'] ) . self::DIR;
		$file = $dir . '/' . $name . '.webp';
		$url  = trailingslashit( $uploads['baseurl'] ) . self::DIR . '/' . $name . '.webp';

		if ( file_exists( $file ) ) {
			return $this->describe( $file, $url );
		}

		if ( microtime( true ) - $this->started > self::TIME_BUDGET ) {
			return null;
		}

		if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) || ! wp_mkdir_p( $dir ) ) {
			return null;
		}

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$tmp = download_url( $remote, 15 );
		if ( is_wp_error( $tmp ) ) {
			return null;
		}

		$editor = wp_get_image_editor( $tmp );
		if ( is_wp_error( $editor ) ) {
			wp_delete_file( $tmp );
			return null;
		}

		// A centred crop, as object-fit: cover shows it. resize() refuses to
		// enlarge and returns an error for a source smaller than the target; that
		// image is kept at its own size and only re-encoded, which CSS then crops.
		$editor->resize( $width, $height, true );
		$editor->set_quality( self::QUALITY );
		$saved = $editor->save( $file, 'image/webp' );
		wp_delete_file( $tmp );

		if ( is_wp_error( $saved ) ) {
			return null;
		}

		return $this->describe( $file, $url );
	}

	/**
	 * Path, address and pixel size of a stored copy.
	 *
	 * @param string $file Absolute path.
	 * @param string $url  Public address.
	 * @return array{file: string, url: string, width: int, height: int}|null
	 */
	private function describe( string $file, string $url ): ?array {
		$size = wp_getimagesize( $file );
		if ( ! is_array( $size ) ) {
			return null;
		}

		return array(
			'file'   => $file,
			'url'    => $url,
			'width'  => (int) $size[0],
			'height' => (int) $size[1],
		);
	}

	/**
	 * Delete post copies no longer referenced: posts that dropped out of the
	 * feed and copies made at a width that is no longer selected.
	 *
	 * @param array<int, string> $keep   Absolute paths still in use.
	 * @param string             $prefix File name prefix to consider.
	 * @return void
	 */
	private function prune( array $keep, string $prefix ): void {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return;
		}

		$files = glob( trailingslashit( $uploads['basedir'] ) . self::DIR . '/' . $prefix . '*.webp' );
		if ( ! is_array( $files ) ) {
			return;
		}

		foreach ( array_diff( $files, $keep ) as $stale ) {
			wp_delete_file( $stale );
		}
	}
}
