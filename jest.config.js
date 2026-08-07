/**
 * Jest config for the block suite.
 *
 * The theme's blocks are static — `save()` emits the real markup — so the thing worth
 * testing is that what `save()` writes is what the editor can read back. See
 * tests/README.md.
 *
 * `@wordpress/*` is listed in devDependencies purely for this suite. The build externalizes
 * those imports to the `wp.*` globals WordPress already ships, so none of it reaches
 * build/ — check `npm run build` output size before assuming otherwise.
 */
module.exports = {
	preset: '@wordpress/jest-preset-default',
	rootDir: __dirname,

	// The preset's transform is a bare babel-jest, which needs a Babel config the theme
	// does not have — wp-scripts supplies @wordpress/babel-preset-default through its own
	// webpack babel-loader, never as a project-level babel.config.js. Passing the preset
	// inline here keeps Babel scoped to the test run: adding a babel.config.js at the root
	// would also be picked up by the build, which is a change to shipped output made for
	// the sake of the tests.
	// `.mjs` is included because the ESM-only packages below ship that extension.
	transform: {
		'\\.[cm]?[jt]sx?$': [
			require.resolve( 'babel-jest' ),
			{
				presets: [
					require.resolve( '@wordpress/babel-preset-default' ),
				],
			},
		],
	},

	moduleFileExtensions: [ 'js', 'mjs', 'cjs', 'jsx', 'ts', 'tsx', 'json' ],

	// Naming setupFiles replaces the preset's copy rather than adding to it, so the
	// preset's own globals file has to be listed alongside ours.
	setupFiles: [
		require.resolve(
			'@wordpress/jest-preset-default/scripts/setup-globals.js'
		),
		'<rootDir>/tests/js/setup-globals.js',
	],

	// Jest's jsdom environment resolves packages under the `browser` export condition,
	// which points most @wordpress/* packages at their ESM build (build-module/*.mjs).
	// Jest runs CommonJS, so every one of those fails on its first `import`. Asking for the
	// node/require conditions selects the .cjs builds that already exist in the same
	// packages — no transformation, just the right entry point.
	testEnvironmentOptions: {
		customExportConditions: [ 'node', 'require', 'default' ],
	},

	// A short list of dependencies have no CJS build to select at all, so the condition
	// above cannot help them and they go through Babel instead:
	//
	//   uuid 14, marked          reached via @wordpress/blocks (client ids, paste handler)
	//   @wordpress/theme         reached via components → @wordpress/ui
	//   @wordpress/interactivity reached via block-library's view scripts
	//
	// Keep this list short and specific. If it starts growing on every dependency bump,
	// that is the signal to stop resolving the editor through npm and validate against the
	// WordPress bundle the site actually runs instead — see tests/README.md.
	transformIgnorePatterns: [
		'/node_modules/(?!.*(uuid|marked|@wordpress/theme|@wordpress/interactivity))',
	],

	testMatch: [ '<rootDir>/tests/js/**/*.test.js' ],
	testPathIgnorePatterns: [ '/node_modules/', '/vendor/', '/build/' ],
	// The a11y suite is Playwright's, not Jest's; it lives in tests/a11y/.
	modulePathIgnorePatterns: [ '<rootDir>/tests/a11y/' ],
};
