/**
 * Color Swatch — one brand color and its published values.
 *
 * Static block: `save` emits real markup, so nothing is rendered on the server.
 *
 * The chip's color comes from the theme palette by slug and is applied with core's
 * `has-{slug}-background-color` class rather than an inline hex, so a swatch keeps
 * tracking its token if that token's value ever changes.
 */

import { Fragment, useEffect } from '@wordpress/element';
import { registerBlockType } from '@wordpress/blocks';
import {
	useBlockProps,
	InspectorControls,
	RichText,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import {
	BaseControl,
	ColorPalette,
	PanelBody,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Build the swatch's value lines, skipping any field left empty.
 *
 * Shared by edit and save so the two can never drift.
 *
 * @param {Object} attributes         Block attributes.
 * @param {string} attributes.hex     Hex value.
 * @param {string} attributes.rgb     RGB channels.
 * @param {string} attributes.cmyk    CMYK values.
 * @param {string} attributes.pantone Pantone name.
 * @param {string} attributes.usage   Usage note.
 * @return {string[]} Lines to render.
 */
function valueLines( { hex, rgb, cmyk, pantone, usage } ) {
	return [
		hex && `HEX ${ hex }`,
		rgb && `RGB ${ rgb }`,
		cmyk && `CMYK ${ cmyk }`,
		pantone && `PANTONE ${ pantone }`,
		usage,
	].filter( Boolean );
}

/** Separator between RGB channels, matching the published swatches. */
const RGB_SEP = ' · ';

/**
 * Split a six-digit hex color into its three channel values.
 *
 * @param {string} hex Color like `#EDB80D` (leading `#` optional).
 * @return {?number[]} `[ r, g, b ]`, or null if the string isn't a hex color.
 */
function hexToRgb( hex ) {
	const match = /^#?([0-9a-f]{6})$/i.exec( hex || '' );
	if ( ! match ) {
		return null;
	}
	const digits = match[ 1 ];
	return [ 0, 2, 4 ].map( ( at ) =>
		parseInt( digits.slice( at, at + 2 ), 16 )
	);
}

/**
 * WCAG relative luminance of an sRGB color.
 *
 * @param {number[]} rgb `[ r, g, b ]`, each 0–255.
 * @return {number} Relative luminance, 0–1.
 */
function luminance( [ r, g, b ] ) {
	const channel = ( value ) => {
		const c = value / 255;
		return c <= 0.03928
			? c / 12.92
			: Math.pow( ( c + 0.055 ) / 1.055, 2.4 );
	};
	return (
		0.2126 * channel( r ) + 0.7152 * channel( g ) + 0.0722 * channel( b )
	);
}

/**
 * WCAG contrast ratio between two relative luminances.
 *
 * @param {number} a One relative luminance, 0–1.
 * @param {number} b The other, 0–1.
 * @return {number} Contrast ratio, 1–21.
 */
function contrast( a, b ) {
	const [ hi, lo ] = a >= b ? [ a, b ] : [ b, a ];
	return ( hi + 0.05 ) / ( lo + 0.05 );
}

/**
 * Everything derivable from a palette color: published hex, rgb, and the
 * contrast ratio against whichever of black or white reads better.
 *
 * CMYK and Pantone are deliberately absent — they are print specs that no
 * formula reproduces, so they stay manual fields.
 *
 * @param {string} color Hex color from the palette token.
 * @return {Object} Attributes to merge in.
 */
function derivedFromColor( color ) {
	const rgb = hexToRgb( color );
	if ( ! rgb ) {
		return { hex: color };
	}
	const lum = luminance( rgb );
	const onBlack = contrast( lum, 0 );
	const onWhite = contrast( lum, 1 );
	const [ ratio, backdrop ] =
		onBlack >= onWhite ? [ onBlack, 'black' ] : [ onWhite, 'white' ];
	// Two decimals, but a whole ratio reads as "21:1", not "21.00:1".
	const rounded = ratio.toFixed( 2 ).replace( /\.?0+$/, '' );

	return {
		hex: color.toUpperCase(),
		rgb: rgb.join( RGB_SEP ),
		ratio: `${ rounded }:1 on ${ backdrop }`,
		// AA for normal text; the paper-alt style surface can override by hand.
		ratioStatus: ratio >= 4.5 ? 'pass' : 'warn',
	};
}

/**
 * The swatch wrapper's class, shared by edit and save so their markup stays byte-identical.
 *
 * The band layout puts the label on the color, so one label color cannot serve every swatch —
 * black on UCF Black is invisible, and on Link Blue it measures 3.59:1. Which one applies is
 * the author's call rather than something derived, so it is an attribute.
 *
 * An unset `labelInk` adds no class at all, and that is deliberate: it makes the markup of a
 * swatch saved before this control existed identical to what it saves now, so nothing needs a
 * `deprecated` entry and no swatch already sitting in a page is invalidated. Black is the
 * default in the stylesheet, which is the right answer for most of the palette — but it is a
 * default, not a measurement, so a dark swatch left untouched will need this set by hand.
 *
 * @param {string} labelInk `light`, or '' for the default.
 * @return {string} Class name for the wrapper.
 */
function swatchClass( labelInk ) {
	return labelInk ? `brand-swatch is-label-${ labelInk }` : 'brand-swatch';
}

/**
 * Props for the color chip, shared by edit and save so their markup stays
 * byte-identical.
 *
 * A palette token renders as core's `has-{slug}-background-color` class, so the
 * chip keeps tracking that token if its value ever changes. A custom color has
 * no token to track, so it falls back to an inline background — the same shape
 * core uses for a custom block color.
 *
 * @param {string} colorSlug   Palette slug, or empty for a custom color.
 * @param {string} customColor Hex color, used only when there is no slug.
 * @return {Object} `{ className, style }` for the chip element.
 */
function chipProps( colorSlug, customColor ) {
	if ( colorSlug ) {
		return {
			className: `brand-swatch__chip has-${ colorSlug }-background-color has-background`,
		};
	}
	return {
		className: 'brand-swatch__chip has-background',
		style: customColor ? { backgroundColor: customColor } : undefined,
	};
}

/**
 * Render value lines separated by line breaks.
 *
 * @param {string[]} lines Lines to render.
 * @return {Array} Elements.
 */
function renderLines( lines ) {
	return lines.map( ( line, i ) => (
		<Fragment key={ i }>
			{ i > 0 && <br /> }
			{ line }
		</Fragment>
	) );
}

function Edit( { attributes, setAttributes } ) {
	const {
		colorSlug,
		customColor,
		name,
		hex,
		rgb,
		cmyk,
		pantone,
		usage,
		ratio,
		ratioStatus,
		labelInk,
	} = attributes;
	const blockProps = useBlockProps( {
		className: swatchClass( labelInk ),
	} );

	// The theme palette, as the editor sees it — the same 19 brand tokens.
	const palette = useSelect(
		( select ) => select( blockEditorStore ).getSettings().colors || [],
		[]
	);

	// The color the control is currently showing: a token resolves to its
	// palette hex, otherwise the free-form custom color.
	const currentColor = colorSlug
		? palette.find( ( c ) => c.slug === colorSlug )?.color
		: customColor;

	/**
	 * Any color change — token or custom — recomputes every value the color
	 * determines (hex, rgb, contrast), so the chip and the text beside it can
	 * never disagree. A color that matches a palette token is stored as that
	 * token; anything else is kept as a custom hex.
	 *
	 * @param {?string} value Hex color from the picker, or undefined if cleared.
	 */
	const onColorChange = ( value ) => {
		if ( ! value ) {
			setAttributes( { colorSlug: '', customColor: '' } );
			return;
		}
		const match = palette.find(
			( c ) => c.color?.toLowerCase() === value.toLowerCase()
		);
		setAttributes( {
			colorSlug: match ? match.slug : '',
			customColor: match ? '' : value,
			...derivedFromColor( value ),
		} );
	};

	/**
	 * Fill the derived values on a freshly inserted swatch, once the palette is
	 * available and before anything has been entered by hand.
	 *
	 * The guard is `undefined === hex`, not `! hex`, and `hex` is declared in
	 * block.json with no default so that it can be. Three states, one attribute:
	 * absent is "never filled", a string is the published value, and `''` is an
	 * author who deleted it on purpose. A `! hex` guard cannot tell the last two
	 * apart, so it re-runs on every mount — clear HEX to publish a swatch without
	 * one, reopen the page, and it is back, with nothing to blame but the editor.
	 *
	 * Absent rather than a separate `filled` flag because an attribute with no
	 * default is not serialized at all: "never filled" stays out of post content
	 * instead of parking editor-only state in every page forever.
	 */
	useEffect( () => {
		if ( undefined !== hex || ! currentColor ) {
			return;
		}
		setAttributes( derivedFromColor( currentColor ) );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ currentColor ] );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Color', 'ucf-brand-block-theme' ) }>
					<BaseControl
						__nextHasNoMarginBottom
						help={ __(
							'Pick a brand token to keep tracking it, or choose a custom color for a one-off swatch.',
							'ucf-brand-block-theme'
						) }
					>
						{ /* A11Y: VisualLabel, not `label` — a ColorPalette is a
						     group of buttons with no labelable control, so a real
						     <label for> would point at nothing. Core's own
						     ColorGradientControl does the same. */ }
						<BaseControl.VisualLabel>
							{ __( 'Swatch color', 'ucf-brand-block-theme' ) }
						</BaseControl.VisualLabel>
						{ /* disableCustomColors:false overrides the theme's
						     global color.custom lock for this block only, so a
						     one-off swatch can use any color. */ }
						<ColorPalette
							colors={ palette }
							value={ currentColor }
							onChange={ onColorChange }
							disableCustomColors={ false }
							enableAlpha={ false }
							clearable={ false }
							__experimentalIsRenderedInSidebar
						/>
					</BaseControl>

					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Label color', 'ucf-brand-block-theme' ) }
						help={ __(
							'The name and values print on top of the swatch. Switch to white on a dark color — black is the default and will not be legible there.',
							'ucf-brand-block-theme'
						) }
						value={ labelInk }
						options={ [
							{
								label: __( 'Black', 'ucf-brand-block-theme' ),
								value: '',
							},
							{
								label: __( 'White', 'ucf-brand-block-theme' ),
								value: 'light',
							},
						] }
						onChange={ ( v ) => setAttributes( { labelInk: v } ) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Published values', 'ucf-brand-block-theme' ) }
				>
					{ /*
					   Editable, not read-only. These are filled from the swatch
					   color and refilled whenever it changes, but a computed
					   default is not the same as a locked one: a swatch may need
					   to publish fewer lines than the formula produces — a
					   digital-only color with no print values, or a surface
					   whose name is the whole point. `valueLines()` already drops
					   an empty field, so clearing one is how that is expressed,
					   and read-only fields left no way to do it.

					   `hex || ''` because `hex` is undefined until the first
					   fill — see the effect above — and a TextControl handed
					   undefined flips from uncontrolled to controlled the
					   moment a value lands.
					 */ }
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'HEX', 'ucf-brand-block-theme' ) }
						help={ __(
							'Filled from the swatch color. Edit or clear it to leave the line off.',
							'ucf-brand-block-theme'
						) }
						value={ hex || '' }
						onChange={ ( v ) => setAttributes( { hex: v } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'RGB', 'ucf-brand-block-theme' ) }
						help={ __(
							'Filled from the HEX value. Edit or clear it to leave the line off.',
							'ucf-brand-block-theme'
						) }
						value={ rgb }
						onChange={ ( v ) => setAttributes( { rgb: v } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'CMYK', 'ucf-brand-block-theme' ) }
						placeholder="0 · 23 · 100 · 7"
						value={ cmyk }
						onChange={ ( v ) => setAttributes( { cmyk: v } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Pantone', 'ucf-brand-block-theme' ) }
						value={ pantone }
						onChange={ ( v ) => setAttributes( { pantone: v } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Usage note', 'ucf-brand-block-theme' ) }
						placeholder={ __(
							'Digital only',
							'ucf-brand-block-theme'
						) }
						value={ usage }
						onChange={ ( v ) => setAttributes( { usage: v } ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Contrast', 'ucf-brand-block-theme' ) }>
					<TextControl
						__nextHasNoMarginBottom
						label={ __(
							'Contrast ratio',
							'ucf-brand-block-theme'
						) }
						placeholder="11.46:1 on black"
						help={ __(
							'Auto-computed against black or white. Override for surface-only colors (e.g. “Surface only”).',
							'ucf-brand-block-theme'
						) }
						value={ ratio }
						onChange={ ( v ) => setAttributes( { ratio: v } ) }
					/>
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Result', 'ucf-brand-block-theme' ) }
						value={ ratioStatus }
						options={ [
							{
								label: __( 'Passes', 'ucf-brand-block-theme' ),
								value: 'pass',
							},
							{
								label: __( 'Caution', 'ucf-brand-block-theme' ),
								value: 'warn',
							},
						] }
						onChange={ ( v ) =>
							setAttributes( { ratioStatus: v } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<span { ...chipProps( colorSlug, customColor ) } />
				<div className="brand-swatch__body">
					<RichText
						tagName="p"
						className="brand-swatch__name"
						value={ name }
						allowedFormats={ [] }
						placeholder={ __(
							'Color name',
							'ucf-brand-block-theme'
						) }
						onChange={ ( v ) => setAttributes( { name: v } ) }
					/>
					<p className="brand-swatch__value">
						{ renderLines( valueLines( attributes ) ) }
					</p>
					{ ratio && (
						<span
							className={ `brand-swatch__ratio is-${ ratioStatus }` }
						>
							{ ratio }
						</span>
					) }
				</div>
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,

	save( { attributes } ) {
		const { colorSlug, customColor, name, ratio, ratioStatus, labelInk } =
			attributes;
		const blockProps = useBlockProps.save( {
			className: swatchClass( labelInk ),
		} );

		return (
			<div { ...blockProps }>
				<span { ...chipProps( colorSlug, customColor ) } />
				<div className="brand-swatch__body">
					<RichText.Content
						tagName="p"
						className="brand-swatch__name"
						value={ name }
					/>
					<p className="brand-swatch__value">
						{ renderLines( valueLines( attributes ) ) }
					</p>
					{ ratio && (
						<span
							className={ `brand-swatch__ratio is-${ ratioStatus }` }
						>
							{ ratio }
						</span>
					) }
				</div>
			</div>
		);
	},
} );
