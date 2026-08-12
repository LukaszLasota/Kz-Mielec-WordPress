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
 * "Dane kontaktowe" screen.
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
} )( window.wp );
