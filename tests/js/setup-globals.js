/**
 * Web globals jsdom does not implement, which the editor packages expect.
 *
 * jsdom deliberately ships a partial DOM. None of these gaps are the theme's code — they
 * surface while `@wordpress/block-library` is still being imported — but a missing global
 * throws at module scope, so it takes the whole suite down before a single test runs.
 *
 * Add to this only when a real failure names the missing global. A speculative polyfill
 * hides the fact that a package started depending on something new.
 */

/* eslint-env node */

const { TextDecoder, TextEncoder } = require( 'util' );

// Reached through @wordpress/sync (core-data → block-library).
if ( typeof global.TextEncoder === 'undefined' ) {
	global.TextEncoder = TextEncoder;
}

if ( typeof global.TextDecoder === 'undefined' ) {
	global.TextDecoder = TextDecoder;
}
