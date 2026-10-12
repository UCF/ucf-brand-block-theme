/**
 * The home hero's background video, driven by src/js/hero-video.js.
 *
 * The script runs on load, so each case builds the DOM and browser state first and then
 * loads a fresh copy of it. jsdom implements neither media playback nor matchMedia, so
 * both are stubbed — the width query with a `change` event, to cover resizing.
 */

const SRC = 'https://example.test/loop.mp4';

// SYNC: what includes/home-hero.php leaves of core's Cover video — no src, no autoplay.
const BAND = `
	<div class="wp-block-cover alignfull is-style-dark brand-home-hero__band">
		<video class="wp-block-cover__video-background intrinsic-ignore" muted loop playsinline data-brand-src="${ SRC }" preload="none"></video>
		<span aria-hidden="true" class="wp-block-cover__background"></span>
		<div class="wp-block-cover__inner-container"><h1>Unmistakably UCF</h1></div>
	</div>`;

let widthQuery;

/**
 * Load hero-video.js against the current document, as the page would.
 *
 * @param {Object}  state              Browser state the script reads.
 * @param {boolean} state.wide         Whether the viewport is at or above the breakpoint.
 * @param {boolean} state.reduceMotion Whether prefers-reduced-motion matches.
 */
function run( { wide = true, reduceMotion = false } = {} ) {
	const listeners = [];
	widthQuery = {
		matches: wide,
		addEventListener: ( type, fn ) => listeners.push( fn ),
		resize( toWide ) {
			this.matches = toWide;
			listeners.forEach( ( fn ) => fn() );
		},
	};

	window.matchMedia = jest.fn( ( query ) =>
		query.includes( 'min-width' )
			? widthQuery
			: { matches: reduceMotion, addEventListener() {} }
	);

	jest.isolateModules( () => {
		require( '../../src/js/hero-video' );
	} );
}

const band = () => document.querySelector( '.brand-home-hero__band' );
const video = () => band().querySelector( 'video' );
const button = () => document.querySelector( '.brand-home-hero__toggle' );

describe( 'hero-video', () => {
	let pause;
	let play;

	beforeEach( () => {
		document.body.innerHTML = BAND;
		pause = jest
			.spyOn( window.HTMLMediaElement.prototype, 'pause' )
			.mockImplementation( () => {} );
		play = jest
			.spyOn( window.HTMLMediaElement.prototype, 'play' )
			.mockResolvedValue( undefined );
	} );

	afterEach( () => {
		jest.restoreAllMocks();
		delete window.matchMedia;
		document.body.innerHTML = '';
	} );

	it( 'loads and plays the video on a wide screen', () => {
		run();

		expect( video().getAttribute( 'src' ) ).toBe( SRC );
		expect( play ).toHaveBeenCalledTimes( 1 );
		expect( button().parentElement ).toBe( band() );
		expect( button().getAttribute( 'type' ) ).toBe( 'button' );
		expect( button().getAttribute( 'aria-label' ) ).toBe(
			'Pause background video'
		);
	} );

	it( 'never loads the video on a narrow screen', () => {
		run( { wide: false } );

		expect( video().hasAttribute( 'src' ) ).toBe( false );
		expect( play ).not.toHaveBeenCalled();
	} );

	it( 'pauses on the first click and plays on the second', () => {
		run();
		play.mockClear();

		button().click();

		expect( pause ).toHaveBeenCalledTimes( 1 );
		expect( band().classList.contains( 'is-video-paused' ) ).toBe( true );
		expect( button().getAttribute( 'aria-label' ) ).toBe(
			'Play background video'
		);

		button().click();

		expect( play ).toHaveBeenCalledTimes( 1 );
		expect( band().classList.contains( 'is-video-paused' ) ).toBe( false );
		expect( button().getAttribute( 'aria-label' ) ).toBe(
			'Pause background video'
		);
	} );

	it( 'loads and plays once a narrow window is widened', () => {
		run( { wide: false } );

		widthQuery.resize( true );

		expect( video().getAttribute( 'src' ) ).toBe( SRC );
		expect( play ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'pauses when a wide window is narrowed', () => {
		run();
		pause.mockClear();

		widthQuery.resize( false );

		expect( pause ).toHaveBeenCalledTimes( 1 );
		// WHY: the visitor did not pause it, so it resumes when widened again.
		expect( band().classList.contains( 'is-video-paused' ) ).toBe( false );
	} );

	it( 'stays paused across a resize when the visitor paused it', () => {
		run();
		button().click();
		play.mockClear();

		widthQuery.resize( false );
		widthQuery.resize( true );

		expect( play ).not.toHaveBeenCalled();
	} );

	it( 'shows the first frame but does not play when reduced motion is requested', () => {
		run( { reduceMotion: true } );

		expect( video().getAttribute( 'src' ) ).toBe( SRC );
		expect( play ).not.toHaveBeenCalled();
		expect( band().classList.contains( 'is-video-paused' ) ).toBe( true );
		expect( button().getAttribute( 'aria-label' ) ).toBe(
			'Play background video'
		);
	} );

	// WHY: the video is set per site in the Site Editor, so the band can ship without one.
	it( 'adds nothing when the band has no video', () => {
		video().remove();
		run();

		expect( button() ).toBeNull();
	} );

	it( 'does nothing on a page with no home hero', () => {
		document.body.innerHTML = '';

		expect( () => run() ).not.toThrow();
		expect( button() ).toBeNull();
	} );
} );
