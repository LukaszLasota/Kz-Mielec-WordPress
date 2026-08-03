/**
 * Scroll Arrow — smooth scroll on click.
 *
 * Loaded only on the frontend via viewScript when the block is on the page.
 */

/**
 * Where an element belongs in the document, ignoring any sticky offset.
 *
 * A pinned `position: sticky` element reports where it is being PAINTED, not
 * where it sits in flow — and that applies to `offsetTop` just as much as to
 * `getBoundingClientRect()`. Measured on the site header while pinned: both said
 * 7041px, which was simply the current scroll position. The back-to-top arrow
 * targets that header, so `scrollIntoView()` concluded it was already in view
 * and nudged the page by a few pixels instead of returning to the top.
 *
 * Neutralising the stickiness for the single measurement is what gives the real
 * position. It costs two forced reflows per click, which is nothing next to the
 * animation that follows.
 *
 * @param {HTMLElement} element Target element.
 * @return {number} Offset from the document top, in pixels.
 */
const documentTop = ( element ) => {
	if ( window.getComputedStyle( element ).position !== 'sticky' ) {
		return element.getBoundingClientRect().top + window.scrollY;
	}

	const previous = element.style.position;
	element.style.position = 'static';
	const top = element.getBoundingClientRect().top + window.scrollY;
	element.style.position = previous;

	return top;
};

document.querySelectorAll( '.scroll-arrow' ).forEach( ( arrow ) => {
	arrow.addEventListener( 'click', ( e ) => {
		const href = arrow.getAttribute( 'href' );
		if ( ! href ) {
			return;
		}

		// `getElementById`, not `querySelector`: an id may legally start with a
		// digit while `#2` is not a valid CSS selector, so `querySelector` throws
		// a DOMException. One arrow on the front page points at `#2`, and because
		// the throw happened before `preventDefault()` that arrow silently lost
		// its smooth scroll and its clearance from the sticky menu.
		const target = href.startsWith( '#' )
			? document.getElementById( href.slice( 1 ) )
			: null;

		if ( ! target ) {
			return;
		}

		e.preventDefault();

		// `scrollTo` ignores `scroll-margin-top`, which is what keeps a target
		// clear of the sticky menu, so apply the element's own value by hand and
		// keep that contract in CSS where the rest of the theme states it.
		const margin =
			parseFloat( window.getComputedStyle( target ).scrollMarginTop ) || 0;

		window.scrollTo( {
			top: Math.max( 0, documentTop( target ) - margin ),
			behavior: 'smooth',
		} );
	} );
} );
