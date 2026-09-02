/**
 * Which headings the index block's editor preview counts as sections.
 *
 * The preview reads the editor's own blocks rather than post_content, so it has to apply
 * core's hide-block flag itself. `ucf_brand_strip_hidden_blocks()` in includes/headings.php
 * is the server's half of the same rule — the two have to agree, or an entry shows in the
 * editor and is missing from the page.
 */
import { isHiddenBlock } from '../../src/js/editor/section-index';

/**
 * A stand-in for the core/block-editor selectors, built from a flat parent map.
 *
 * @param {Object} tree Client id → { parent, attributes }.
 * @return {Object} The two selectors isHiddenBlock() uses.
 */
function store( tree ) {
	return {
		getBlockAttributes: ( clientId ) => tree[ clientId ]?.attributes,
		getBlockParents: ( clientId ) => {
			const parents = [];
			let current = tree[ clientId ]?.parent;

			while ( current ) {
				parents.unshift( current );
				current = tree[ current ]?.parent;
			}

			return parents;
		},
	};
}

describe( 'isHiddenBlock', () => {
	it( 'is false for a block with no visibility metadata', () => {
		const tree = store( { heading: { attributes: { level: 2 } } } );

		expect( isHiddenBlock( tree, 'heading' ) ).toBe( false );
	} );

	it( 'is true for a block hidden on itself', () => {
		const tree = store( {
			heading: {
				attributes: { metadata: { blockVisibility: false } },
			},
		} );

		expect( isHiddenBlock( tree, 'heading' ) ).toBe( true );
	} );

	it( 'is true for a heading inside a hidden group, however deeply nested', () => {
		const tree = store( {
			group: { attributes: { metadata: { blockVisibility: false } } },
			columns: { parent: 'group', attributes: {} },
			column: { parent: 'columns', attributes: {} },
			heading: { parent: 'column', attributes: { level: 2 } },
		} );

		expect( isHiddenBlock( tree, 'heading' ) ).toBe( true );
	} );

	it( 'is false for a heading in a visible sibling of a hidden group', () => {
		const tree = store( {
			hidden: { attributes: { metadata: { blockVisibility: false } } },
			shown: { attributes: {} },
			heading: { parent: 'shown', attributes: { level: 2 } },
		} );

		expect( isHiddenBlock( tree, 'heading' ) ).toBe( false );
	} );

	// UPSTREAM: the viewport form hides with a media query and still renders, so the
	// heading is on the page at some width. Only boolean false removes a block.
	it( 'is false for the viewport form of blockVisibility', () => {
		const tree = store( {
			group: {
				attributes: {
					metadata: {
						blockVisibility: { viewport: { mobile: false } },
					},
				},
			},
			heading: { parent: 'group', attributes: { level: 2 } },
		} );

		expect( isHiddenBlock( tree, 'heading' ) ).toBe( false );
	} );
} );
