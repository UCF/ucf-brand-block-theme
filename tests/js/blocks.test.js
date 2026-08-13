/**
 * Per-block round-trip validity.
 *
 * Every custom block in this theme is static: `save()` emits the real markup and nothing is
 * rendered on the server. That makes exactly one failure mode dominant — `save()` changes,
 * the markup already stored in a page no longer matches what `save()` now produces, and the
 * editor flags every existing instance as invalid.
 *
 * That failure is invisible on the front end. docs/architecture.md says so outright: "A page
 * render is not a sufficient check — invalid blocks still render." So the assertion here is
 * the one the editor itself makes, `parse()` → `isValid`, run against markup produced by
 * `serialize()`. If this suite is green, opening the page in the editor will not show
 * "This block contains unexpected or invalid content."
 *
 * The snapshots alongside it are the early-warning half: they fail on any markup change at
 * all, including one that is still self-consistent and would therefore round-trip cleanly
 * while silently invalidating every page already saved with the old output.
 */

import {
	createBlock,
	isValidBlockContent,
	parse,
	serialize,
} from '@wordpress/blocks';
import { registerCoreBlocks } from '@wordpress/block-library';

import { BLOCK_FIXTURES, registerThemeBlocks } from './helpers/register-blocks';

/**
 * Turn a fixture tree into real blocks.
 *
 * @param {Object} fixture Fixture node.
 * @return {Object} Block object.
 */
function build( fixture ) {
	return createBlock(
		fixture.name,
		fixture.attributes,
		( fixture.innerBlocks || [] ).map( build )
	);
}

/**
 * Walk a parsed tree and collect every block that failed validation.
 *
 * @param {Array} blocks Parsed blocks.
 * @param {Array} acc    Accumulator.
 * @return {Array} Invalid block names.
 */
function collectInvalid( blocks, acc = [] ) {
	blocks.forEach( ( block ) => {
		if ( block.isValid !== true ) {
			acc.push( block.name || 'core/missing' );
		}

		if ( block.innerBlocks && block.innerBlocks.length ) {
			collectInvalid( block.innerBlocks, acc );
		}
	} );

	return acc;
}

beforeAll( () => {
	registerCoreBlocks();
	registerThemeBlocks();
} );

describe.each( BLOCK_FIXTURES.map( ( f ) => [ f.name, f ] ) )(
	'%s',
	( name, fixture ) => {
		it( 'serializes to markup the editor parses back as valid', () => {
			const html = serialize( build( fixture ) );
			const invalid = collectInvalid( parse( html ) );

			expect( invalid ).toEqual( [] );
		} );

		it( 'produces stable save() markup', () => {
			expect( serialize( build( fixture ) ) ).toMatchSnapshot();
		} );
	}
);

describe( 'ucf-brand/tab-label', () => {
	// The badge is conditional in save() — `{ badge && ... }`. A block saved without one
	// must still round-trip, and the branch must not leave an empty element behind that a
	// later save would not reproduce.
	it( 'round-trips with the optional badge omitted', () => {
		const html = serialize(
			createBlock( 'ucf-brand/tabs', {}, [
				createBlock( 'ucf-brand/tab', {}, [
					createBlock( 'ucf-brand/tab-label', {
						heading: 'No badge here',
					} ),
					createBlock( 'ucf-brand/tab-panel', {}, [] ),
				] ),
			] )
		);

		expect( collectInvalid( parse( html ) ) ).toEqual( [] );
		expect( html ).not.toContain( 'ucf-tabs__badge' );
	} );

	// `description` was added to this block after labels had already been saved without it.
	// Those pages hold markup with no `.ucf-tabs__description` element, and they stay valid
	// only because `save()` emits that element conditionally. If the guard is ever dropped —
	// or the element gains a wrapper that renders when the attribute is empty — every tab
	// label already in the database goes invalid, and the front end will not show it.
	//
	// `isValidBlockContent()`, not `parse()` → `isValid`: parse migrates markup through the
	// block's deprecations and then reports valid, which is exactly how this class of bug has
	// shipped from this repo before. See CLAUDE.md.
	it( 'still validates markup saved before the description existed', () => {
		const legacy =
			'<div class="wp-block-ucf-brand-tab-label ucf-tabs__label">' +
			'<span class="ucf-tabs__badge">Do</span>' +
			'<h3 class="ucf-tabs__heading">Use the primary mark</h3>' +
			'</div>';

		expect(
			isValidBlockContent(
				'ucf-brand/tab-label',
				{ badge: 'Do', heading: 'Use the primary mark' },
				legacy
			)
		).toBe( true );
	} );

	it( 'round-trips with the optional description omitted', () => {
		const html = serialize(
			createBlock( 'ucf-brand/tabs', {}, [
				createBlock( 'ucf-brand/tab', {}, [
					createBlock( 'ucf-brand/tab-label', {
						badge: 'Do',
						heading: 'No supporting copy here',
					} ),
					createBlock( 'ucf-brand/tab-panel', {}, [] ),
				] ),
			] )
		);

		expect( collectInvalid( parse( html ) ) ).toEqual( [] );
		expect( html ).not.toContain( 'ucf-tabs__description' );
	} );

	it( 'reads its RichText attributes back out of the markup', () => {
		const html = serialize(
			createBlock( 'ucf-brand/tabs', {}, [
				createBlock( 'ucf-brand/tab', {}, [
					createBlock( 'ucf-brand/tab-label', {
						badge: 'Do',
						heading: 'Use the primary mark',
						description: 'On a field with room to breathe.',
					} ),
					createBlock( 'ucf-brand/tab-panel', {}, [] ),
				] ),
			] )
		);

		const label = parse( html )[ 0 ].innerBlocks[ 0 ].innerBlocks[ 0 ];

		// `source: html` attributes are re-derived from the markup on parse, not from the
		// block comment. If the selector in block.json and the class in save() ever drift
		// apart, these come back empty while the block still looks valid.
		expect( label.attributes.badge ).toBe( 'Do' );
		expect( label.attributes.heading ).toBe( 'Use the primary mark' );
		expect( label.attributes.description ).toBe(
			'On a field with room to breathe.'
		);
	} );
} );

describe( 'ucf-brand/color-swatch', () => {
	it( 'applies the palette slug as a core class, never an inline hex', () => {
		const html = serialize( build( BLOCK_FIXTURES[ 1 ] ) );

		// docs/architecture.md: blocks take colors by palette slug and apply core's
		// has-{slug}-background-color, so a swatch keeps tracking its token if the token's
		// value changes. An inline background hex here is the regression.
		expect( html ).toContain( 'has-gold-background-color' );
		expect( html ).not.toMatch( /style="[^"]*background(-color)?:\s*#/i );
	} );

	it( 'round-trips with every optional value line empty', () => {
		const html = serialize(
			createBlock( 'ucf-brand/color-swatches', {}, [
				createBlock( 'ucf-brand/color-swatch', {
					colorSlug: 'black',
					name: 'UCF Black',
				} ),
			] )
		);

		expect( collectInvalid( parse( html ) ) ).toEqual( [] );
	} );
} );

describe( 'the block registry', () => {
	it( 'registers every block that ships in src/blocks/', () => {
		// Guards the hand-written import list in helpers/register-blocks.js against a new
		// block being added to src/blocks/ without being covered here.
		// eslint-disable-next-line global-require
		const { readdirSync } = require( 'fs' );
		// eslint-disable-next-line global-require
		const { join } = require( 'path' );
		// eslint-disable-next-line global-require
		const { getBlockTypes } = require( '@wordpress/blocks' );

		const onDisk = readdirSync( join( __dirname, '../../src/blocks' ), {
			withFileTypes: true,
		} )
			.filter( ( entry ) => entry.isDirectory() )
			.map( ( entry ) => `ucf-brand/${ entry.name }` )
			.sort();

		const registered = getBlockTypes()
			.map( ( type ) => type.name )
			.filter( ( name ) => name.startsWith( 'ucf-brand/' ) )
			.sort();

		expect( registered ).toEqual( onDisk );
	} );
} );
