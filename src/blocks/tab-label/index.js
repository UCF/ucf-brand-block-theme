/**
 * Tab Label — the templated label for a tab.
 *
 * Not free-form: a gold badge (optional) over a condensed H3 heading, with an optional
 * line of supporting copy under it — all plain-text RichText. The badge is always
 * gold-on-black; the heading and the copy follow the tab's state — light on black when
 * idle, dark on white when selected — driven entirely by CSS off the `aria-selected`
 * view.js sets (see _tabs.scss).
 *
 * `save` emits the description element only when it has content, which is what keeps
 * labels saved before this attribute existed valid: with an empty description the output
 * is byte-identical to what they hold, so they need no `deprecated` entry to migrate.
 *
 * Static block: `save` emits the real markup. view.js promotes the wrapper to
 * `role="tab"` at runtime (above the tabs breakpoint only). The heading is an H3 on
 * purpose — H2 is structural and drives the drawer (see CLAUDE.md).
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

function Edit( { attributes, setAttributes } ) {
	const { badge, heading, description } = attributes;
	const blockProps = useBlockProps( { className: 'ucf-tabs__label' } );

	return (
		<div { ...blockProps }>
			<RichText
				tagName="span"
				className="ucf-tabs__badge"
				value={ badge }
				allowedFormats={ [] }
				placeholder={ __( 'Badge', 'ucf-brand-block-theme' ) }
				onChange={ ( value ) => setAttributes( { badge: value } ) }
			/>
			<RichText
				tagName="h3"
				className="ucf-tabs__heading"
				value={ heading }
				allowedFormats={ [] }
				placeholder={ __( 'Tab heading', 'ucf-brand-block-theme' ) }
				onChange={ ( value ) => setAttributes( { heading: value } ) }
			/>
			<RichText
				tagName="p"
				className="ucf-tabs__description"
				value={ description }
				allowedFormats={ [] }
				placeholder={ __(
					'Supporting copy (optional)',
					'ucf-brand-block-theme'
				) }
				onChange={ ( value ) =>
					setAttributes( { description: value } )
				}
			/>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,

	save( { attributes } ) {
		const { badge, heading, description } = attributes;
		const blockProps = useBlockProps.save( {
			className: 'ucf-tabs__label',
		} );

		return (
			<div { ...blockProps }>
				{ badge && (
					<RichText.Content
						tagName="span"
						className="ucf-tabs__badge"
						value={ badge }
					/>
				) }
				<RichText.Content
					tagName="h3"
					className="ucf-tabs__heading"
					value={ heading }
				/>
				{ description && (
					<RichText.Content
						tagName="p"
						className="ucf-tabs__description"
						value={ description }
					/>
				) }
			</div>
		);
	},
} );
