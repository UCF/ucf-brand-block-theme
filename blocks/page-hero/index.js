/**
 * Page Hero — the container the page hero's core blocks sit in.
 *
 * It holds no content of its own. Its whole job is to be a block the editor can *name*,
 * because that is the only handle WordPress gives us for keeping something in a template
 * editable while a page is open.
 *
 * In `template-locked` mode (the theme's default for pages — see functions.php) core
 * disables the root block and then re-enables a short allowlist of block *types*. Anything
 * not on that list inherits `disabled`, which is why the hero used to be inert. The list is
 * filterable, so blocks/index.js adds this block to it; see the comment there. A wrapper is
 * the unit that works because the filter matches by name — we cannot allowlist
 * `core/paragraph` without unlocking every paragraph in the template.
 *
 * `templateLock: 'contentOnly'` then decides what stays editable *inside* it: core keeps
 * blocks that declare a `role: "content"` attribute and disables the rest. That lands
 * exactly where the design wants it — the title, the featured image and the two bound
 * paragraphs are editable; the separator is not; and the eyebrow is not either, because its
 * binding source is read-only. Nothing can be added, removed or reordered.
 *
 * Static block: `save` emits real markup and nothing renders on the server. Everything
 * dynamic in the hero is a core block this one merely wraps, which is what keeps it free of
 * any reference to the theme.
 *
 * @package ucf-brand-block-theme
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit() {
		const blockProps = useBlockProps( { className: 'brand-hero' } );
		const innerProps = useInnerBlocksProps( blockProps, {
			templateLock: 'contentOnly',
		} );

		return <div { ...innerProps } />;
	},

	save() {
		const blockProps = useBlockProps.save( { className: 'brand-hero' } );
		const innerProps = useInnerBlocksProps.save( blockProps );

		return <div { ...innerProps } />;
	},
} );
