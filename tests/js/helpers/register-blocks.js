/**
 * Register the theme's custom blocks into a test's block registry.
 *
 * There is a wrinkle worth understanding before changing anything here.
 *
 * Every block in src/blocks/ calls `registerBlockType( metadata.name, { edit, save } )` —
 * the *name*, not the metadata object. On a real site that works because PHP's
 * `register_block_type()` has already read block.json and shipped its contents to the
 * client, so the title, attributes and supports are waiting under that name when the script
 * runs. Jest has no PHP, so without the same step `registerBlockType()` finds no title,
 * warns, and returns undefined — every block silently fails to register, `serialize()`
 * returns an empty string, and a round-trip test passes against nothing at all.
 *
 * `unstable__bootstrapServerSideBlockDefinitions()` is the exact API core uses to hand the
 * client its server-side definitions, so calling it with each block.json reproduces the real
 * arrangement rather than working around it. The theme's own source is imported unmodified.
 *
 * The empty-registry case is guarded by a test in blocks.test.js — it is the reason that
 * test exists.
 */

import { unstable__bootstrapServerSideBlockDefinitions as bootstrapServerDefinitions } from '@wordpress/blocks';

/**
 * Block directories under src/blocks/, in dependency order (parents before children).
 *
 * Written out rather than globbed on purpose: a new block nobody adds here is a new block
 * nobody tested, and an explicit list makes that visible in review. The registry test keeps
 * this honest by comparing it against what is actually on disk.
 *
 * @type {string[]}
 */
const BLOCK_DIRS = [
	'color-swatches',
	'color-swatch',
	'page-hero',
	'tabs',
	'tab',
	'tab-label',
	'tab-panel',
];

/**
 * Bootstrap each block's block.json, then import the module that registers it.
 *
 * @return {void}
 */
export function registerThemeBlocks() {
	BLOCK_DIRS.forEach( ( dir ) => {
		/* eslint-disable global-require */
		const metadata = require( `../../../src/blocks/${ dir }/block.json` );

		bootstrapServerDefinitions( { [ metadata.name ]: metadata } );

		require( `../../../src/blocks/${ dir }` );
		/* eslint-enable global-require */
	} );
}

/**
 * Register the theme's two server-rendered blocks.
 *
 * `section-nav` and `search-subsections` render in PHP and have no entry in src/blocks/, so
 * `registerThemeBlocks()` above does not cover them. The editor registers
 * them from src/js/editor/dynamic-blocks.js purely so the Site Editor has something to draw,
 * and that same module is imported here rather than hand-rolling stand-ins: a registration
 * dropped from it is a real bug (the Site Editor falls back to an "unsupported block"
 * placeholder), and a hand-rolled copy would keep passing straight through it.
 *
 * Only the markup sweep needs these — template parts reference them, and without the
 * registration every one parses as `core/missing`. Keep it out of `registerThemeBlocks()`
 * so the "registers every block that ships in src/blocks/" test keeps comparing like with
 * like.
 *
 * @return {void}
 */
export function registerDynamicBlocks() {
	// eslint-disable-next-line global-require
	require( '../../../src/js/editor/dynamic-blocks' );
}

/**
 * Every custom block this theme ships, with the tree shape each one legally appears in.
 *
 * `parent` in block.json means several of these cannot be validated standalone — a
 * `tab-label` outside a `tab` is not a case the editor ever produces — so the fixtures nest
 * them the way the editor does.
 *
 * @type {Array<{name: string, attributes: Object, innerBlocks: Array}>}
 */
export const BLOCK_FIXTURES = [
	{
		name: 'ucf-brand/tabs',
		attributes: {},
		innerBlocks: [
			{
				name: 'ucf-brand/tab',
				attributes: {},
				innerBlocks: [
					{
						name: 'ucf-brand/tab-label',
						attributes: {
							heading: 'Use the primary mark',
							description:
								'The full lockup, on a field with room to breathe.',
						},
						innerBlocks: [],
					},
					{
						name: 'ucf-brand/tab-panel',
						attributes: {},
						innerBlocks: [
							{
								name: 'core/paragraph',
								attributes: { content: 'Panel copy.' },
								innerBlocks: [],
							},
						],
					},
				],
			},
		],
	},
	{
		name: 'ucf-brand/color-swatches',
		attributes: {},
		innerBlocks: [
			{
				name: 'ucf-brand/color-swatch',
				attributes: {
					colorSlug: 'gold',
					name: 'Bold Gold',
					hex: '#EDB80C',
					rgb: '237 · 184 · 12',
					cmyk: '0 22 95 0',
					pantone: '7409 C',
					usage: 'Primary accent',
					ratio: '1.9:1',
					ratioStatus: 'fail',
				},
				innerBlocks: [],
			},
		],
	},
	{
		name: 'ucf-brand/page-hero',
		attributes: {},
		innerBlocks: [
			{
				name: 'core/paragraph',
				attributes: { content: 'Hero deck.' },
				innerBlocks: [],
			},
		],
	},
];
