/**
 * Accessibility: one audit per registered block style.
 *
 * This is the tier the theme actually needs, and the one a per-route suite cannot stand in
 * for. The compositions — `is-style-dark`, `is-style-paper`, `is-style-light`,
 * `is-style-bold-gold` and their `-accent` twins — set the `--brand-*` roles their contents
 * read, so the same `is-style-muted` paragraph resolves to a different color in each. Grey on
 * paper passes; the same grey on the dark field may not. **A variant can fail contrast where
 * the default passes**, which means auditing the default composition proves nothing about the
 * other eleven.
 *
 * So every `core/group` variant page carries the full probe from `seed.php` — a heading, body
 * copy, a link, all four text styles, a rule, a list and a button — and axe measures each of
 * them against whatever that composition resolved to.
 *
 * Every style in `WP_Block_Styles_Registry` gets a page. Core registers its own styles on the
 * client, so the server registry is exactly this theme's — nothing to filter, and a style
 * added to `includes/block-styles.php` is covered on the next seed.
 */
const { test } = require( '@playwright/test' );
const { auditPage } = require( './axe' );
const { loadManifest } = require( './manifest' );

const { variants } = loadManifest();

for ( const variant of variants ) {
	test( `variant: ${ variant.name }`, async ( { page }, testInfo ) => {
		// `variant.class` is asserted in the DOM before axe runs — see tests/a11y/axe.js.
		// Without it, a stale sample renders in default colors and passes as the variant.
		await auditPage( page, testInfo, variant );
	} );
}
