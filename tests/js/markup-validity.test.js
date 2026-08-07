/**
 * Every block in every template part and pattern must parse as valid.
 *
 * `docs/architecture.md` states the rule this enforces: "Pattern PHP must serialize exactly
 * what `save()` would produce or the editor flags the block invalid," and records that
 * `section-index.php` shipped a violation — an `is-style-meta` paragraph that also carried a
 * `textColor` attribute, which looked correct on white and stayed grey-on-black inside a Dark
 * group. Nothing caught it, because **invalid blocks still render on the front end**. A page
 * render is not a check; this is.
 *
 * Two sources, read differently:
 *
 * - `parts/*.html` is literal markup and is read straight off disk.
 * - `patterns/**\/*.php` interpolates PHP *inside* its block markup, so the file on disk is
 *   not what WordPress registers. `tests/php/render-patterns.php` renders each one the way
 *   core does and hands back the result — see that file for why it shells out to PHP.
 *
 * The sweep is deliberately data-driven off what is on disk, so a pattern added tomorrow is
 * covered without anyone remembering to add it here.
 */

import { execFileSync } from 'child_process';
import { readdirSync, readFileSync } from 'fs';
import { join } from 'path';

import { isValidBlockContent, parse } from '@wordpress/blocks';
import { registerCoreBlocks } from '@wordpress/block-library';

import {
	registerDynamicBlocks,
	registerThemeBlocks,
} from './helpers/register-blocks';

const THEME_DIR = join( __dirname, '../..' );

/**
 * Render every pattern through PHP.
 *
 * This is the one place the JS suite needs a PHP binary. That is a fair trade for a
 * WordPress theme — CI has both — but it is the reason this file is separate from
 * blocks.test.js, which needs only Node.
 *
 * @return {Array<{file: string, content: string}>} Rendered patterns.
 */
function renderPatterns() {
	const json = execFileSync(
		'php',
		[ join( THEME_DIR, 'tests/php/render-patterns.php' ) ],
		{ encoding: 'utf8', maxBuffer: 20 * 1024 * 1024 }
	);

	return JSON.parse( json );
}

/**
 * Read the template parts off disk.
 *
 * @return {Array<{file: string, content: string}>} Template parts.
 */
function readParts() {
	const dir = join( THEME_DIR, 'parts' );

	return readdirSync( dir )
		.filter( ( name ) => name.endsWith( '.html' ) )
		.sort()
		.map( ( name ) => ( {
			file: `parts/${ name }`,
			content: readFileSync( join( dir, name ), 'utf8' ),
		} ) );
}

/**
 * Flatten a parsed tree into every block it contains.
 *
 * @param {Array} blocks Parsed blocks.
 * @param {Array} acc    Accumulator.
 * @return {Array} Every block, nested ones included.
 */
function flatten( blocks, acc = [] ) {
	blocks.forEach( ( block ) => {
		acc.push( block );

		if ( block.innerBlocks && block.innerBlocks.length ) {
			flatten( block.innerBlocks, acc );
		}
	} );

	return acc;
}

/**
 * Describe why a block failed, precisely enough to fix it without re-running by hand.
 *
 * @param {Object} block Parsed block.
 * @return {string} Human-readable failure line.
 */
function describe_( block ) {
	const name = block.name || '(unrecognized)';
	const snippet = ( block.originalContent || '' )
		.replace( /\s+/g, ' ' )
		.trim()
		.slice( 0, 160 );

	return `${ name }: ${ snippet }`;
}

/**
 * Blocks in a source whose markup is not what the current `save()` produces.
 *
 * **Why this is not `block.isValid`.** `parse()` does not simply validate — when markup
 * fails to match, it walks the block type's `deprecated` array looking for an older `save()`
 * that *does* match, and if it finds one it migrates the block and reports `isValid: true`.
 * Core blocks carry a lot of deprecations (`core/heading` has six), so asserting on
 * `isValid` passes on almost anything.
 *
 * That was measured, not assumed. Reproducing the exact bug `docs/architecture.md` records
 * against `section-index.php` — a paragraph carrying a `textColor` attribute with no
 * matching `has-*-color` class on the element — leaves every block `isValid: true`. Core
 * quietly recovers it and logs "Updated Block: core/paragraph". An `isValid` sweep would
 * have shipped that bug a second time.
 *
 * `isValidBlockContent()` compares the source markup against what today's `save()` emits
 * with no deprecation fallback, which is the property a pattern actually needs to hold: what
 * is on disk is what the block would write. A block that only survives via migration is a
 * stale pattern, and in the `textColor` case a block that has opted out of the composition
 * system — the thing that made it a bug rather than a formatting nit.
 *
 * @param {{file: string, content: string}} source Rendered source.
 * @return {string[]} Failure lines, empty when the source is clean.
 */
function staleBlocksIn( source ) {
	return (
		flatten( parse( source.content ) )
			.filter( ( block ) => block.name && block.name !== 'core/missing' )
			/*
			 * `core/html` is exempt, and this is not a workaround for a real failure.
			 *
			 * Its entire purpose is to carry verbatim raw markup: `save()` hands the stored
			 * content straight back through `RawHTML`, so there is no normalization contract to
			 * hold it to the way there is for a block that rebuilds its own markup from
			 * attributes. `isValidBlockContent()` still round-trips it through the comparison
			 * and reports a mismatch on incidentals — entities like `&times;` and collapsed
			 * whitespace.
			 *
			 * Verified against the real thing rather than assumed: loading WordPress 7.0.2's own
			 * bundles in a browser and parsing `parts/brand-sidebar.html` reports
			 * `core/html isValid=true`. The editor is happy with both of these.
			 *
			 * The patterns suite separately asserts that no pattern uses `core/html` at all, so
			 * this exemption only ever applies to the two chrome blocks in parts/ that
			 * docs/architecture.md explicitly allows.
			 */
			.filter( ( block ) => block.name !== 'core/html' )
			.filter(
				( block ) =>
					! isValidBlockContent(
						block.name,
						block.attributes,
						block.originalContent || ''
					)
			)
			.map( ( block ) => `${ source.file } → ${ describe_( block ) }` )
	);
}

/**
 * Blocks in a source whose name does not resolve to a registered type.
 *
 * A typo in a block comment, or a block removed while something still references it.
 *
 * Each entry names the offending block, not just the file. Reporting the filename alone
 * meant a source with three unrecognized blocks produced the same string three times — a
 * longer failure that told you nothing more than a shorter one would have. `core/missing`
 * keeps the original markup on the block, so say which one.
 *
 * @param {{file: string, content: string}} source Rendered source.
 * @return {string[]} Failure lines.
 */
function unrecognizedBlocksIn( source ) {
	return flatten( parse( source.content ) )
		.filter( ( block ) => block.name === 'core/missing' )
		.map( ( block ) => `${ source.file } → ${ describe_( block ) }` );
}

const SILENCED = [
	'info',
	'warn',
	'error',
	'log',
	'groupCollapsed',
	'groupEnd',
];
const originalConsole = {};

let PARTS = [];
let PATTERNS = [];

beforeAll( () => {
	/*
	 * Gutenberg logs a full validation diff through console.info for every block it finds
	 * invalid or migrates through a deprecation, and @wordpress/jest-console turns
	 * unexpected console output into a failure of its own. That failure reads
	 * "expect(jest.fn()).not.toHaveInformed(expected)" followed by the entire serialized
	 * block type — hundreds of lines — which completely buries the one thing this file
	 * exists to tell you: which file and which block broke.
	 *
	 * Note these are plain functions, NOT jest.spyOn(). jest-console asserts against
	 * whatever `.mock.calls` it finds on the console method, so a spy — even one with a
	 * no-op implementation — still records the calls and still fails the test. Replacing
	 * the method with an ordinary function leaves nothing for it to find.
	 *
	 * The assertions below report file, block name and the offending markup themselves.
	 */
	/* eslint-disable no-console -- Silencing the console is the point of this block. */
	SILENCED.forEach( ( method ) => {
		originalConsole[ method ] = console[ method ];
		console[ method ] = () => {};
	} );
	/* eslint-enable no-console */

	registerCoreBlocks();
	registerThemeBlocks();
	registerDynamicBlocks();

	PARTS = readParts();
	PATTERNS = renderPatterns();
} );

afterAll( () => {
	/* eslint-disable no-console -- Restoring what beforeAll replaced. */
	SILENCED.forEach( ( method ) => {
		console[ method ] = originalConsole[ method ];
	} );
	/* eslint-enable no-console */
} );

describe( 'the sweep itself', () => {
	// Without these, an empty source list would make every assertion below pass by
	// vacuously iterating nothing — the same trap that made the first version of
	// blocks.test.js green against an empty block registry.
	it( 'found template parts to check', () => {
		expect( PARTS.length ).toBeGreaterThan( 0 );
		PARTS.forEach( ( part ) =>
			expect( part.content.length ).toBeGreaterThan( 0 )
		);
	} );

	it( 'found patterns to check', () => {
		expect( PATTERNS.length ).toBeGreaterThan( 0 );
		PATTERNS.forEach( ( p ) =>
			expect( p.content.length ).toBeGreaterThan( 0 )
		);
	} );

	it( 'rendered patterns contain no unresolved PHP', () => {
		PATTERNS.forEach( ( pattern ) => {
			expect( `${ pattern.file }: ${ pattern.content }` ).not.toContain(
				'<?php'
			);
		} );
	} );

	it( 'parses actual blocks out of each source', () => {
		[ ...PARTS, ...PATTERNS ].forEach( ( source ) => {
			expect( {
				file: source.file,
				blocks: flatten( parse( source.content ) ).length,
			} ).toEqual( {
				file: source.file,
				blocks: expect.any( Number ),
			} );

			expect( flatten( parse( source.content ) ).length ).toBeGreaterThan(
				0
			);
		} );
	} );
} );

describe( 'template parts', () => {
	it( 'match what save() currently emits', () => {
		expect( PARTS.flatMap( staleBlocksIn ) ).toEqual( [] );
	} );

	it( 'contain no unrecognized blocks', () => {
		expect( PARTS.flatMap( unrecognizedBlocksIn ) ).toEqual( [] );
	} );
} );

describe( 'patterns', () => {
	it( 'match what save() currently emits', () => {
		expect( PATTERNS.flatMap( staleBlocksIn ) ).toEqual( [] );
	} );

	it( 'contain no unrecognized blocks', () => {
		expect( PATTERNS.flatMap( unrecognizedBlocksIn ) ).toEqual( [] );
	} );

	it( 'use no core/html blocks', () => {
		// docs/architecture.md: raw markup pasted into content is banned outright. The one
		// legitimate core/html in the theme is chrome in parts/mobile-bar.html, which is why
		// this asserts against patterns only.
		const offenders = [];

		PATTERNS.forEach( ( pattern ) => {
			flatten( parse( pattern.content ) )
				.filter( ( block ) => block.name === 'core/html' )
				.forEach( () => offenders.push( pattern.file ) );
		} );

		expect( offenders ).toEqual( [] );
	} );
} );
