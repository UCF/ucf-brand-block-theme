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

// A starting grid, not a fixed set — the appender below lets an author add a
// single custom swatch (or remove any of these) at will.
const TEMPLATE = [
	[ 'ucf-brand/color-swatch', { colorSlug: 'gold', name: 'Bold Gold' } ],
	[ 'ucf-brand/color-swatch', { colorSlug: 'black', name: 'UCF Black' } ],
	[
		'ucf-brand/color-swatch',
		{ colorSlug: 'horizon-blue', name: 'Horizon Blue' },
	],
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
