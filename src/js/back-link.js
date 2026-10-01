/**
 * "Go back" button on the 404 template.
 *
 * CONTEXT: the button ships with no href and is hidden by `_not-found.scss` until this
 * script marks it ready, so a visitor who arrived with nowhere to go back to never sees it.
 */
( function () {
	'use strict';

	const wrapper = document.querySelector( '.brand-back-link' );
	const link = wrapper && wrapper.querySelector( 'a' );

	// WHY: a typed or bookmarked URL has no referrer, and history.back() would then leave
	// the site or do nothing at all.
	if ( ! link || ! document.referrer || window.history.length < 2 ) {
		return;
	}

	// WHY: a real href keeps it focusable and middle-clickable; the click handler prefers
	// history so the previous page comes back at its scroll position.
	link.href = document.referrer;
	link.addEventListener( 'click', ( event ) => {
		event.preventDefault();
		window.history.back();
	} );

	wrapper.classList.add( 'is-ready' );
} )();
