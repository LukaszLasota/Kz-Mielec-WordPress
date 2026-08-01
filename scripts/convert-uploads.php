<?php
/**
 * Generate AVIF and WebP siblings for the images already in wp-content/uploads.
 *
 * The theme's ModernImages class offers a modern format only when the file is
 * on disk next to the original, so this is what puts it there. It never touches
 * the original and never overwrites a newer conversion, which makes it safe to
 * run repeatedly — it only does the work still missing.
 *
 * Run it inside DDEV, where PHP has GD with WebP and AVIF support:
 *
 *   ddev exec php scripts/convert-uploads.php --dry-run
 *   ddev exec php scripts/convert-uploads.php --limit=40
 *   ddev exec php scripts/convert-uploads.php
 *
 * Quality: WebP at 82 and AVIF at 55 are the settings where these photographs
 * stop losing anything visible while still collapsing to a fraction of a PNG.
 * A conversion that comes out larger than the original is discarded — that
 * happens with flat line art, where PNG is already the right format.
 *
 * @package Kzmielec
 */

declare( strict_types = 1 );

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

const WEBP_QUALITY = 82;
const AVIF_QUALITY = 55;

$options = getopt( '', array( 'dry-run', 'limit::', 'only::' ) );
$dry_run = isset( $options['dry-run'] );
$limit   = isset( $options['limit'] ) ? (int) $options['limit'] : 0;
$only    = isset( $options['only'] ) ? (string) $options['only'] : '';

$formats = array( 'avif', 'webp' );

if ( '' !== $only ) {
	$formats = array_values( array_intersect( $formats, array( $only ) ) );
}

if ( array() === $formats ) {
	fwrite( STDERR, "--only accepts avif or webp\n" );
	exit( 1 );
}

$root = dirname( __DIR__ ) . '/wp-content/uploads';

if ( ! is_dir( $root ) ) {
	fwrite( STDERR, "No uploads directory at {$root}\n" );
	exit( 1 );
}

/**
 * Load an image, flattened to true colour with alpha preserved.
 *
 * @param string $path Source file.
 * @return \GdImage|null
 */
function load_image( string $path ): ?\GdImage {
	$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

	$image = 'png' === $extension ? @imagecreatefrompng( $path ) : @imagecreatefromjpeg( $path );

	if ( false === $image ) {
		return null;
	}

	imagepalettetotruecolor( $image );
	imagealphablending( $image, true );
	imagesavealpha( $image, true );

	return $image;
}

$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
$sources  = array();

foreach ( $iterator as $file ) {
	if ( ! $file instanceof SplFileInfo || ! $file->isFile() ) {
		continue;
	}

	if ( 1 === preg_match( '/\.(jpe?g|png)$/i', $file->getFilename() ) ) {
		$sources[] = $file->getPathname();
	}
}

sort( $sources );

$stats = array(
	'considered' => 0,
	'written'    => 0,
	'skipped'    => 0,
	'discarded'  => 0,
	'source'     => 0,
	'output'     => 0,
);

$started = microtime( true );

foreach ( $sources as $source ) {
	if ( 0 !== $limit && $stats['written'] >= $limit ) {
		break;
	}

	$source_size = (int) filesize( $source );
	$image       = null;

	foreach ( $formats as $format ) {
		$target = preg_replace( '/\.(jpe?g|png)$/i', '.' . $format, $source );

		if ( ! is_string( $target ) || $target === $source ) {
			continue;
		}

		++$stats['considered'];

		if ( file_exists( $target ) && filemtime( $target ) >= filemtime( $source ) ) {
			++$stats['skipped'];
			continue;
		}

		if ( $dry_run ) {
			++$stats['written'];
			continue;
		}

		if ( null === $image ) {
			$image = load_image( $source );

			if ( null === $image ) {
				fwrite( STDERR, "  unreadable: {$source}\n" );
				break;
			}
		}

		$written = 'avif' === $format
			? @imageavif( $image, $target, AVIF_QUALITY )
			: @imagewebp( $image, $target, WEBP_QUALITY );

		if ( ! $written || ! file_exists( $target ) ) {
			fwrite( STDERR, "  failed: {$target}\n" );
			continue;
		}

		$target_size = (int) filesize( $target );

		// Line art often encodes larger than its PNG; keeping it would make the
		// page slower while looking like an optimisation.
		if ( $target_size >= $source_size ) {
			unlink( $target );
			++$stats['discarded'];
			continue;
		}

		++$stats['written'];
		$stats['source'] += $source_size;
		$stats['output'] += $target_size;
	}

	if ( null !== $image ) {
		imagedestroy( $image );
	}
}

$elapsed = microtime( true ) - $started;
$kb      = static fn( int $bytes ): string => number_format( $bytes / 1024, 0, ',', ' ' ) . ' KB';

printf( "%s%d źródeł, %d kandydatów\n", $dry_run ? "PRÓBNIE — nic nie zapisano\n" : '', count( $sources ), $stats['considered'] );
printf( "  zapisane:   %d\n", $stats['written'] );
printf( "  pominięte:  %d (już aktualne)\n", $stats['skipped'] );
printf( "  odrzucone:  %d (wynik nie mniejszy od źródła)\n", $stats['discarded'] );

if ( 0 < $stats['source'] ) {
	printf(
		"  waga:       %s → %s  (−%d%%)\n",
		$kb( $stats['source'] ),
		$kb( $stats['output'] ),
		(int) round( 100 - ( $stats['output'] / $stats['source'] * 100 ) )
	);
}

printf( "  czas:       %.1fs\n", $elapsed );
