/**
 * Tabs — the grid container.
 *
 * Static block: `save` emits real markup, so nothing is rendered on the server. The
 * saved markup is deliberately role-free — a stack of label/panel pairs. The behavior
 * (ARIA roles, keyboard, panel show/hide) is added at runtime by view.js, and only
 * above the tabs breakpoint. Below it, and with no JS at all, the stack *is* the mobile
 * layout: each label reads as a section heading with its panel beneath it.
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

const ALLOWED = [ 'ucf-brand/tab' ];

// A starting pair, not a fixed set — the appender lets an author add or remove tabs.
const TEMPLATE = [ [ 'ucf-brand/tab' ], [ 'ucf-brand/tab' ] ];

registerBlockType( metadata.name, {
	edit() {
		const blockProps = useBlockProps( { className: 'ucf-tabs' } );
		const innerProps = useInnerBlocksProps( blockProps, {
			allowedBlocks: ALLOWED,
			template: TEMPLATE,
			renderAppender: InnerBlocks.ButtonBlockAppender,
		} );

		return <div { ...innerProps } />;
	},

	save() {
		const blockProps = useBlockProps.save( { className: 'ucf-tabs' } );
		const innerProps = useInnerBlocksProps.save( blockProps );

		return <div { ...innerProps } />;
	},
} );
