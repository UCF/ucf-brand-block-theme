/**
 * Editor glue — the `editor` webpack entry, enqueued by includes/enqueue.php.
 *
 * Everything here customizes the block editor and nothing here ships to the front end.
 * It has no block.json of its own, so webpack.config.js declares it as an explicit entry
 * alongside the block folders discovered under src/blocks/.
 *
 * Each import below is one self-contained job; read the file for the reasoning:
 *
 *   brand-order-panel      The "Brand" number field on the Page document sidebar.
 *   section-variable       Mirrors that number into the canvas as `--brand-section`.
 *   page-hero-editable     Keeps the hero editable while a page is open.
 *   section-number-binding The client half of the `ucf-brand/section-number` binding.
 *   stretch-link           A `stretchLink` toggle added to core/button.
 *   dynamic-blocks         Editor stand-ins for the two PHP-rendered blocks with no UI.
 *   section-index          The third PHP-rendered block, whose descriptions are editable.
 *
 * Order is not significant — every module registers against a hook or a store and none
 * depends on another's side effects. The shared `section-number` helper is imported by
 * the three modules that need the page's number rather than re-derived in each.
 */
import './brand-order-panel';
import './section-variable';
import './page-hero-editable';
import './section-number-binding';
import './stretch-link';
import './dynamic-blocks';
import './section-index';
