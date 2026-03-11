<?php
/**
 * Section Block Server-Side Render
 *
 * @package CustomBlockPackage
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$allowed_tags = [ 'div', 'section', 'article', 'main', 'header', 'footer', 'aside' ];
$tag_name     = in_array( $attributes['tagName'] ?? 'div', $allowed_tags, true ) ? $attributes['tagName'] : 'div';
$layout_type  = $attributes['layoutType'] ?? 'none';

// Build CSS classes.
$classes = array( 'cbp-block', 'section-block' );

if ( 'grid' === $layout_type ) {
	$classes[] = 'is-grid';
} elseif ( 'flex' === $layout_type ) {
	$classes[] = 'is-flex';
}

// Build inline styles.
$styles = array();

if ( ! empty( $attributes['backgroundImage'] ) ) {
	$styles[] = 'background-image:url(' . esc_url( $attributes['backgroundImage'] ) . ')';
}

if ( ! empty( $attributes['blockWidth'] ) ) {
	if ( preg_match( '/^\d+(%|px|em|rem|vw|ch)$/', $attributes['blockWidth'] ) ) {
		$styles[] = 'width:' . $attributes['blockWidth'];
		$styles[] = 'margin-inline:auto';
	}
}

// Allowed CSS values for validation.
$allowed_justify_items   = [ '', 'start', 'end', 'center', 'stretch', 'baseline' ];
$allowed_align_content   = [ '', 'start', 'end', 'center', 'stretch', 'space-between', 'space-around', 'space-evenly' ];
$allowed_flex_wrap       = [ '', 'nowrap', 'wrap', 'wrap-reverse' ];
$allowed_flex_direction  = [ '', 'row', 'row-reverse', 'column', 'column-reverse' ];
$allowed_align_items     = [ '', 'start', 'end', 'center', 'stretch', 'baseline' ];
$allowed_justify_content = [ '', 'start', 'end', 'center', 'space-between', 'space-around', 'space-evenly' ];

// Grid CSS custom properties.
if ( 'grid' === $layout_type ) {
	$styles[] = '--columns-mobile:' . ( $attributes['columnsMobile'] ?? 1 );
	$styles[] = '--columns-small-tablet:' . ( $attributes['columnsSmallTablet'] ?? 2 );
	$styles[] = '--columns-large-tablet:' . ( $attributes['columnsLargeTablet'] ?? 3 );
	$styles[] = '--columns-desktop:' . ( $attributes['columnsDesktop'] ?? 4 );
	$styles[] = '--grid-gap:' . ( $attributes['gridGap'] ?? 10 ) . 'px';

	if ( ! empty( $attributes['justifyItems'] ) ) {
		$styles[] = '--justify-items:' . ( in_array( $attributes['justifyItems'], $allowed_justify_items, true ) ? $attributes['justifyItems'] : '' );
	}
	if ( ! empty( $attributes['alignContent'] ) ) {
		$styles[] = '--align-content:' . ( in_array( $attributes['alignContent'], $allowed_align_content, true ) ? $attributes['alignContent'] : '' );
	}
}

// Flex CSS custom properties.
if ( 'flex' === $layout_type ) {
	$styles[] = '--display:flex';

	if ( ! empty( $attributes['flexWrap'] ) ) {
		$styles[] = '--flex-wrap:' . ( in_array( $attributes['flexWrap'], $allowed_flex_wrap, true ) ? $attributes['flexWrap'] : '' );
	}
	if ( ! empty( $attributes['flexDirection'] ) ) {
		$styles[] = '--flex-direction:' . ( in_array( $attributes['flexDirection'], $allowed_flex_direction, true ) ? $attributes['flexDirection'] : '' );
	}
	if ( ! empty( $attributes['alignItems'] ) ) {
		$styles[] = '--align-items:' . ( in_array( $attributes['alignItems'], $allowed_align_items, true ) ? $attributes['alignItems'] : '' );
	}
	if ( ! empty( $attributes['justifyContent'] ) ) {
		$styles[] = '--justify-content:' . ( in_array( $attributes['justifyContent'], $allowed_justify_content, true ) ? $attributes['justifyContent'] : '' );
	}
}

$style_string = implode( ';', $styles );

$wrapper_extra = array(
	'class' => implode( ' ', $classes ),
	'style' => $style_string,
);
if ( ! empty( $attributes['anchor'] ) ) {
	$wrapper_extra['id'] = $attributes['anchor'];
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_extra );

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- $wrapper_attributes is pre-escaped by get_block_wrapper_attributes(), $content is pre-rendered inner blocks.
printf(
	'<%1$s %2$s>%3$s</%1$s>',
	esc_attr( $tag_name ),
	$wrapper_attributes,
	$content
);
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
