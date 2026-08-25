/**
 * Hide on Mobile: a toggle that puts the `.hide-on-mobile` utility on any block.
 *
 * Unlike src/js/editor/stretch-link.js, this declares no attribute of its own. It adds and
 * removes the class on core's `className` rather than emitting it from
 * `blocks.getSaveContent.extraProps`.
 *
 * WHY: that filter only runs for blocks with a `save()`. The drawer's likeliest targets —
 * core/search and the server-rendered ucf-brand/section-nav — have none, and would take
 * the toggle and silently drop the class. `className` reaches the wrapper on both paths.
 * WHY: it also means nothing new is serialized, so a build where editor.js fails to load
 * leaves every block valid. stretch-link's own class would invalidate all of them.
 */
import { hasBlockSupport } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

// SYNC: the class the media query in src/scss/_utilities.scss paints.
const CLASS_NAME = 'hide-on-mobile';

const withHideOnMobileToggle = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		// WHY: keyed to core's `customClassName` support — a block that opts out of the
		// Advanced panel's class field has no `className` attribute to write to.
		if ( ! hasBlockSupport( props.name, 'customClassName', true ) ) {
			return <BlockEdit { ...props } />;
		}

		const classes = ( props.attributes.className || '' )
			.split( ' ' )
			.filter( Boolean );
		const hidden = classes.includes( CLASS_NAME );

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					<PanelBody
						title={ __( 'Visibility', 'ucf-brand-block-theme' ) }
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Hide on mobile',
								'ucf-brand-block-theme'
							) }
							help={ __(
								'Removes the block below 960px — from screen readers too, so keep anything a phone user still needs.',
								'ucf-brand-block-theme'
							) }
							checked={ hidden }
							onChange={ ( next ) => {
								const kept = classes.filter(
									( name ) => name !== CLASS_NAME
								);

								if ( next ) {
									kept.push( CLASS_NAME );
								}

								props.setAttributes( {
									className: kept.join( ' ' ) || undefined,
								} );
							} }
						/>
					</PanelBody>
				</InspectorControls>
			</>
		);
	},
	'withHideOnMobileToggle'
);
addFilter(
	'editor.BlockEdit',
	'ucf-brand/hide-on-mobile-toggle',
	withHideOnMobileToggle
);
