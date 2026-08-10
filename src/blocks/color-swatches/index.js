/**
 * Color Swatches — the grid container.
 *
 * Static block: `save` emits real markup, so nothing is rendered on the server.
 *
 * @package ucf-brand-block-theme
 */

import { registerBlockType } from '@wordpress/blocks';
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import metadata from './block.json';

const ALLOWED = [ 'ucf-brand/color-swatch' ];

// One swatch to start, not a row: the band is a single contiguous run of color, so a
// three-swatch default reads as the finished thing an author is meant to keep rather than
// the first of however many they came to build. The appender below adds the rest, and
// `patterns/groups/color-swatches.php` is where the full published palette lives.
const TEMPLATE = [
	[ 'ucf-brand/color-swatch', { colorSlug: 'gold', name: 'Bold Gold' } ],
];

registerBlockType( metadata.name, {
	edit() {
		const blockProps = useBlockProps( { className: 'brand-swatches' } );
		const innerProps = useInnerBlocksProps( blockProps, {
			allowedBlocks: ALLOWED,
			template: TEMPLATE,
			renderAppender: InnerBlocks.ButtonBlockAppender,
		} );

		return <div { ...innerProps } />;
	},

	save() {
		const blockProps = useBlockProps.save( {
			className: 'brand-swatches',
		} );
		const innerProps = useInnerBlocksProps.save( blockProps );

		return <div { ...innerProps } />;
	},
} );
