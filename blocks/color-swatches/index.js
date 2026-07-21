/**
 * Color Swatches — the grid container.
 *
 * Static block: `save` emits real markup, so nothing is rendered on the server.
 *
 * @package ucf-brand-block-theme
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import metadata from './block.json';

const ALLOWED = [ 'ucf-brand/color-swatch' ];

const TEMPLATE = [
	[ 'ucf-brand/color-swatch', { colorSlug: 'gold', name: 'Bold Gold' } ],
	[ 'ucf-brand/color-swatch', { colorSlug: 'black', name: 'UCF Black' } ],
	[ 'ucf-brand/color-swatch', { colorSlug: 'horizon-blue', name: 'Horizon Blue' } ],
];

registerBlockType( metadata.name, {
	edit() {
		const blockProps = useBlockProps( { className: 'brand-swatches' } );
		const innerProps = useInnerBlocksProps( blockProps, {
			allowedBlocks: ALLOWED,
			template: TEMPLATE,
		} );

		return <div { ...innerProps } />;
	},

	save() {
		const blockProps = useBlockProps.save( { className: 'brand-swatches' } );
		const innerProps = useInnerBlocksProps.save( blockProps );

		return <div { ...innerProps } />;
	},
} );
