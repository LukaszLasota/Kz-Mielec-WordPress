/**
 * Shows the same contact lines in the editor as on the front end.
 *
 * A binding source registered only in PHP renders correctly on the front end but leaves
 * the editor displaying the paragraph's fallback text, so an administrator editing the
 * page would see stale data and reasonably try to "fix" it by hand — which is exactly the
 * habit this whole feature exists to end. Registering the same source name here, with the
 * values PHP already resolved for the language of the edited post, makes the editor agree
 * with the page.
 *
 * The values are read-only on purpose: they are edited in one place, on the theme's
 * "Dane kontaktowe" screen - which the bound blocks now point to (see the end of file).
 */
( function ( wp ) {
	if ( ! wp || ! wp.blocks || ! wp.blocks.registerBlockBindingsSource ) {
		return;
	}

	var values = window.kzmielecContact || {};
	var label = wp.i18n
		? wp.i18n.__( 'Dane kontaktowe zboru', 'kzmielec' )
		: 'Dane kontaktowe zboru';

	wp.blocks.registerBlockBindingsSource( {
		name: 'kzmielec/contact',
		label: label,
		getValues: function ( params ) {
			var result = {};
			var bindings = ( params && params.bindings ) || {};

			Object.keys( bindings ).forEach( function ( attribute ) {
				var args = bindings[ attribute ].args || {};
				var key = args.key || '';

				// An unknown key is left undefined so the editor falls back to the text
				// stored in the block, which is what PHP does on the front end.
				if ( Object.prototype.hasOwnProperty.call( values, key ) ) {
					result[ attribute ] = values[ key ];
				}
			} );

			return result;
		},
		canUserEditValue: function () {
			return false;
		},
	} );

	/*
	 * Say where the text is edited. A bound paragraph looks like any other, and a
	 * read-only one that ignores typing reads as a broken editor. So every block
	 * bound to this source gets a dashed outline in the canvas (styles in
	 * ContactBindings::canvas_styles()) and a sidebar panel with a button to the
	 * settings screen.
	 */
	var hooks = wp.hooks;
	var compose = wp.compose;
	var el = wp.element && wp.element.createElement;
	var blockEditor = wp.blockEditor;
	var components = wp.components;
	var meta = window.kzmielecContactMeta || {};

	if ( ! hooks || ! compose || ! el || ! blockEditor || ! components ) {
		return;
	}

	var isBound = function ( attributes ) {
		var bindings = attributes && attributes.metadata && attributes.metadata.bindings;
		if ( ! bindings ) {
			return false;
		}
		return Object.keys( bindings ).some( function ( attribute ) {
			return bindings[ attribute ] && bindings[ attribute ].source === 'kzmielec/contact';
		} );
	};

	var __ = wp.i18n ? wp.i18n.__ : function ( text ) {
		return text;
	};

	hooks.addFilter(
		'editor.BlockEdit',
		'kzmielec/contact-bindings/panel',
		compose.createHigherOrderComponent( function ( BlockEdit ) {
			return function ( props ) {
				if ( ! isBound( props.attributes ) ) {
					return el( BlockEdit, props );
				}

				return el(
					wp.element.Fragment,
					null,
					el( BlockEdit, props ),
					el(
						blockEditor.InspectorControls,
						null,
						el(
							components.PanelBody,
							{ title: __( 'Dane kontaktowe', 'kzmielec' ), initialOpen: true },
							el(
								'p',
								null,
								__( 'Ten tekst pochodzi z ekranu „Dane kontaktowe” i jest taki sam we wszystkich wersjach językowych. Tutaj nie da się go zmienić.', 'kzmielec' )
							),
							meta.settingsUrl
								? el(
									components.Button,
									{ variant: 'secondary', href: meta.settingsUrl, target: '_blank' },
									__( 'Edytuj dane kontaktowe', 'kzmielec' )
								)
								: null
						)
					)
				);
			};
		}, 'withContactBindingPanel' )
	);

	hooks.addFilter(
		'editor.BlockListBlock',
		'kzmielec/contact-bindings/outline',
		compose.createHigherOrderComponent( function ( BlockListBlock ) {
			return function ( props ) {
				if ( ! isBound( props.attributes ) ) {
					return el( BlockListBlock, props );
				}
				var className = ( props.className ? props.className + ' ' : '' ) + 'kzmielec-bound-contact';
				return el( BlockListBlock, Object.assign( {}, props, { className: className } ) );
			};
		}, 'withContactBindingOutline' )
	);
} )( window.wp );
