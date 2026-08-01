<?php
/**
 * Navigable Tiles Block Render.
 *
 * Renders meetings (CPT) or beliefs (Pages) as circular navigation tiles.
 * Semantic list markup, decorative image stack, "you are here" indicator.
 *
 * @package CustomBlockPackage
 *
 * @var array $attributes Block attributes.
 */

declare(strict_types=1);

use CustomBlockPackage\Services\NavigableTilesService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'cbp_navigable_tiles_config' ) ) {
	/**
	 * Parse and validate block attributes into a typed config array.
	 *
	 * @param array<string, mixed> $attributes Raw block attributes.
	 * @return array<string, mixed>
	 */
	function cbp_navigable_tiles_config( array $attributes ): array {
		$data_source = isset( $attributes['dataSource'] ) && 'meetings' === $attributes['dataSource']
			? 'meetings'
			: 'beliefs';

		return array(
			'data_source'       => $data_source,
			'columns'           => max( 1, min( 6, (int) ( $attributes['columns'] ?? 4 ) ) ),
			'show_day_hour'     => ! empty( $attributes['showDayHour'] ),
			'highlight_current' => ! isset( $attributes['highlightCurrent'] ) || (bool) $attributes['highlightCurrent'],
			'section_title'     => (string) ( $attributes['sectionTitle'] ?? '' ),
			'anchor'            => (string) ( $attributes['anchor'] ?? '' ),
			'current_page_id'   => (int) get_queried_object_id(),
		);
	}
}

if ( ! function_exists( 'cbp_navigable_tiles_items' ) ) {
	/**
	 * Fetch normalized items for the given data source.
	 *
	 * @param string $data_source Either 'meetings' or 'beliefs'.
	 * @return array<int, array<string, mixed>>
	 */
	function cbp_navigable_tiles_items( string $data_source ): array {
		return 'meetings' === $data_source
			? NavigableTilesService::get_meetings()
			: NavigableTilesService::get_beliefs();
	}
}

if ( ! function_exists( 'cbp_navigable_tiles_wrapper_attrs' ) ) {
	/**
	 * Build the block wrapper attributes.
	 *
	 * @param array<string, mixed> $config Parsed config.
	 * @return array<string, string>
	 */
	function cbp_navigable_tiles_wrapper_attrs( array $config ): array {
		// Two tile blocks share the front page, so the fallback label has to name
		// the section, not the role — a screen reader already announces "navigation",
		// and two landmarks both called "Nawigacja" are indistinguishable in the
		// landmark list. An author-supplied section title always wins.
		$fallback_label = 'meetings' === $config['data_source']
			? __( 'Spotkania', 'custom-block-package' )
			: __( 'W co wierzymy', 'custom-block-package' );

		$attrs = array(
			'class'      => 'has-source-' . $config['data_source'] . ' has-columns-' . $config['columns'],
			'aria-label' => '' !== $config['section_title'] ? $config['section_title'] : $fallback_label,
		);

		if ( '' !== $config['anchor'] ) {
			$attrs['id'] = $config['anchor'];
		}

		return $attrs;
	}
}

if ( ! function_exists( 'cbp_navigable_tiles_image' ) ) {
	/**
	 * Render one tile image through WordPress so it carries srcset and dimensions.
	 *
	 * The block used to print a raw `<img src>` pointing at the `full` size: on the
	 * beliefs page that is 16 images, and the photographic ones are 131 KB each at
	 * 400x400, downloaded at full size on a phone that shows them 150px wide.
	 * `wp_get_attachment_image()` emits the whole srcset WordPress already
	 * generated, plus the intrinsic width and height.
	 *
	 * `sizes` is derived from the column count rather than left at the default
	 * `100vw`, which would tell the browser these are full-width images and defeat
	 * the point. Tiles are one per row below 600px, two up to 1024px, and
	 * `columns` per row above that.
	 *
	 * The alt is empty on purpose: the tile's own link text carries the meaning and
	 * the wrapper is aria-hidden, so a description here would be read twice.
	 *
	 * @param int    $attachment_id Attachment to render.
	 * @param string $class_name    Class for the img tag.
	 * @param int    $columns       Tiles per row on a wide screen.
	 * @return void
	 */
	function cbp_navigable_tiles_image( int $attachment_id, string $class_name, int $columns ): void {
		$wide = max( 1, (int) round( 100 / max( 1, $columns ) ) );

		echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress escapes the attributes it builds.
			$attachment_id,
			'medium_large',
			false,
			array(
				'class'   => $class_name,
				'alt'     => '',
				'loading' => 'lazy',
				'sizes'   => '(max-width: 600px) 100vw, (max-width: 1024px) 50vw, ' . $wide . 'vw',
			)
		);
	}
}

if ( ! function_exists( 'cbp_navigable_tiles_render_tile' ) ) {
	/**
	 * Render a single tile as a list item.
	 *
	 * @param array<string, mixed> $item   Normalized item data.
	 * @param array<string, mixed> $config Parsed config.
	 * @return void
	 */
	function cbp_navigable_tiles_render_tile( array $item, array $config ): void {
		$is_current  = $config['highlight_current'] && ( (int) $item['page_id'] === $config['current_page_id'] );
		$base_image  = (string) ( $item['image_base'] ?? '' );
		$hover_image = (string) ( $item['image_hover'] ?? '' );
		$base_id     = (int) ( $item['image_base_id'] ?? 0 );
		$hover_id    = (int) ( $item['image_hover_id'] ?? 0 );
		$title       = (string) ( $item['title'] ?? '' );
		$link        = (string) ( $item['link'] ?? '' );
		$day_hour    = (string) ( $item['day_hour'] ?? '' );
		$has_overlay = 'beliefs' === $config['data_source'];
		$show_hover  = ! $is_current && '' !== $hover_image;
		$show_meta   = $config['show_day_hour'] && 'meetings' === $config['data_source'] && '' !== $day_hour;
		?>
		<li class="navigable-tiles__item<?php echo $is_current ? ' is-current' : ''; ?>">
			<span class="navigable-tiles__image" aria-hidden="true">
				<?php
				if ( $base_id ) {
					cbp_navigable_tiles_image( $base_id, 'navigable-tiles__image--one', $config['columns'] );
				} elseif ( '' !== $base_image ) {
					?>
					<img class="navigable-tiles__image--one" src="<?php echo esc_url( $base_image ); ?>" alt="" loading="lazy">
					<?php
				}
				?>
				<?php if ( $has_overlay ) : ?>
					<span class="navigable-tiles__image--overlay"></span>
				<?php endif; ?>
				<?php
				if ( $show_hover ) {
					if ( $hover_id ) {
						cbp_navigable_tiles_image( $hover_id, 'navigable-tiles__image--two', $config['columns'] );
					} else {
						?>
						<img class="navigable-tiles__image--two" src="<?php echo esc_url( $hover_image ); ?>" alt="" loading="lazy">
						<?php
					}
				}
				?>
			</span>
			<h3 class="navigable-tiles__title">
				<a
					href="<?php echo esc_url( $link ); ?>"
					class="navigable-tiles__link"
					<?php echo $is_current ? 'aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $title ); ?>
					<?php if ( $is_current ) : ?>
						<span class="screen-reader-text"> <?php esc_html_e( '(aktualna strona)', 'custom-block-package' ); ?></span>
					<?php endif; ?>
				</a>
			</h3>
			<?php if ( $show_meta ) : ?>
				<span class="navigable-tiles__meta"><?php echo esc_html( $day_hour ); ?></span>
			<?php endif; ?>
		</li>
		<?php
	}
}

$config = cbp_navigable_tiles_config( $attributes );
$items  = cbp_navigable_tiles_items( $config['data_source'] );

if ( empty( $items ) ) {
	return;
}
?>
<nav <?php echo get_block_wrapper_attributes( cbp_navigable_tiles_wrapper_attrs( $config ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Returns pre-escaped HTML. ?>>

	<?php if ( '' !== $config['section_title'] ) : ?>
		<h2 class="navigable-tiles__heading"><?php echo esc_html( $config['section_title'] ); ?></h2>
	<?php endif; ?>

	<ul class="navigable-tiles__grid" role="list">
		<?php
		foreach ( $items as $item ) {
			cbp_navigable_tiles_render_tile( $item, $config );
		}
		?>
	</ul>
</nav>