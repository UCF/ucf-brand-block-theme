/**
 * Editor glue (no block.json — built via webpack.config.js as the `index` entry).
 *
 * Three jobs:
 *   1. A "Brand" panel on the Page document sidebar with a number field bound to the
 *      `ucf_brand_number` post meta. That value orders the page in the drawer and prints
 *      as its decimal label — see functions.php (`ucf_brand_get_ordered_sections`). Edits
 *      to it are mirrored into the editor canvas as `--brand-section` so the H2
 *      subsection badges stay correct while editing.
 *   2. Adding the page hero to the list of block types that stay editable while a page is
 *      open, which is what makes the hero editable in place rather than through the
 *      sidebar. See the `editor.postContentBlockTypes` filter below.
 *   3. Client registrations for the server-rendered `ucf-brand/section-nav` and
 *      `ucf-brand/search-subsections` blocks, so the Site Editor shows them as real blocks
 *      rather than "unsupported block" placeholders. Both render their markup in PHP.
 */
import { registerPlugin } from '@wordpress/plugins';
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import {
	registerBlockBindingsSource,
	registerBlockType,
} from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { useEntityProp } from '@wordpress/core-data';
import {
	PanelBody,
	Placeholder,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { __, sprintf } from '@wordpress/i18n';

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
 * Port of `ucf_brand_format_number()` in functions.php, shared by the two editor-only
 * consumers below — the canvas `--brand-section` and the hero's eyebrow binding.
 *
 * Two copies of one format can drift, the hazard CLAUDE.md flags over the heading slugs.
 * The blast radius is much smaller here: the front end never runs this code, so drift
 * shows up as a wrong preview, never a wrong page. Keep them in step anyway. PHP is the
 * original.
 *
 * @param {number|string} value Raw meta value.
 * @return {string} Zero-padded number, or '' when unset/0.
 */
function formatSectionNumber( value ) {
	const number = parseInt( value, 10 );

	return number > 0 ? String( number ).padStart( 2, '0' ) : '';
}

/**
 * Keep the canvas's `--brand-section` in step with the Brand order field.
 *
 * ucf_brand_editor_section_style() in functions.php already puts the page's number into
 * the canvas at load, and Gutenberg re-renders it on every canvas re-mount — so all this
 * has to cover is an author editing the field mid-session, by which point the canvas is
 * mounted. That is why there is no mount detection here.
 */
const CANVAS_IFRAME = 'iframe[name="editor-canvas"]';
const CANVAS_ROOT = '.is-root-container';

function BrandSectionVariable() {
	const section = useSelect( ( select ) => {
		const editor = select( editorStore );

		if ( editor.getCurrentPostType() !== 'page' ) {
			return '';
		}

		return formatSectionNumber(
			editor.getEditedPostAttribute( 'meta' )?.[ META_KEY ]
		);
	}, [] );

	useEffect( () => {
		// Non-iframed canvases (the mobile editor) keep the wrapper in the main document.
		const canvas =
			document.querySelector( CANVAS_IFRAME )?.contentDocument ?? document;

		// Written on the same element the PHP rule targets, so the inline style wins.
		// `initial` is the guaranteed-invalid value: it makes the badge's `content`
		// invalid and hides it, matching a page with no Brand order.
		canvas.querySelector( CANVAS_ROOT )?.style.setProperty(
			'--brand-section',
			section ? `"${ section }."` : 'initial'
		);
	}, [ section ] );

	return null;
}

registerPlugin( 'ucf-brand-section-variable', { render: BrandSectionVariable } );

/**
 * Keep the page hero editable while a page is open.
 *
 * Pages open in `template-locked` mode (functions.php). In that mode core disables the root
 * block outright and then re-enables one allowlist of block *types* — `core/post-title`,
 * `core/post-featured-image`, `core/post-content` — so everything else in the template,
 * the hero included, inherits `disabled`. That allowlist runs through this filter, so
 * naming the hero here is the whole mechanism that makes it editable in place.
 *
 * It has to be the wrapper block that gets named, not the blocks inside it: the filter
 * matches by block type, and allowlisting `core/paragraph` would unlock every paragraph in
 * every template. The wrapper's own `templateLock: 'contentOnly'` then sorts its children —
 * see blocks/page-hero/index.js.
 *
 * If this filter ever goes away the hero reverts to being inert in the editor. Nothing on
 * the front end depends on it.
 */
addFilter(
	'editor.postContentBlockTypes',
	'ucf-brand/page-hero-editable',
	( blockTypes ) => [ ...blockTypes, 'ucf-brand/page-hero' ]
);

/**
 * Give the hero's eyebrow something to draw in the editor.
 *
 * `ucf-brand/section-number` is registered in PHP (functions.php). That is enough for the
 * front end, but a server-registered source reaches the client with a label and nothing
 * else — no `getValues` — so the canvas falls back to printing the source's *name* where
 * the value should be. Authors saw "BRAND SECTION NUMBER" sitting where "Brand Guidelines ·
 * Section 07" was going to render. Registering the client half fills that in.
 *
 * Deliberately no `setValues`: without one core keeps refusing to let the paragraph be
 * typed into, which is exactly right for a number the drawer owns and the Brand panel sets.
 * And deliberately no `label` or `usesContext` — the server already supplied both, and
 * passing a second label only earns an "overridden" warning.
 *
 * The eyebrow's `sprintf` is restated here alongside `formatSectionNumber()` above; the
 * same keep-in-step caveat applies.
 */
registerBlockBindingsSource( {
	name: 'ucf-brand/section-number',
	getValues( { select, bindings } ) {
		const meta = select( editorStore ).getEditedPostAttribute( 'meta' );
		const number = formatSectionNumber( meta?.[ META_KEY ] );
		const values = {};

		for ( const [ attribute, binding ] of Object.entries( bindings ) ) {
			values[ attribute ] =
				number && binding.args?.label
					? sprintf(
							/* translators: 1: guide name, e.g. "Brand Guidelines". 2: zero-padded section number, e.g. "05". */
							__( '%1$s · Section %2$s', 'ucf-brand-block-theme' ),
							binding.args.label,
							number
					  )
					: number;
		}

		return values;
	},
} );

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
