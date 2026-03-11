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

$latitude        = isset( $attributes['latitude'] ) ? (float) $attributes['latitude'] : 50.031562;
$longitude       = isset( $attributes['longitude'] ) ? (float) $attributes['longitude'] : 21.997937;
$zoom            = isset( $attributes['zoom'] ) ? (int) $attributes['zoom'] : 16;
$containerHeight = isset( $attributes['containerHeight'] ) ? (int) $attributes['containerHeight'] : 300;
$popupText       = isset( $attributes['popupText'] ) ? $attributes['popupText'] : __( 'Nasza lokalizacja', 'custom-block-package' );

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
		style="width: 100%; height: <?php echo esc_attr( (string) $containerHeight ); ?>px;"
	></div>
</div>