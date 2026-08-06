/**
 * Tab Panel — the free-form content region a tab reveals.
 *
 * Static block: `save` emits a role-free `.ucf-tabs__panel` wrapper around whatever
 * blocks the author nests. view.js promotes it to `role="tabpanel"` and hides the
 * inactive panels at runtime (above the tabs breakpoint only), so with no JS every
 * panel is simply visible beneath its label.
 *
 * @package ucf-brand-block-theme
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

const TEMPLATE = [
	[
		'core/paragraph',
		{ placeholder: __( 'Panel content…', 'ucf-brand-block-theme' ) },
	],
];

registerBlockType( metadata.name, {
	edit() {
		const blockProps = useBlockProps( { className: 'ucf-tabs__panel' } );
		const innerProps = useInnerBlocksProps( blockProps, {
			template: TEMPLATE,
			// The parent Tab locks its label/panel pair with templateLock: 'all',
			// which descendants inherit. Opt this region back out so a panel accepts
			// any blocks — headings, images, columns, buttons, etc.
			templateLock: false,
		} );

		return <div { ...innerProps } />;
	},

	save() {
		const blockProps = useBlockProps.save( {
			className: 'ucf-tabs__panel',
		} );
		const innerProps = useInnerBlocksProps.save( blockProps );

		return <div { ...innerProps } />;
	},
} );
