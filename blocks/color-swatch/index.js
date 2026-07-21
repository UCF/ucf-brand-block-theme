/**
 * Color Swatch — one brand color and its published values.
 *
 * Static block: `save` emits real markup, so nothing is rendered on the server.
 *
 * The chip's color comes from the theme palette by slug and is applied with core's
 * `has-{slug}-background-color` class rather than an inline hex, so a swatch keeps
 * tracking its token if that token's value ever changes.
 *
 * @package ucf-brand-block-theme
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
 * @param {Object} attributes Block attributes.
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
	const int = parseInt( match[ 1 ], 16 );
	return [ ( int >> 16 ) & 255, ( int >> 8 ) & 255, int & 255 ];
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
		return c <= 0.03928 ? c / 12.92 : Math.pow( ( c + 0.055 ) / 1.055, 2.4 );
	};
	return 0.2126 * channel( r ) + 0.7152 * channel( g ) + 0.0722 * channel( b );
}

/** WCAG contrast ratio between two relative luminances. */
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

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
		const { colorSlug, customColor, name, hex, rgb, cmyk, pantone, usage, ratio, ratioStatus } = attributes;
		const blockProps = useBlockProps( { className: 'brand-swatch' } );

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

		// Fill the derived values on a freshly inserted swatch, once the palette
		// is available and before anything has been entered by hand.
		useEffect( () => {
			if ( hex || ! currentColor ) {
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
							label={ __( 'Swatch color', 'ucf-brand-block-theme' ) }
							help={ __(
								'Pick a brand token to keep tracking it, or choose a custom color for a one-off swatch.',
								'ucf-brand-block-theme'
							) }
						>
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
					</PanelBody>

					<PanelBody title={ __( 'Published values', 'ucf-brand-block-theme' ) }>
						<TextControl
							__nextHasNoMarginBottom
							readOnly
							label={ __( 'HEX', 'ucf-brand-block-theme' ) }
							help={ __(
								'Computed from the swatch color.',
								'ucf-brand-block-theme'
							) }
							value={ hex }
						/>
						<TextControl
							__nextHasNoMarginBottom
							readOnly
							label={ __( 'RGB', 'ucf-brand-block-theme' ) }
							help={ __(
								'Computed from the HEX value.',
								'ucf-brand-block-theme'
							) }
							value={ rgb }
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
							placeholder={ __( 'Digital only', 'ucf-brand-block-theme' ) }
							value={ usage }
							onChange={ ( v ) => setAttributes( { usage: v } ) }
						/>
					</PanelBody>

					<PanelBody title={ __( 'Contrast', 'ucf-brand-block-theme' ) }>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Contrast ratio', 'ucf-brand-block-theme' ) }
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
								{ label: __( 'Passes', 'ucf-brand-block-theme' ), value: 'pass' },
								{ label: __( 'Caution', 'ucf-brand-block-theme' ), value: 'warn' },
							] }
							onChange={ ( v ) => setAttributes( { ratioStatus: v } ) }
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
							placeholder={ __( 'Color name', 'ucf-brand-block-theme' ) }
							onChange={ ( v ) => setAttributes( { name: v } ) }
						/>
						<p className="brand-swatch__value">
							{ renderLines( valueLines( attributes ) ) }
						</p>
						{ ratio && (
							<span className={ `brand-swatch__ratio is-${ ratioStatus }` }>
								{ ratio }
							</span>
						) }
					</div>
				</div>
			</>
		);
	},

	save( { attributes } ) {
		const { colorSlug, customColor, name, ratio, ratioStatus } = attributes;
		const blockProps = useBlockProps.save( { className: 'brand-swatch' } );

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
						<span className={ `brand-swatch__ratio is-${ ratioStatus }` }>
							{ ratio }
						</span>
					) }
				</div>
			</div>
		);
	},
} );
