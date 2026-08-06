/**
 * Keep the page hero editable while a page is open.
 *
 * Pages open in `template-locked` mode (includes/setup.php). In that mode core disables the
 * root block outright and then re-enables one allowlist of block *types* —
 * `core/post-title`, `core/post-featured-image`, `core/post-content` — so everything else in
 * the template, the hero included, inherits `disabled`. That allowlist runs through this
 * filter, so naming the hero here is the whole mechanism that makes it editable in place.
 *
 * It has to be the wrapper block that gets named, not the blocks inside it: the filter
 * matches by block type, and allowlisting `core/paragraph` would unlock every paragraph in
 * every template. The wrapper's own `templateLock: 'contentOnly'` then sorts its children —
 * see src/blocks/page-hero/index.js.
 *
 * If this filter ever goes away the hero reverts to being inert in the editor. Nothing on
 * the front end depends on it.
 */
import { addFilter } from '@wordpress/hooks';

addFilter(
	'editor.postContentBlockTypes',
	'ucf-brand/page-hero-editable',
	( blockTypes ) => [ ...blockTypes, 'ucf-brand/page-hero' ]
);
