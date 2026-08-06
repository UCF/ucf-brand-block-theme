/**
 * Tab Label — the templated label for a tab.
 *
 * Not free-form: a gold badge (optional) over a condensed H3 heading, both plain-text
 * RichText. The badge is always gold-on-black; the heading follows the tab's state —
 * gold on black when idle, black on white when selected — driven entirely by CSS off
 * the `aria-selected` view.js sets (see _tabs.scss).
 *
 * Static block: `save` emits the real markup. view.js promotes the wrapper to
 * `role="tab"` at runtime (above the tabs breakpoint only). The heading is an H3 on
 * purpose — H2 is structural and drives the drawer (see CLAUDE.md).
 *
 * @package ucf-brand-block-theme
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
		const { badge, heading } = attributes;
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
					placeholder={ __(
						'Tab heading',
						'ucf-brand-block-theme'
					) }
					onChange={ ( value ) =>
						setAttributes( { heading: value } )
					}
				/>
			</div>
		);
	},

	save( { attributes } ) {
		const { badge, heading } = attributes;
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
			</div>
		);
	},
} );
