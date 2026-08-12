<?php
/**
 * Map Block Render Template
 *
 * @package CustomBlockPackage
 *
 * @var array $attributes Block attributes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Coordinates come from the congregation's contact settings unless this particular map
 * was deliberately pointed somewhere else.
 *
 * `block.json` carries a placeholder pair, which core merges into `$attributes` for every
 * instance, so "the author chose nothing" and "the author chose the placeholder" look
 * identical here. An instance still on the placeholder therefore follows the settings; an
 * instance holding anything else keeps what it holds, because a second map at a different
 * address has to stay possible.
 *
 * The placeholder is the congregation's own location, and it has to be. It used to be a
 * spot near Rzeszów, and the editor's own preview reads `block.json` directly — so once the
 * stored coordinates were removed from the pages, every map showed the wrong town while
 * being edited, and the right one once published. A placeholder nobody can see is a
 * placeholder; one the editor draws on a map has to be true.
 *
 * The option is read directly rather than through the theme's `ContactData`, so this plugin
 * keeps working with any theme — the same arrangement as `kzmielec_belief_pages` in
 * NavigableTilesService.
 */
$placeholder_lat = 50.299071;
$placeholder_lng = 21.4483254;

$latitude  = isset( $attributes['latitude'] ) ? (float) $attributes['latitude'] : $placeholder_lat;
$longitude = isset( $attributes['longitude'] ) ? (float) $attributes['longitude'] : $placeholder_lng;

if ( abs( $latitude - $placeholder_lat ) < 0.000001 && abs( $longitude - $placeholder_lng ) < 0.000001 ) {
	$contact = get_option( 'kzmielec_contact', array() );

	if ( is_array( $contact ) ) {
		$from_settings_lat = isset( $contact['latitude'] ) ? trim( (string) $contact['latitude'] ) : '';
		$from_settings_lng = isset( $contact['longitude'] ) ? trim( (string) $contact['longitude'] ) : '';

		if ( is_numeric( $from_settings_lat ) && is_numeric( $from_settings_lng ) ) {
			$latitude  = (float) $from_settings_lat;
			$longitude = (float) $from_settings_lng;
		}
	}
}
$zoom            = isset( $attributes['zoom'] ) ? (int) $attributes['zoom'] : 16;
$containerHeight = isset( $attributes['containerHeight'] ) ? (int) $attributes['containerHeight'] : 400;
$popupText       = isset( $attributes['popupText'] ) ? $attributes['popupText'] : __( 'Nasza lokalizacja', 'custom-block-package' );

$allowed_styles = array( 'standard', 'voyager', 'positron', 'dark', 'satellite', 'satelliteLabels', 'terrain', 'esriStreet', 'esriTopo', 'esriGray', 'humanitarian' );
$tile_style     = isset( $attributes['tileStyle'] ) && in_array( $attributes['tileStyle'], $allowed_styles, true )
	? $attributes['tileStyle']
	: 'voyager';

// Unique ID for multiple instances.
$map_id = 'map-' . wp_unique_id();

// Icon paths from build directory.
$icon_url   = plugins_url( 'build/blocks/map-block/images/marker-icon.png', UP_PLUGIN_FILE );
$shadow_url = plugins_url( 'build/blocks/map-block/images/marker-shadow.png', UP_PLUGIN_FILE );

$wrapper_extra = array(
	'class'      => 'map-block-wrapper',
	'role'       => 'region',
	'aria-label' => __( 'Mapa lokalizacji', 'custom-block-package' ),
);
if ( ! empty( $attributes['anchor'] ) ) {
	$wrapper_extra['id'] = $attributes['anchor'];
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_extra );

?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped by get_block_wrapper_attributes(). ?>>
	<div
		id="<?php echo esc_attr( $map_id ); ?>"
		class="map-container"
		data-lat="<?php echo esc_attr( (string) $latitude ); ?>"
		data-lng="<?php echo esc_attr( (string) $longitude ); ?>"
		data-zoom="<?php echo esc_attr( (string) $zoom ); ?>"
		data-popup="<?php echo esc_attr( $popupText ); ?>"
		data-icon-url="<?php echo esc_url( $icon_url ); ?>"
		data-shadow-url="<?php echo esc_url( $shadow_url ); ?>"
		data-tile-style="<?php echo esc_attr( $tile_style ); ?>"
		style="width: 100%; height: <?php echo esc_attr( (string) $containerHeight ); ?>px;"
	></div>
</div>