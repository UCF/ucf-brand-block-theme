/**
 * Accessibility: one audit per template.
 *
 * Front page, a numbered section page, a single post, the blog index, search with and
 * without results, and 404 — the six templates in `templates/`, plus the empty-results case,
 * which renders a different tree from a populated one and is the version nobody looks at.
 *
 * These are the pages that carry the theme's chrome: the header bar, the drawer and its
 * derived sub-nav, the mobile bar, the search form and the footer. Everything the pattern and
 * variant tiers audit sits inside that chrome too, so a problem here shows up everywhere.
 */
const { test } = require( '@playwright/test' );
const { auditPage } = require( './axe' );
const { loadManifest } = require( './manifest' );

const { routes } = loadManifest();

for ( const route of routes ) {
	test( `route: ${ route.name }`, async ( { page }, testInfo ) => {
		await auditPage( page, testInfo, route );
	} );
}
