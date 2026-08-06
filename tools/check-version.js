/**
 * Verify that the theme's two version numbers agree.
 *
 * WordPress reads the `Version:` header in style.css; npm reads package.json. Nothing
 * links them, so a release that bumps one and forgets the other ships a theme whose
 * reported version is wrong — and because the header is what the update machinery and the
 * Appearance screen both read, style.css is the one that matters.
 *
 * Run with `npm run lint:version`.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const root = path.join( __dirname, '..' );
const pkg = require( path.join( root, 'package.json' ) );
const style = fs.readFileSync( path.join( root, 'style.css' ), 'utf8' );

const match = style.match( /^Version:\s*(.+)$/m );

if ( ! match ) {
	console.error( 'style.css has no `Version:` header.' );
	process.exit( 1 );
}

const header = match[ 1 ].trim();

if ( header !== pkg.version ) {
	console.error(
		`Version mismatch:\n  style.css    ${ header }\n  package.json ${ pkg.version }\n\n` +
			'Bump both, then rerun. style.css is the one WordPress reads.'
	);
	process.exit( 1 );
}

console.log( `Versions match: ${ header }` );
