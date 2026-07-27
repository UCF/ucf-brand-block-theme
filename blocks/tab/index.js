/**
 * Tab — one authoring unit pairing a label with its panel.
 *
 * Static block. Its inner template is locked to exactly one Tab Label and one Tab
 * Panel, so every tab always has both regions. On the front end the saved wrapper is
 * `display: contents` *only in the enhanced desktop grid* (see _tabs.scss), so its label
 * and panel become direct grid items — every label in the top row, panels stacked in a
 * shared cell below. In the editor and the mobile view the wrapper stays a real box, so
 * the whole tab is easy to select and remove.
 *
 * @package ucf-brand-block-theme
 */

import { registerBlockType } from '@wordpress/blocks';
import {
	useBlockProps,
	useInnerBlocksProps,
	BlockControls,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

// Both regions always present, and the pair can't be added to, removed, or reordered.
const TEMPLATE = [ [ 'ucf-brand/tab-label' ], [ 'ucf-brand/tab-panel' ] ];

registerBlockType( metadata.name, {
	edit( { clientId } ) {
		const blockProps = useBlockProps( { className: 'ucf-tabs__set' } );
		const innerProps = useInnerBlocksProps( blockProps, {
			template: TEMPLATE,
			templateLock: 'all',
			renderAppender: false,
		} );
		const { removeBlock } = useDispatch( blockEditorStore );

		return (
			<>
				<BlockControls>
					<ToolbarGroup>
						<ToolbarButton
							icon="trash"
							label={ __( 'Remove tab', 'ucf-brand-block-theme' ) }
							onClick={ () => removeBlock( clientId ) }
						/>
					</ToolbarGroup>
				</BlockControls>
				<div { ...innerProps } />
			</>
		);
	},

	save() {
		const blockProps = useBlockProps.save( { className: 'ucf-tabs__set' } );
		const innerProps = useInnerBlocksProps.save( blockProps );

		return <div { ...innerProps } />;
	},
} );
