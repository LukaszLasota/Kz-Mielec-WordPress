<?php
/**
 * SVG Block rendering on the front-end.
 *
 * This file handles the server-side rendering of the SVG block.
 * It sanitizes SVG content for security and applies attributes from the block editor.
 *
 * @package CustomBlockPackage
 */

// SVG sanitization helper function.
if ( ! function_exists( 'sanitize_svg_content_custom' ) ) {
	/**
	 * Securely cleans SVG content using wp_kses with a simplified whitelist approach.
	 *
	 * Uses a medium-level whitelist of SVG elements and attributes that covers
	 * most common use cases while maintaining security.
	 *
	 * @since 1.0.0
	 *
	 * @param string $svg SVG content to sanitize.
	 * @return string Sanitized SVG content.
	 */
	function sanitize_svg_content_custom( $svg ) {
		// Return empty string if SVG is empty.
		if ( empty( $svg ) ) {
			return '';
		}

		// Pre-kses sanitization for things kses doesn't catch.
		$svg = preg_replace( '/<!DOCTYPE[^>]*>/i', '', $svg );
		$svg = preg_replace( '/<!ENTITY[^>]*>/i', '', $svg );
		$svg = preg_replace( '/<!--(.|\s)*?-->/', '', $svg );

		// Define allowed SVG elements and attributes - medium level.
		$allowed_svg_tags = array(
			// Main SVG element.
			'svg'            => array(
				'xmlns'               => true,
				'viewbox'             => true,
				'viewBox'             => true, // Both cases for better compatibility.
				'width'               => true,
				'height'              => true,
				'fill'                => true,
				'stroke'              => true,
				'stroke-width'        => true,
				'class'               => true,
				'id'                  => true,
				'style'               => true,
				'preserveaspectratio' => true,
				'version'             => true,
				'xmlns:xlink'         => true,
				'role'                => true,
				'aria-labelledby'     => true,
				'aria-hidden'         => true,
			),
			// Basic shapes.
			'path'           => array(
				'd'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'id'              => true,
				'class'           => true,
				'style'           => true,
				'transform'       => true,
				'opacity'         => true,
				'fill-rule'       => true,
				'clip-rule'       => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
			),
			'rect'           => array(
				'x'            => true,
				'y'            => true,
				'width'        => true,
				'height'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'rx'           => true,
				'ry'           => true,
				'id'           => true,
				'class'        => true,
				'style'        => true,
				'transform'    => true,
			),
			'circle'         => array(
				'cx'           => true,
				'cy'           => true,
				'r'            => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'id'           => true,
				'class'        => true,
				'style'        => true,
			),
			'ellipse'        => array(
				'cx'           => true,
				'cy'           => true,
				'rx'           => true,
				'ry'           => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'id'           => true,
				'class'        => true,
			),
			'line'           => array(
				'x1'           => true,
				'y1'           => true,
				'x2'           => true,
				'y2'           => true,
				'stroke'       => true,
				'stroke-width' => true,
				'id'           => true,
				'class'        => true,
			),
			'polyline'       => array(
				'points'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'id'           => true,
				'class'        => true,
			),
			'polygon'        => array(
				'points'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'id'           => true,
				'class'        => true,
			),
			// Grouping.
			'g'              => array(
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'transform'    => true,
				'id'           => true,
				'class'        => true,
				'style'        => true,
			),
			// Text.
			'text'           => array(
				'x'           => true,
				'y'           => true,
				'fill'        => true,
				'font-size'   => true,
				'font-family' => true,
				'id'          => true,
				'class'       => true,
				'text-anchor' => true,
			),
			'tspan'          => array(
				'x'         => true,
				'y'         => true,
				'fill'      => true,
				'font-size' => true,
				'id'        => true,
				'class'     => true,
			),
			// Common reusable elements.
			'defs'           => array( 'id' => true ),
			'style'          => array( 'type' => true ),
			'title'          => array( 'id' => true ),
			'desc'           => array( 'id' => true ),
			'use'            => array(
				'href'       => true,
				'xlink:href' => true,
				'x'          => true,
				'y'          => true,
				'width'      => true,
				'height'     => true,
				'transform'  => true,
			),
			// Basic gradients.
			'linearGradient' => array(
				'id'            => true,
				'x1'            => true,
				'y1'            => true,
				'x2'            => true,
				'y2'            => true,
				'gradientunits' => true,
			),
			'radialGradient' => array(
				'id'            => true,
				'cx'            => true,
				'cy'            => true,
				'r'             => true,
				'fx'            => true,
				'fy'            => true,
				'gradientunits' => true,
			),
			'stop'           => array(
				'offset'       => true,
				'stop-color'   => true,
				'stop-opacity' => true,
			),
			'pattern'        => array(
				'id'                  => true,
				'patternUnits'        => true,
				'patternContentUnits' => true,
				'patterncontentunits' => true,
				'viewBox'             => true,
				'width'               => true,
				'height'              => true,
				'x'                   => true,
				'y'                   => true,
				'preserveAspectRatio' => true,
				'class'               => true,
				'style'               => true,
			),
			'image'          => array(
				'id'                  => true,
				'width'               => true,
				'height'              => true,
				'x'                   => true,
				'y'                   => true,
				'href'                => true,
				'xlink:href'          => true,
				'preserveAspectRatio' => true,
				'transform'           => true,
				'class'               => true,
				'style'               => true,
			),
		);

		// Apply wp_kses with allowed SVG elements and attributes.
		return wp_kses( $svg, $allowed_svg_tags );
	}
}

// SVG modification helper function.
if ( ! function_exists( 'apply_svg_attributes' ) ) {
	/**
	 * Applies specified attributes to SVG content.
	 *
	 * This function takes the SVG content and block attributes, then modifies
	 * the SVG tag to include dimensions, colors, classes, and other settings
	 * while preserving existing attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $svg_content Original SVG content.
	 * @param array  $attributes  Block attributes to apply.
	 * @return string             Modified SVG content.
	 */
	function apply_svg_attributes( $svg_content, $attributes ) {
		// Check if we have SVG content.
		if ( empty( $svg_content ) ) {
			return '';
		}

		// Extract attributes from the block.
		$svg_width          = ! empty( $attributes['svgWidth'] ) ? esc_attr( $attributes['svgWidth'] ) : null;
		$svg_height         = ! empty( $attributes['svgHeight'] ) ? esc_attr( $attributes['svgHeight'] ) : null;
		$svg_fill           = ! empty( $attributes['svgFill'] ) ? esc_attr( $attributes['svgFill'] ) : null;
		$svg_stroke         = ! empty( $attributes['svgStroke'] ) ? esc_attr( $attributes['svgStroke'] ) : null;
		$apply_color_to_all = ! empty( $attributes['applyColorToAllElements'] );
		$extra_class        = ! empty( $attributes['blockClasses'] ) ? esc_attr( $attributes['blockClasses'] ) : '';

		// Generate unique ID for SVG if needed for styles.
		$unique_id = 'svg-' . uniqid();

		// Find the SVG tag using regular expression.
		if ( preg_match( '/<svg[^>]*>/', $svg_content, $svg_tag ) ) {
			$original_svg_tag = $svg_tag[0];
			$new_svg_tag      = $original_svg_tag;

			// Absolutely add aria-hidden=“true” to each SVG
			if ( preg_match( '/\baria-hidden\s*=\s*["\'][^"\']*["\']/i', $new_svg_tag ) ) {
				$new_svg_tag = preg_replace( '/\baria-hidden\s*=\s*["\'][^"\']*["\']/i', 'aria-hidden="true"', $new_svg_tag );
			} else {
				$new_svg_tag = str_replace( '<svg', '<svg aria-hidden="true"', $new_svg_tag );
			}

			// Ensure we have a consistent ID to use in CSS selector.
			$target_id = $unique_id;
			if ( $apply_color_to_all && ( $svg_fill || $svg_stroke ) ) {
				if ( preg_match( '/\bid\s*=\s*["\']([^"\']+)["\']/i', $new_svg_tag, $id_match ) ) {
					// Use existing ID if found.
					$target_id = $id_match[1];
				} else {
					// Add new ID if none exists.
					$new_svg_tag = str_replace( '<svg', '<svg id="' . $target_id . '"', $new_svg_tag );
				}
			}

			// Add dimensions as attributes if specified.
			if ( $svg_width ) {
				if ( preg_match( '/\bwidth\s*=\s*["\'][^"\']*["\']/i', $new_svg_tag ) ) {
					$new_svg_tag = preg_replace( '/\bwidth\s*=\s*["\'][^"\']*["\']/i', 'width="' . $svg_width . '"', $new_svg_tag );
				} else {
					$new_svg_tag = str_replace( '<svg', '<svg width="' . $svg_width . '"', $new_svg_tag );
				}
			}

			if ( $svg_height ) {
				if ( preg_match( '/\bheight\s*=\s*["\'][^"\']*["\']/i', $new_svg_tag ) ) {
					$new_svg_tag = preg_replace( '/\bheight\s*=\s*["\'][^"\']*["\']/i', 'height="' . $svg_height . '"', $new_svg_tag );
				} else {
					$new_svg_tag = str_replace( '<svg', '<svg height="' . $svg_height . '"', $new_svg_tag );
				}
			}

			// Add colors as attributes, only if we're not applying to all elements.
			if ( ! $apply_color_to_all ) {
				if ( $svg_fill ) {
					if ( preg_match( '/\bfill\s*=\s*["\'][^"\']*["\']/i', $new_svg_tag ) ) {
						$new_svg_tag = preg_replace( '/\bfill\s*=\s*["\'][^"\']*["\']/i', 'fill="' . $svg_fill . '"', $new_svg_tag );
					} else {
						$new_svg_tag = str_replace( '<svg', '<svg fill="' . $svg_fill . '"', $new_svg_tag );
					}
				}

				if ( $svg_stroke ) {
					if ( preg_match( '/\bstroke\s*=\s*["\'][^"\']*["\']/i', $new_svg_tag ) ) {
						$new_svg_tag = preg_replace( '/\bstroke\s*=\s*["\'][^"\']*["\']/i', 'stroke="' . $svg_stroke . '"', $new_svg_tag );
					} else {
						$new_svg_tag = str_replace( '<svg', '<svg stroke="' . $svg_stroke . '"', $new_svg_tag );
					}
				}
			}

			// Add CSS classes from block editor directly to SVG.
			if ( $extra_class ) {
				if ( preg_match( '/\bclass\s*=\s*["\'][^"\']*["\']/i', $new_svg_tag ) ) {
					// If SVG already has a class attribute, add our classes.
					$new_svg_tag = preg_replace(
						'/\bclass\s*=\s*["\']([^"\']*)["\']/i',
						'class="$1 ' . esc_attr( $extra_class ) . '"',
						$new_svg_tag
					);
				} else {
					// Otherwise add a new class attribute.
					$new_svg_tag = str_replace(
						'<svg',
						'<svg class="' . esc_attr( $extra_class ) . '"',
						$new_svg_tag
					);
				}
			}

			// Replace the original SVG tag with the new one.
			$svg_content = str_replace( $original_svg_tag, $new_svg_tag, $svg_content );
		}

		// If we need to apply colors to all elements, add CSS style.
		if ( $apply_color_to_all && ( $svg_fill || $svg_stroke ) ) {
			$style  = '<style>';
			$style .= '#' . $target_id . ' * {';

			if ( $svg_fill ) {
				$style .= 'fill: ' . $svg_fill . ' !important;';
			}

			if ( $svg_stroke ) {
				$style .= 'stroke: ' . $svg_stroke . ' !important;';
			}

			$style .= '}';
			$style .= '</style>';

			// Add style before closing SVG tag.
			$svg_content = str_replace( '</svg>', $style . '</svg>', $svg_content );
		}

		return $svg_content;
	}
}

// Main render logic - check if we have SVG content.
if ( ! empty( $attributes['svgContent'] ) ) {
	// Sanitize SVG for security.
	$svg_content = sanitize_svg_content_custom( $attributes['svgContent'] );

	// Get CSS classes from Gutenberg.
	$wrapper_attributes = get_block_wrapper_attributes();
	$block_classes      = '';

	// Extract classes from wrapper attributes.
	if ( preg_match( '/class="([^"]*)"/i', $wrapper_attributes, $matches ) ) {
		if ( ! empty( $matches[1] ) ) {
			$block_classes = $matches[1];
		}
	}

	// Add classes to attributes.
	$attributes['blockClasses'] = $block_classes;

	// Apply attributes to SVG.
	$svg_content = apply_svg_attributes( $svg_content, $attributes );

	// Extract link attributes.
	$wrap_with_link = ! empty( $attributes['wrapWithLink'] );
	$link_url       = ! empty( $attributes['linkUrl'] ) ? esc_url( $attributes['linkUrl'] ) : '';
	$link_target    = ! empty( $attributes['linkTarget'] ) ? esc_attr( $attributes['linkTarget'] ) : '_self';
	$link_rel       = ! empty( $attributes['linkRel'] ) ? esc_attr( $attributes['linkRel'] ) : '';

	// Output just the SVG without additional wrappers.
	if ( $wrap_with_link && $link_url ) {
		// Output SVG wrapped in a link
		echo '<a href="' . $link_url . '" target="' . $link_target . '" rel="' . $link_rel . '">';
		echo $svg_content;
		echo '</a>';
	} else {
		// Output just the SVG without additional wrappers.
		echo $svg_content;
	}
}
// If there's no SVG content, we don't output anything.
