/**
 * Client registrations for the theme's two server-rendered blocks.
 *
 * Both render their markup in PHP — `ucf-brand/section-nav` in includes/section-nav.php and
 * `ucf-brand/search-subsections` in includes/search.php. Without a client registration the
 * Site Editor would show them as "unsupported block" placeholders, so these exist purely to
 * give the editor something to draw. Neither saves any markup.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

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
