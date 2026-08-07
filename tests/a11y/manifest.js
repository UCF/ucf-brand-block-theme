/**
 * The list of pages to audit, as written by `tests/a11y/seed.php`.
 *
 * The manifest is generated rather than checked in, and that is the whole point of it: the
 * pattern and variant tiers are read out of WordPress's own registries at seed time, so a
 * pattern added to `patterns/` or a style added to `includes/block-styles.php` turns into a
 * test on the next seed with nothing here to update.
 *
 * A missing manifest is a hard error, not an empty run. Playwright reports "0 tests" as a
 * pass, so a forgotten seed would look exactly like a site with nothing wrong with it.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const MANIFEST = path.join( __dirname, 'seeded.json' );

/**
 * Read the manifest, or explain how to produce one.
 *
 * @return {{routes: Array, blocks: Array, patterns: Array, variants: Array}} Seeded targets.
 */
function loadManifest() {
	if ( ! fs.existsSync( MANIFEST ) ) {
		throw new Error(
			`No seeded content found at ${ MANIFEST }.\n` +
				'Run `npm run test:a11y`, which seeds and then runs, or `npm run env:seed` ' +
				'against an already-running environment.'
		);
	}

	const manifest = JSON.parse( fs.readFileSync( MANIFEST, 'utf8' ) );

	/*
	 * Every family must be present, so a seed that died partway through — say the pattern loop
	 * threw after the routes were written — fails here instead of showing up as a smaller
	 * green run.
	 *
	 * Only three of them must also be non-empty. `blocks` covers custom blocks that no pattern
	 * or template already renders, so it legitimately empties out the moment the last one gets
	 * used somewhere: today it holds `tabs` alone, and a pattern using tabs would correctly
	 * reduce it to nothing. Requiring a row there would turn better coverage into a failure.
	 * Nothing is lost by allowing it — `ucf_brand_a11y_assert_blocks_covered()` in the seeder
	 * is what actually guarantees no block goes unaudited, and it runs regardless.
	 */
	const families = {
		routes: true,
		blocks: false,
		patterns: true,
		variants: true,
	};

	Object.entries( families ).forEach( ( [ family, required ] ) => {
		if ( ! Array.isArray( manifest[ family ] ) ) {
			throw new Error(
				`The seed manifest has no "${ family }" list at all. Re-run the seed and check its output.`
			);
		}

		if ( required && ! manifest[ family ].length ) {
			throw new Error(
				`The seed manifest has no "${ family }" entries. Re-run the seed and check its output.`
			);
		}
	} );

	return manifest;
}

module.exports = { loadManifest, MANIFEST };
