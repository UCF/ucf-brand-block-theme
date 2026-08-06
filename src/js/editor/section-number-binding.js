/**
 * Give the hero's eyebrow something to draw in the editor.
 *
 * `ucf-brand/section-number` is registered in PHP (includes/sections.php). That is enough
 * for the front end, but a server-registered source reaches the client with a label and
 * nothing else — no `getValues` — so the canvas falls back to printing the source's *name*
 * where the value should be. Authors saw "BRAND SECTION NUMBER" sitting where "Brand
 * Guidelines · Section 07" was going to render. Registering the client half fills that in.
 *
 * Deliberately no `setValues`: without one core keeps refusing to let the paragraph be
 * typed into, which is exactly right for a number the drawer owns and the Brand panel sets.
 * And deliberately no `label` or `usesContext` — the server already supplied both, and
 * passing a second label only earns an "overridden" warning.
 *
 * The eyebrow's `sprintf` is restated here alongside `formatSectionNumber()`; the same
 * keep-in-step caveat applies. See ./section-number.js.
 */
import { registerBlockBindingsSource } from '@wordpress/blocks';
import { store as editorStore } from '@wordpress/editor';
import { __, sprintf } from '@wordpress/i18n';

import { META_KEY, formatSectionNumber } from './section-number';

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
