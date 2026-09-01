/**
 * The editor half of ucf-brand/section-index.
 *
 * The block renders in PHP (includes/section-index.php), so this exists to give the editor
 * something to draw — and, unlike the two stand-ins in dynamic-blocks.js, something to
 * edit: the entries themselves are derived and read-only, and the description beside each
 * one is the only authored value.
 *
 * WHY: a live preview built from the editor's own blocks rather than a ServerSideRender.
 * The descriptions are edited in place, one field per entry, so the preview has to be real
 * React — and the H2s an author is adding right now are in the store long before they are
 * in `post_content`, which is what the server reads.
 */
import { registerBlockType } from '@wordpress/blocks';
import {
	PlainText,
	RichText,
	store as blockEditorStore,
	useBlockProps,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { META_KEY, formatSectionNumber } from './section-number';

/**
 * Reduce a heading's stored content to the text the server keys descriptions by.
 *
 * SYNC: `ucf_brand_get_post_sections()` in includes/headings.php produces this same string
 * — tags stripped, entities decoded, trimmed — and looks a description up by it. Two
 * spellings of one key means a description that saves under one and renders under neither.
 *
 * @param {string} content Raw heading content, which may carry inline markup.
 * @return {string} Comparable title.
 */
function headingTitle( content ) {
	const decoder = document.createElement( 'textarea' );

	decoder.innerHTML = String( content || '' ).replace( /<[^>]*>/g, '' );

	return decoder.value.trim();
}

/**
 * Whether a block is hidden, itself or by an ancestor.
 *
 * UPSTREAM: `metadata.blockVisibility === false` is core's hide-block flag, and it hides
 * the subtree — `render_block` returns '' for the whole group, so a heading inside one is
 * not on the page. Only boolean false hides; the viewport form is an array and still
 * renders.
 *
 * SYNC: `ucf_brand_strip_hidden_blocks()` in includes/headings.php is the same rule applied
 * to stored markup, and it is what the saved block renders from. A heading dropped in one
 * and kept in the other is an entry that appears in the editor and not on the page.
 *
 * @param {Object} blockEditor The core/block-editor store's selectors.
 * @param {string} clientId    Block to test.
 * @return {boolean} True when the block does not render.
 */
export function isHiddenBlock( blockEditor, clientId ) {
	return [ clientId, ...blockEditor.getBlockParents( clientId ) ].some(
		( id ) =>
			false ===
			blockEditor.getBlockAttributes( id )?.metadata?.blockVisibility
	);
}

/**
 * What the description field offers: inline formatting, nothing structural.
 *
 * SYNC: `ucf_brand_section_index_allowed_html()` in includes/section-index.php is the
 * allowlist this markup has to survive, so the two lists move together. The badge tones are
 * spelled out because `ucf/badge-picker` is only the toolbar button — the tone formats are
 * what wrap the text, and one left out here cannot be applied at all.
 *
 * @type {string[]}
 */
const DESCRIPTION_FORMATS = [
	'core/bold',
	'core/italic',
	'core/link',
	'ucf/badge-picker',
	'ucf/badge',
	'ucf/badge-gold',
	'ucf/badge-blue',
	'ucf/badge-success',
	'ucf/badge-danger',
	'ucf/badge-dark',
	'ucf/badge-inverse',
];

/**
 * One entry: the derived number and title, plus its editable description.
 *
 * @param {Object}   props             Component props.
 * @param {string}   props.number      Decimal label, or '' on an unnumbered page.
 * @param {string}   props.title       Heading text.
 * @param {string}   props.description Current description, as rich-text HTML.
 * @param {Function} props.onChange    Called with the new description.
 * @return {Element} The row.
 */
function IndexRow( { number, title, description, onChange } ) {
	return (
		<li className="brand-index__item">
			<span className="brand-index__link">
				{ number && (
					<span className="brand-index__num is-style-meta">
						{ number }
					</span>
				) }
				{ /* SYNC: an h3 here as on the server (includes/section-index.php) —
				     the preview is what the styles are checked against. */ }
				<h3 className="brand-index__label">{ title }</h3>
			</span>
			<RichText
				tagName="p"
				className="brand-index__desc"
				value={ description }
				onChange={ onChange }
				allowedFormats={ DESCRIPTION_FORMATS }
				placeholder={ __(
					'Describe this section…',
					'ucf-brand-block-theme'
				) }
			/>
		</li>
	);
}

/**
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {Element} The block preview.
 */
function SectionIndexEdit( { attributes, setAttributes } ) {
	const { heading = '', descriptions = {} } = attributes;

	const { titles, number } = useSelect( ( select ) => {
		const blockEditor = select( blockEditorStore );

		// WHY: `core/editor` by name, not the imported store object the rest of this
		// directory uses. This module is loaded by tests/js as well as by the editor bundle
		// (see registerDynamicBlocks in tests/js/helpers/register-blocks.js), and
		// `@wordpress/editor` is the one editor package that is not in node_modules — an
		// import of it fails Jest's resolver. The store is absent outside the post editor
		// either way, hence the guard.
		const editor = select( 'core/editor' );

		// getBlocksByName() walks the whole tree in document order, so an H2 nested inside a
		// group or a column is found in the position it renders in — which is the order the
		// server reads them out of post_content.
		const headings = blockEditor
			.getBlocksByName( 'core/heading' )
			.filter( ( clientId ) => ! isHiddenBlock( blockEditor, clientId ) )
			.map( ( clientId ) => blockEditor.getBlockAttributes( clientId ) )
			.filter( ( attrs ) => attrs && 2 === ( attrs.level ?? 2 ) )
			.map( ( attrs ) => headingTitle( attrs.content ) )
			.filter( Boolean );

		return {
			titles: headings,
			number: formatSectionNumber(
				editor?.getEditedPostAttribute( 'meta' )?.[ META_KEY ]
			),
		};
	}, [] );

	function setDescription( title, value ) {
		setAttributes( {
			descriptions: { ...descriptions, [ title ]: value },
		} );
	}

	return (
		<nav { ...useBlockProps( { className: 'brand-index' } ) }>
			<PlainText
				className="brand-index__title"
				value={ heading }
				onChange={ ( value ) => setAttributes( { heading: value } ) }
				placeholder={ __(
					'Lead-in heading…',
					'ucf-brand-block-theme'
				) }
			/>
			{ titles.length ? (
				<ul className="brand-index__list">
					{ titles.map( ( title, position ) => (
						<IndexRow
							key={ title }
							number={ number && `${ number }.${ position + 1 }` }
							title={ title }
							description={ descriptions[ title ] || '' }
							onChange={ ( value ) =>
								setDescription( title, value )
							}
						/>
					) ) }
				</ul>
			) : (
				<p>
					{ __(
						'Add an H2 to this page and it will appear here.',
						'ucf-brand-block-theme'
					) }
				</p>
			) }
		</nav>
	);
}

registerBlockType( 'ucf-brand/section-index', {
	apiVersion: 3,
	title: __( 'Brand section index', 'ucf-brand-block-theme' ),
	description: __(
		'A jump list of this page’s H2 sections, built automatically. Only the descriptions are authored.',
		'ucf-brand-block-theme'
	),
	category: 'theme',
	icon: 'list-view',
	// SYNC: declared again in ucf_brand_register_section_index(), includes/section-index.php.
	attributes: {
		heading: { type: 'string', default: '' },
		descriptions: { type: 'object', default: {} },
	},
	usesContext: [ 'postId' ],
	supports: { html: false, reusable: false },
	edit: SectionIndexEdit,
	save: () => null,
} );
