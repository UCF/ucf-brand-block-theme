/**
 * The hero's light/dark treatment, per page.
 *
 * Two halves, both here because they are one feature: the control that writes
 * `ucf_brand_hero_treatment`, and the canvas filter that shows the choice on the hero while
 * the page is open. The front end's half is `ucf_brand_apply_hero_treatment()` in
 * includes/hero.php — the template's class is the site-wide default and this overrides it
 * for one page.
 *
 * WHY: a page-level field rather than the block style on the hero. The hero lives in
 * templates/page.html, so its own style variation is one setting for every brand page, and
 * a page open in `template-locked` mode offers an author no block controls at all.
 */
import { registerPlugin } from '@wordpress/plugins';
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { addFilter } from '@wordpress/hooks';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const TREATMENT_KEY = 'ucf_brand_hero_treatment';

/**
 * SYNC: `ucf_brand_hero_treatments()` in includes/hero.php allows exactly these two, and
 * the meta's sanitize callback drops anything else. '' is the page following the template.
 *
 * @type {Array<{label: string, value: string}>}
 */
const TREATMENTS = [
	{
		label: __( 'Use the template default', 'ucf-brand-block-theme' ),
		value: '',
	},
	{ label: __( 'Dark', 'ucf-brand-block-theme' ), value: 'dark' },
	{ label: __( 'Light', 'ucf-brand-block-theme' ), value: 'light' },
];

/**
 * The canvas class for a treatment.
 *
 * SYNC: `.brand-hero.is-hero-treatment-*` in src/scss/_hero.scss. Deliberately not the
 * `is-style-*` composition class the front end gets: the hero belongs to the template, so in
 * the canvas it still carries the template's own class and the two would both apply — which
 * one painted would come down to their order in main.css. These rules are written to win.
 *
 * @param {string} treatment Treatment slug.
 * @return {string} Class name.
 */
const treatmentClass = ( treatment ) => `is-hero-treatment-${ treatment }`;

/** Every class the choice picks between, so switching clears the last one. */
const TREATMENT_CLASSES = TREATMENTS.filter( ( t ) => t.value ).map( ( t ) =>
	treatmentClass( t.value )
);

/**
 * The current page's treatment, or '' when it follows the template.
 *
 * @return {string} Treatment slug.
 */
function useHeroTreatment() {
	return useSelect( ( select ) => {
		const editor = select( editorStore );

		if ( editor.getCurrentPostType() !== 'page' ) {
			return '';
		}

		return editor.getEditedPostAttribute( 'meta' )?.[ TREATMENT_KEY ] ?? '';
	}, [] );
}

function HeroTreatmentPanel() {
	const postType = useSelect(
		( select ) => select( editorStore ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	if ( postType !== 'page' ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="ucf-brand-hero-treatment"
			title={ __( 'Hero', 'ucf-brand-block-theme' ) }
		>
			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Hero treatment', 'ucf-brand-block-theme' ) }
				help={ __(
					'White copy on the dark band, or black copy on the light one. The photo scrim follows the choice. Leave on the template default unless this page needs the other.',
					'ucf-brand-block-theme'
				) }
				options={ TREATMENTS }
				value={ meta?.[ TREATMENT_KEY ] ?? '' }
				onChange={ ( next ) =>
					setMeta( { ...meta, [ TREATMENT_KEY ]: next } )
				}
			/>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'ucf-brand-hero-treatment', { render: HeroTreatmentPanel } );

/**
 * Show the page's treatment on the hero in the canvas.
 *
 * WHY: the class cannot be written into the block. The hero is the template's block, shared
 * by every page — editing its attributes here would change the template for the whole site,
 * which is the thing this field exists to avoid. The class is added to the rendered wrapper
 * only, so nothing is saved and the block stays valid.
 */
const withHeroTreatment = createHigherOrderComponent(
	( BlockListBlock ) => ( props ) => {
		const treatment = useHeroTreatment();

		if ( 'ucf-brand/page-hero' !== props.name || ! treatment ) {
			return <BlockListBlock { ...props } />;
		}

		const className = [
			...String( props.className || '' )
				.split( ' ' )
				.filter(
					( name ) => name && ! TREATMENT_CLASSES.includes( name )
				),
			treatmentClass( treatment ),
		].join( ' ' );

		return <BlockListBlock { ...props } className={ className } />;
	},
	'withHeroTreatment'
);

addFilter(
	'editor.BlockListBlock',
	'ucf-brand/hero-treatment',
	withHeroTreatment
);
