/**
 * Keep the canvas's `--brand-section` in step with the Brand order field.
 *
 * `ucf_brand_editor_section_style()` in includes/enqueue.php already puts the page's
 * number into the canvas at load, and Gutenberg re-renders it on every canvas re-mount —
 * so all this has to cover is an author editing the field mid-session, by which point the
 * canvas is mounted. That is why there is no mount detection here.
 */
import { registerPlugin } from '@wordpress/plugins';
import { store as editorStore } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

import { META_KEY, formatSectionNumber } from './section-number';

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
