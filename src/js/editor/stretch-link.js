/**
 * Stretch link: an orthogonal `stretchLink` toggle on core/button.
 *
 * Unlike a block style (single-select — you couldn't have Outline *and* stretch), this is
 * a boolean that composes with any look. When on, it emits `.has-stretch-link` on the
 * button wrapper; the covering overlay lives in src/scss/_stretch-link.scss.
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

const STRETCH_ATTR = 'stretchLink';

addFilter(
	'blocks.registerBlockType',
	'ucf-brand/stretch-link-attribute',
	( settings, name ) => {
		if ( name !== 'core/button' ) {
			return settings;
		}
		settings.attributes = {
			...settings.attributes,
			[ STRETCH_ATTR ]: { type: 'boolean', default: false },
		};
		return settings;
	}
);

const withStretchToggle = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		if ( props.name !== 'core/button' ) {
			return <BlockEdit { ...props } />;
		}

		const stretch = !! props.attributes[ STRETCH_ATTR ];

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					<PanelBody
						title={ __( 'Link behavior', 'ucf-brand-block-theme' ) }
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Stretch to container',
								'ucf-brand-block-theme'
							) }
							help={ __(
								'Makes the enclosing Group clickable. Previews on the front end, not in the editor.',
								'ucf-brand-block-theme'
							) }
							checked={ stretch }
							onChange={ ( next ) =>
								props.setAttributes( {
									[ STRETCH_ATTR ]: next,
								} )
							}
						/>
					</PanelBody>
				</InspectorControls>
			</>
		);
	},
	'withStretchToggle'
);
addFilter( 'editor.BlockEdit', 'ucf-brand/stretch-link-toggle', withStretchToggle );

addFilter(
	'blocks.getSaveContent.extraProps',
	'ucf-brand/stretch-link-class',
	( props, block, attributes ) => {
		if ( block.name !== 'core/button' || ! attributes[ STRETCH_ATTR ] ) {
			return props;
		}
		props.className = [ props.className, 'has-stretch-link' ]
			.filter( Boolean )
			.join( ' ' );
		return props;
	}
);
