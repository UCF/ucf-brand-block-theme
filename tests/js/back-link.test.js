/**
 * The 404 template's "Go back" button, driven by src/js/back-link.js.
 *
 * The script runs on load, so each case builds the DOM and browser state first and then
 * loads a fresh copy of it.
 */

// SYNC: the button markup in templates/404.html.
const BUTTON = `
	<div class="wp-block-button is-style-outline brand-back-link">
		<a class="wp-block-button__link wp-element-button">Go back</a>
	</div>`;

const REFERRER = 'https://www.ucf.edu/brand/logos/';

/**
 * Load back-link.js against the current document, as the page would.
 *
 * @param {Object} state            Browser state the script reads.
 * @param {string} state.referrer   Value of document.referrer.
 * @param {number} state.historyLen Value of window.history.length.
 */
function run( { referrer, historyLen } ) {
	jest.spyOn( document, 'referrer', 'get' ).mockReturnValue( referrer );
	jest.spyOn( window.history, 'length', 'get' ).mockReturnValue( historyLen );

	jest.isolateModules( () => {
		require( '../../src/js/back-link' );
	} );
}

const wrapper = () => document.querySelector( '.brand-back-link' );
const link = () => wrapper().querySelector( 'a' );

describe( 'back-link', () => {
	let back;

	beforeEach( () => {
		document.body.innerHTML = BUTTON;
		back = jest
			.spyOn( window.history, 'back' )
			.mockImplementation( () => {} );
	} );

	afterEach( () => {
		jest.restoreAllMocks();
		document.body.innerHTML = '';
	} );

	it( 'stays hidden with no href when there is no referrer', () => {
		run( { referrer: '', historyLen: 3 } );

		expect( wrapper().classList.contains( 'is-ready' ) ).toBe( false );
		expect( link().hasAttribute( 'href' ) ).toBe( false );

		link().click();
		expect( back ).not.toHaveBeenCalled();
	} );

	// WHY: a link opened in a new tab carries a referrer but has no entry to go back to.
	it( 'stays hidden when there is a referrer but no history entry', () => {
		run( { referrer: REFERRER, historyLen: 1 } );

		expect( wrapper().classList.contains( 'is-ready' ) ).toBe( false );
		expect( link().hasAttribute( 'href' ) ).toBe( false );
	} );

	it( 'sets the href, reveals the button and goes back on click', () => {
		run( { referrer: REFERRER, historyLen: 2 } );

		expect( link().getAttribute( 'href' ) ).toBe( REFERRER );
		expect( wrapper().classList.contains( 'is-ready' ) ).toBe( true );

		const click = new window.MouseEvent( 'click', {
			bubbles: true,
			cancelable: true,
		} );
		link().dispatchEvent( click );

		expect( back ).toHaveBeenCalledTimes( 1 );
		expect( click.defaultPrevented ).toBe( true );
	} );

	it( 'does nothing when the 404 template has no back button', () => {
		document.body.innerHTML = '';

		expect( () =>
			run( { referrer: REFERRER, historyLen: 2 } )
		).not.toThrow();
	} );
} );
