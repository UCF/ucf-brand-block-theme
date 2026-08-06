/**
 * Extends the default @wordpress/scripts webpack config.
 *
 * Two departures from the default:
 *
 * 1. **Three extra entries.** Only folders with a block.json are auto-detected, and these
 *    three have none: the editor glue (src/js/editor/), the drawer's front-end script and
 *    the badge rich-text format. Naming them here is what puts them through the same
 *    pipeline as the blocks — one dialect, one minifier, and a generated *.asset.php per
 *    entry so includes/enqueue.php never restates a dependency list by hand.
 *
 * 2. **`clean` keeps css/.** wp-scripts wipes the output directory on every build,
 *    preserving only `fonts/` and `images/`. The stylesheet is compiled into build/css/ by
 *    a separate `sass` run, so without this `npm run build:blocks` on its own would delete
 *    a stylesheet that `npm run build:css` had just produced.
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const blockEntries =
	typeof defaultConfig.entry === 'function'
		? defaultConfig.entry()
		: defaultConfig.entry;

module.exports = {
	...defaultConfig,
	entry: {
		...blockEntries,
		editor: './src/js/editor/index.js',
		'brand-nav': './src/js/brand-nav.js',
		'badge-format': './src/js/badge-format.js',
	},
	output: {
		...defaultConfig.output,
		clean: {
			keep: /^(css|fonts|images)\//,
		},
	},
};
