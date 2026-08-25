/**
 * Tab Label — the templated label for a tab.
 *
 * Not free-form: a condensed H3 heading over an optional line of supporting copy, both
 * plain-text RichText. Heading and copy follow the tab's state — light on black when idle,
 * dark on white when selected — driven entirely by CSS off the `aria-selected` view.js
 * sets (see _tabs.scss).
 *
 * `save` emits the description element only when it has content, which is what keeps
 * labels saved before that attribute existed valid: with an empty description the output
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
/**
 * v1 — the label when it still carried a gold `badge` span above the heading.
 *
 * WHY: the badge was a real attribute holding real copy (the acronyms on the Digital
 * page), so dropping it changed `save()`. Without this entry every tab already in the
 * database parses as invalid; with it, WordPress matches the old output, runs `migrate`
 * and drops the badge on the next save.
 * FIX: `save` here must stay byte-identical to what shipped — including emitting the span
 * only when `badge` had content, since labels saved with an empty badge never held one.
 */
const v1 = {
	attributes: {
		...metadata.attributes,
		badge: {
			type: 'string',
			source: 'html',
			selector: '.ucf-tabs__badge',
			default: '',
		},
	},
	supports: metadata.supports,

	migrate( { badge, ...rest } ) {
		return rest;
	},

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
};

// UPSTREAM: `edit` is a named component, not an inline method — `useBlockProps` is a hook,
// and the react-hooks lint rule only recognises one inside a capitalised function. Every
// block in src/blocks/ follows this shape.
function Edit( { attributes, setAttributes } ) {
	const { heading, description } = attributes;
	const blockProps = useBlockProps( { className: 'ucf-tabs__label' } );

	return (
		<div { ...blockProps }>
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
		const { heading, description } = attributes;
		const blockProps = useBlockProps.save( {
			className: 'ucf-tabs__label',
		} );

		return (
			<div { ...blockProps }>
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

	deprecated: [ v1 ],
} );
