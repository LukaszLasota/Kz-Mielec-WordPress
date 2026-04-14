/**
 * Scroll Arrow — smooth scroll on click.
 *
 * Loaded only on the frontend via viewScript when the block is on the page.
 */
document.querySelectorAll( '.scroll-arrow' ).forEach( ( arrow ) => {
	arrow.addEventListener( 'click', ( e ) => {
		const href = arrow.getAttribute( 'href' );
		if ( ! href ) {
			return;
		}

		const target = document.querySelector( href );
		if ( target ) {
			e.preventDefault();
			target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}
	} );
} );
