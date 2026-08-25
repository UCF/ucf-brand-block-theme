/**
 * Accessibility: the home hero, and the two layouts it swaps between.
 *
 * templates/front-page.html builds the band from core Cover and puts
 * `core/post-featured-image` above it. _home-hero.scss shows one and hides the other at
 * 768px: below it the image is the hero with the copy stacked underneath, at and above it
 * the band takes over and the image is gone.
 *
 * The band ships with no background media — the video is set per site in the Site Editor, so
 * it is content rather than something the theme can assert on. What the theme owns is the
 * swap, and that is what this covers: a hero stuck in one layout is valid markup and passes
 * axe at every viewport, so each project checks which of the two it actually got before the
 * audit means anything. Same guard as the tabs test in blocks.spec.js.
 */
const { test, expect } = require( '@playwright/test' );
const { auditPage } = require( './axe' );
const { loadManifest } = require( './manifest' );

const { routes } = loadManifest();

/** SYNC: $breakpoint-video in src/scss/_variables.scss. */
const VIDEO_BREAKPOINT = 768;

const home = routes.find( ( route ) => 'front-page' === route.name );

test( 'home hero: band above the breakpoint, image below', async ( {
	page,
}, testInfo ) => {
	expect(
		home,
		'Expected an a11y route named "front-page" in the manifest.'
	).toBeTruthy();
	await auditPage( page, testInfo, { ...home, name: 'home-hero' } );
	// The hero owns the page's h1. Content that opens with its own heading has to use h2,
	// and nothing in axe catches the duplicate — `page-has-heading-one` only checks that at
	// least one exists.
	await expect(
		page.locator( 'h1' ),
		'The home page should have exactly one h1, the hero title.'
	).toHaveCount( 1 );

	await expect(
		page.locator( '.brand-home-hero__band' ),
		'The front page should render the hero band at every viewport. Missing means the ' +
			'template changed and both branches below would pass against a page with no ' +
			'hero at all.'
	).toBeVisible();

	const image = page.locator( '.brand-home-hero__image img' );
	const width = testInfo.project.use.viewport.width;

	if ( width >= VIDEO_BREAKPOINT ) {
		await expect(
			image,
			'Above the breakpoint the band is the hero on its own, so the featured image ' +
				'must be hidden — otherwise both stack and the band is pushed down the page.'
		).toBeHidden();
	} else {
		await expect(
			image,
			'Below the breakpoint the featured image is the hero. It is not visible, so ' +
				'either the fixture lost its thumbnail or the media query stopped matching ' +
				'— on a real page with no featured image set, this is a bare band.'
		).toBeVisible();
	}
} );
