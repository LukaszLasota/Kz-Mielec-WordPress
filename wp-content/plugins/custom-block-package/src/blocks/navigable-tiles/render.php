<?php
/**
 * Navigable Tiles Block Render.
 *
 * Renders a grid of tiles from meetings CPT or beliefs pages.
 * Supports "you are here" indicator on the current page tile.
 *
 * @package CustomBlockPackage
 *
 * @var array $attributes Block attributes.
 */

use CustomBlockPackage\Cache\BlockCache;
use CustomBlockPackage\Services\NavigableTilesService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data_source       = isset( $attributes['dataSource'] ) && 'meetings' === $attributes['dataSource'] ? 'meetings' : 'beliefs';
$columns           = isset( $attributes['columns'] ) ? max( 1, min( 6, (int) $attributes['columns'] ) ) : 4;
$show_day_hour     = ! empty( $attributes['showDayHour'] );
$highlight_current = ! isset( $attributes['highlightCurrent'] ) || (bool) $attributes['highlightCurrent'];
$section_title     = isset( $attributes['sectionTitle'] ) ? (string) $attributes['sectionTitle'] : '';

$cache_key = BlockCache::key( BlockCache::NAVIGABLE_TILES_PREFIX, $attributes );
$items     = get_transient( $cache_key );

if ( false === $items ) {
	$items = 'meetings' === $data_source
		? NavigableTilesService::get_meetings()
		: NavigableTilesService::get_beliefs();
	set_transient( $cache_key, $items, BlockCache::TTL );
}

if ( ! is_array( $items ) || empty( $items ) ) {
	return;
}

$current_page_id = (int) get_queried_object_id();

$wrapper_attrs = array(
	'class'      => 'has-source-' . $data_source . ' has-columns-' . $columns,
	'aria-label' => '' !== $section_title ? $section_title : __( 'Nawigacja', 'custom-block-package' ),
);
if ( ! empty( $attributes['anchor'] ) ) {
	$wrapper_attrs['id'] = $attributes['anchor'];
}
?>
<nav <?php echo get_block_wrapper_attributes( $wrapper_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Returns pre-escaped HTML. ?>>

	<?php if ( '' !== $section_title ) : ?>
		<h2 class="navigable-tiles__heading"><?php echo esc_html( $section_title ); ?></h2>
	<?php endif; ?>

	<ul class="navigable-tiles__grid" role="list">
		<?php
		foreach ( $items as $item ) :
			$is_current = $highlight_current && ( (int) $item['page_id'] === $current_page_id );
			?>
			<li class="navigable-tiles__item<?php echo $is_current ? ' is-current' : ''; ?>">
				<a
					href="<?php echo esc_url( (string) $item['link'] ); ?>"
					class="navigable-tiles__link"
					<?php
					if ( $is_current ) :
						?>
						aria-current="page"<?php endif; ?>
				>
					<span class="navigable-tiles__image" aria-hidden="true">
						<?php if ( '' !== (string) $item['image_base'] ) : ?>
							<img class="navigable-tiles__image--one" src="<?php echo esc_url( (string) $item['image_base'] ); ?>" alt="" loading="lazy">
						<?php endif; ?>
						<?php if ( 'beliefs' === $data_source ) : ?>
							<span class="navigable-tiles__image--black"></span>
						<?php endif; ?>
						<?php if ( ! $is_current && '' !== (string) $item['image_hover'] ) : ?>
							<img class="navigable-tiles__image--two" src="<?php echo esc_url( (string) $item['image_hover'] ); ?>" alt="" loading="lazy">
						<?php endif; ?>
					</span>
					<span class="navigable-tiles__title">
						<?php echo esc_html( (string) $item['title'] ); ?>
						<?php if ( $is_current ) : ?>
							<span class="screen-reader-text"> <?php esc_html_e( '(aktualna strona)', 'custom-block-package' ); ?></span>
						<?php endif; ?>
					</span>
					<?php if ( $show_day_hour && 'meetings' === $data_source && '' !== (string) $item['day_hour'] ) : ?>
						<span class="navigable-tiles__meta"><?php echo esc_html( (string) $item['day_hour'] ); ?></span>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
