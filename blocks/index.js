/**
 * Editor glue (no block.json — built via webpack.config.js as the `index` entry).
 *
 * Two jobs:
 *   1. A "Brand" panel on the Page document sidebar with a number field bound to the
 *      `ucf_brand_number` post meta. That value orders the page in the drawer and prints
 *      as its decimal label — see functions.php (`ucf_brand_get_ordered_sections`).
 *   2. Client registrations for the server-rendered `ucf-brand/section-nav` and
 *      `ucf-brand/search-subsections` blocks, so the Site Editor shows them as real blocks
 *      rather than "unsupported block" placeholders. Both render their markup in PHP.
 */
import { registerPlugin } from '@wordpress/plugins';
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import {
	PanelBody,
	Placeholder,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

const META_KEY = 'ucf_brand_number';

function BrandOrderPanel() {
	const postType = useSelect(
		( select ) => select( editorStore ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	if ( postType !== 'page' ) {
		return null;
	}

	const value = meta?.[ META_KEY ] ?? 0;

	return (
		<PluginDocumentSettingPanel
			name="ucf-brand-order"
			title={ __( 'Brand', 'ucf-brand-block-theme' ) }
		>
			<TextControl
				__nextHasNoMarginBottom
				type="number"
				min={ 0 }
				step={ 1 }
				label={ __( 'Brand order', 'ucf-brand-block-theme' ) }
				help={ __(
					'Orders this page in the drawer and prints as its label (1 → 01). Leave 0 to hide it from the drawer.',
					'ucf-brand-block-theme'
				) }
				value={ value ? String( value ) : '' }
				onChange={ ( next ) =>
					setMeta( {
						...meta,
						[ META_KEY ]: parseInt( next, 10 ) || 0,
					} )
				}
			/>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'ucf-brand-order', { render: BrandOrderPanel } );

/**
 * Stretch link: an orthogonal `stretchLink` toggle on core/button. Unlike a block
 * style (single-select — you couldn't have Outline *and* stretch), this is a boolean
 * that composes with any look. When on, it emits `.has-stretch-link` on the button
 * wrapper; the covering overlay lives in src/scss/_stretch-link.scss.
 */
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

registerBlockType( 'ucf-brand/section-nav', {
	apiVersion: 3,
	title: __( 'Brand section navigation', 'ucf-brand-block-theme' ),
	description: __(
		'The drawer menu, built automatically from each page’s Brand order.',
		'ucf-brand-block-theme'
	),
	category: 'theme',
	icon: 'menu',
	supports: { html: false, inserter: false, reusable: false },
	edit: () => <ServerSideRender block="ucf-brand/section-nav" />,
	save: () => null,
} );

// Unlike section-nav this one gets a static placeholder rather than a ServerSideRender
// preview: it renders per result row, from the live search query, so outside a search
// there is nothing for it to draw and the preview would only ever report itself empty.
registerBlockType( 'ucf-brand/search-subsections', {
	apiVersion: 3,
	title: __( 'Matching sections', 'ucf-brand-block-theme' ),
	description: __(
		'Deep links to the headings within a result that match the search query.',
		'ucf-brand-block-theme'
	),
	category: 'theme',
	icon: 'search',
	supports: { html: false, inserter: false, reusable: false },
	edit: () => (
		<div { ...useBlockProps() }>
			<Placeholder
				icon="search"
				label={ __( 'Matching sections', 'ucf-brand-block-theme' ) }
				instructions={ __(
					'Renders on the search results page only, beneath each result.',
					'ucf-brand-block-theme'
				) }
			/>
		</div>
	),
	save: () => null,
} );
