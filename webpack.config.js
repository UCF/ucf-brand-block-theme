/**
 * Extend the default @wordpress/scripts webpack config to also build the editor glue
 * script (blocks/index.js → build/index.js), which has no block.json and so is not
 * auto-detected. The block entries (color-swatch, color-swatches) are still discovered
 * from their block.json files by the default entry resolver.
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
		index: './blocks/index.js',
	},
};
