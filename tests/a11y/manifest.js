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

	// Each family is asserted non-empty on its own. A seed that failed partway through — say
	// the pattern loop threw after the routes were written — would otherwise show up as a
	// smaller green run rather than as a failure.
	[ 'routes', 'blocks', 'patterns', 'variants' ].forEach( ( family ) => {
		if (
			! Array.isArray( manifest[ family ] ) ||
			! manifest[ family ].length
		) {
			throw new Error(
				`The seed manifest has no "${ family }" entries. Re-run the seed and check its output.`
			);
		}
	} );

	return manifest;
}

module.exports = { loadManifest, MANIFEST };
