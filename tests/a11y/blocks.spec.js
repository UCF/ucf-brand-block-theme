/**
 * Accessibility: custom blocks that no pattern or template already puts on a page.
 *
 * Only `ucf-brand/tabs` today. `page-hero` ships inside `templates/page.html` and
 * `color-swatches` inside a pattern, so both are audited by the tiers above; `seed.php`
 * fails the seed outright if a top-level block ends up covered by neither.
 *
 * Tabs earns its own file. Its saved markup is a role-free stack of label/panel pairs, and
 * `src/blocks/tabs/view.js` is the only thing that turns that into a tab widget — at runtime,
 * and only above 768px. Below that the stack *is* the layout. So the desktop and tablet
 * projects audit a real `tablist`, the mobile project audits the fallback, and the guard
 * below makes sure each one audited the tree it was supposed to.
 */
const { test, expect } = require( '@playwright/test' );
const { auditPage } = require( './axe' );
const { loadManifest } = require( './manifest' );

const { blocks } = loadManifest();

/** Mirrors TABS_QUERY in src/blocks/tabs/view.js and $breakpoint-tabs in _variables.scss. */
const TABS_BREAKPOINT = 768;

for ( const block of blocks ) {
	test( `block: ${ block.name }`, async ( { page }, testInfo ) => {
		await auditPage( page, testInfo, block );

		if ( 'ucf-brand/tabs' !== block.name ) {
			return;
		}

		/*
		 * Without this the tabs audit is the weakest kind of green: view.js failing to load
		 * leaves a plain stack, which is valid markup and passes axe at every viewport. The
		 * assertion is that the enhancement actually happened where it should have — the same
		 * reason blocks.test.js checks the registry is not empty.
		 */
		const width = testInfo.project.use.viewport.width;
		const tablists = page.getByRole( 'tablist' );

		if ( width >= TABS_BREAKPOINT ) {
			await expect(
				tablists,
				'Above the tabs breakpoint the block should have built a tablist. It did not, ' +
					'so axe just audited the unenhanced stack and passed for the wrong reason.'
			).toHaveCount( 1 );
		} else {
			await expect(
				tablists,
				'Below the tabs breakpoint the block should stay a plain stack of headings ' +
					'and panels, with no tab roles at all.'
			).toHaveCount( 0 );
		}
	} );
}
