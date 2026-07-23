/**
 * Tab — one authoring unit pairing a label with its panel.
 *
 * Static block. Its inner template is locked to exactly one Tab Label and one Tab
 * Panel, so every tab always has both regions. The saved wrapper carries
 * `display: contents` (see _tabs.scss), so it does not generate a box — the label and
 * panel become direct grid items of the parent Tabs container. That is what lets the
 * desktop grid put all labels in the top row and stack the panels in a shared cell
 * below, while the interleaved label→panel source order is exactly the mobile stack.
 *
 * @package ucf-brand-block-theme
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import metadata from './block.json';

// Both regions always present, and the pair can't be added to, removed, or reordered.
const TEMPLATE = [ [ 'ucf-brand/tab-label' ], [ 'ucf-brand/tab-panel' ] ];

registerBlockType( metadata.name, {
	edit() {
		const blockProps = useBlockProps( { className: 'ucf-tabs__set' } );
		const innerProps = useInnerBlocksProps( blockProps, {
			template: TEMPLATE,
			templateLock: 'all',
			renderAppender: false,
		} );

		return <div { ...innerProps } />;
	},

	save() {
		const blockProps = useBlockProps.save( { className: 'ucf-tabs__set' } );
		const innerProps = useInnerBlocksProps.save( blockProps );

		return <div { ...innerProps } />;
	},
} );
