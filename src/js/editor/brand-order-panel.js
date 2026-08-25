/**
 * The "Brand" panel on the Page document sidebar.
 *
 * One number field, bound to the `ucf_brand_number` post meta. That value orders the page
 * in the drawer and prints as its decimal label — see `ucf_brand_get_ordered_sections()`
 * in includes/sections.php.
 */
import { registerPlugin } from '@wordpress/plugins';
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { META_KEY } from './section-number';

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
					'Orders this page in the drawer and prints as its label — 1 for section 1, whose H2 badges then read 1.1, 1.2. Leave 0 to hide it from the drawer.',
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
