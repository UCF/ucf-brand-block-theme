/**
 * Playwright configuration for the accessibility suite.
 *
 * Three viewports, matching where the theme's layout actually changes rather than a set of
 * round numbers: the drawer collapses into the mobile bar, and `src/blocks/tabs/view.js`
 * builds its tablist only above 768px — so the tablet and mobile projects axe genuinely
 * different accessibility trees, not the same one at two widths.
 *
 * Browser: set `PW_CHANNEL=chrome` to drive a locally installed Chrome and skip Playwright's
 * ~300MB browser download. CI leaves it unset and runs `npx playwright install chromium`.
 *
 * `TEST_BASE_URL` points the suite somewhere other than wp-env. Note that the content the
 * specs expect comes from `tests/a11y/seed.php`, so an arbitrary site will not have it.
 */
const { defineConfig } = require( '@playwright/test' );

const channel = process.env.PW_CHANNEL;

module.exports = defineConfig( {
	testDir: './tests/a11y',
	fullyParallel: true,

	// A `test.only` left in a spec silently drops the rest of the suite. Locally that is a
	// convenience; in CI it is a green run that checked one page.
	forbidOnly: !! process.env.CI,

	// The suite reads static pages, so a failure is a real finding rather than a flake.
	// Retrying would only hide a genuinely flaky page.
	retries: 0,

	// CI adds two machine-readable outputs on top of the human one: `html` becomes the
	// uploaded artifact, and `json` is what `tools/a11y-report.js` renders into the pull
	// request comment.
	reporter: process.env.CI
		? [
				[ 'github' ],
				[ 'list' ],
				[ 'html', { open: 'never' } ],
				[ 'json', { outputFile: 'playwright-report/results.json' } ],
		  ]
		: [ [ 'list' ] ],

	use: {
		baseURL: process.env.TEST_BASE_URL || 'http://localhost:8888',
		headless: true,
		...( channel ? { channel } : {} ),
	},

	projects: [
		{ name: 'desktop', use: { viewport: { width: 1280, height: 900 } } },
		{ name: 'tablet', use: { viewport: { width: 780, height: 1024 } } },
		{ name: 'mobile', use: { viewport: { width: 360, height: 740 } } },
	],
} );
