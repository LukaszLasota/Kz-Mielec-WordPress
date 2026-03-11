<?php
/**
 * Group Link Support
 *
 * Adds clickable link functionality to Group blocks via custom metadata.
 * Users can add a link URL in the block's Advanced settings, and the entire
 * group becomes clickable.
 *
 * @package Church
 */

namespace Church\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Church\Interfaces\ActionHookInterface;
use Church\Interfaces\FilterHookInterface;

/**
 * Class GroupLinkSupport
 *
 * Enables Group blocks to function as clickable links.
 */
class GroupLinkSupport implements ActionHookInterface, FilterHookInterface {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_add_action();
		$this->register_add_filter();
	}

	/**
	 * Register WordPress action hooks.
	 *
	 * @return void
	 */
	public function register_add_action(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_script' ) );
	}

	/**
	 * Register WordPress filter hooks.
	 *
	 * @return void
	 */
	public function register_add_filter(): void {
		add_filter( 'render_block_core/group', array( $this, 'render_group_as_link' ), 10, 2 );
	}

	/**
	 * Enqueue editor JavaScript for adding link field to Group block.
	 *
	 * @return void
	 */
	public function enqueue_editor_script(): void {
		$script = "
		(function(wp) {
			if (!wp || !wp.hooks || !wp.compose || !wp.blockEditor) {
				console.error('GroupLinkSupport: WordPress editor APIs not available');
				return;
			}

			const { addFilter } = wp.hooks;
			const { createHigherOrderComponent } = wp.compose;
			const { Fragment } = wp.element;
			const { InspectorControls } = wp.blockEditor;
			const { PanelBody, TextControl } = wp.components;
			const { __ } = wp.i18n;

			// Add link URL control to Group block settings
			const withLinkControl = createHigherOrderComponent((BlockEdit) => {
				return (props) => {
					if (props.name !== 'core/group') {
						return wp.element.createElement(BlockEdit, props);
					}

					const { attributes, setAttributes } = props;
					const linkUrl = attributes.metadata?.linkUrl || '';

					return wp.element.createElement(
						Fragment,
						null,
						wp.element.createElement(BlockEdit, props),
						wp.element.createElement(
							InspectorControls,
							null,
							wp.element.createElement(
								PanelBody,
								{
									title: __('Link Settings', 'church'),
									initialOpen: false
								},
								wp.element.createElement(TextControl, {
									label: __('Link URL', 'church'),
									value: linkUrl,
									onChange: (value) => {
										setAttributes({
											metadata: {
												...attributes.metadata,
												linkUrl: value
											}
										});
									},
									help: __('Make this entire group clickable. Enter a URL (e.g., /about or https://example.com)', 'church')
								})
							)
						)
					);
				};
			}, 'withLinkControl');

			addFilter(
				'editor.BlockEdit',
				'church/group-link-control',
				withLinkControl
			);

		})(window.wp);
		";

		wp_add_inline_script( 'wp-block-editor', $script, 'after' );
	}

	/**
	 * Render Group block with clickable link functionality.
	 *
	 * Wraps the inner content of Group blocks in an <a> tag when linkUrl is set.
	 *
	 * @param string               $block_content Rendered block content.
	 * @param array<string, mixed> $block Block data.
	 * @return string Modified block content.
	 */
	public function render_group_as_link( string $block_content, array $block ): string {
		// Check if linkUrl is set in metadata.
		if ( ! isset( $block['attrs']['metadata']['linkUrl'] ) || empty( $block['attrs']['metadata']['linkUrl'] ) ) {
			return $block_content;
		}

		$url = esc_url( $block['attrs']['metadata']['linkUrl'] );

		// Use DOMDocument to parse and modify HTML.
		$dom = new \DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $block_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		// Find the outer container (article/div with wp-block-group class).
		$xpath     = new \DOMXPath( $dom );
		$query     = $xpath->query( '//*[contains(@class, "wp-block-group")]' );
		$container = false !== $query ? $query->item( 0 ) : null;

		if ( ! $container ) {
			return $block_content;
		}

		// Create <a> wrapper.
		$link = $dom->createElement( 'a' );
		$link->setAttribute( 'href', $url );
		$link->setAttribute( 'class', 'wp-block-group__link' );

		// Move all children into the link.
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOMNode property.
		while ( $container->firstChild ) {
			$link->appendChild( $container->firstChild ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		// Add link back to container.
		$container->appendChild( $link );

		$result = $dom->saveHTML();

		if ( false === $result ) {
			return $block_content;
		}

		// Remove the XML encoding declaration added for UTF-8 support.
		$result = str_replace( '<?xml encoding="UTF-8">', '', $result );

		return wp_kses_post( $result );
	}
}
