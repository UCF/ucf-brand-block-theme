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

import { Fragment } from '@wordpress/element';
import { registerBlockType } from '@wordpress/blocks';
import {
	useBlockProps,
	InspectorControls,
	RichText,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
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
		const { colorSlug, name, hex, rgb, cmyk, pantone, usage, ratio, ratioStatus } = attributes;
		const blockProps = useBlockProps( { className: 'brand-swatch' } );

		// The theme palette, as the editor sees it — the same 19 brand tokens.
		const palette = useSelect(
			( select ) => select( blockEditorStore ).getSettings().colors || [],
			[]
		);

		const options = palette.map( ( c ) => ( { label: c.name, value: c.slug } ) );

		/**
		 * Switching the token also refreshes the published hex, so the chip and the
		 * text it sits above can't disagree.
		 *
		 * @param {string} slug Palette slug.
		 */
		const onColorChange = ( slug ) => {
			const match = palette.find( ( c ) => c.slug === slug );
			setAttributes( {
				colorSlug: slug,
				hex: match ? match.color.toUpperCase() : hex,
			} );
		};

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Color', 'ucf-brand-block-theme' ) }>
						<SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Palette token', 'ucf-brand-block-theme' ) }
							help={ __(
								'The chip tracks this token, so it follows any future change to its value.',
								'ucf-brand-block-theme'
							) }
							value={ colorSlug }
							options={ options }
							onChange={ onColorChange }
						/>
					</PanelBody>

					<PanelBody title={ __( 'Published values', 'ucf-brand-block-theme' ) }>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'HEX', 'ucf-brand-block-theme' ) }
							value={ hex }
							onChange={ ( v ) => setAttributes( { hex: v } ) }
						/>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'RGB', 'ucf-brand-block-theme' ) }
							placeholder="237 · 184 · 13"
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
							placeholder={ __( 'Digital only', 'ucf-brand-block-theme' ) }
							value={ usage }
							onChange={ ( v ) => setAttributes( { usage: v } ) }
						/>
					</PanelBody>

					<PanelBody title={ __( 'Contrast', 'ucf-brand-block-theme' ) }>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Measured ratio', 'ucf-brand-block-theme' ) }
							placeholder="11.46:1 on black"
							help={ __(
								'Measure it — do not estimate.',
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
					<span
						className={ `brand-swatch__chip has-${ colorSlug }-background-color has-background` }
					/>
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
		const { colorSlug, name, ratio, ratioStatus } = attributes;
		const blockProps = useBlockProps.save( { className: 'brand-swatch' } );

		return (
			<div { ...blockProps }>
				<span
					className={ `brand-swatch__chip has-${ colorSlug }-background-color has-background` }
				/>
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
