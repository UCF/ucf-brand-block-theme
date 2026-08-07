/**
 * Run the accessibility suite, and always leave the environment stopped.
 *
 * Three steps that have to happen in order and cannot be a `&&` chain: boot wp-env, seed the
 * fixture content, run Playwright. It exists for the same four reasons
 * `tools/integration-tests.js` does — read the header there, it is the same argument — plus
 * one specific to this tier:
 *
 * **The seed must not be skipped, and a failed seed must not run the tests anyway.** The
 * pages the suite audits are generated, so a stale seed audits the previous run's content and
 * a missing one audits nothing. `tests/a11y/manifest.js` refuses to run without a manifest,
 * which covers the missing case; this covers the stale one by reseeding every time.
 *
 * Run with `npm run test:a11y`. Arguments are passed through to Playwright, so
 * `npm run test:a11y -- --project=desktop tests/a11y/variants.spec.js` works.
 *
 * While iterating, the boot and seed are around ninety seconds of pure overhead. Use
 * `npm run env:start && npm run env:seed` once, then `npm run test:a11y:only` in a loop.
 */
const { spawnSync } = require( 'child_process' );
const path = require( 'path' );

const root = path.join( __dirname, '..' );

/** Where the theme is mounted inside the containers. */
const THEME_PATH = 'wp-content/themes/ucf-brand-block-theme';

/**
 * Run a local binary through npx, inheriting stdio so its output reaches the terminal.
 *
 * @param {string}   bin  Binary name.
 * @param {string[]} args Arguments.
 * @return {number} Exit status.
 */
function run( bin, args ) {
	const result = spawnSync( 'npx', [ bin, ...args ], {
		cwd: root,
		stdio: 'inherit',
		shell: 'win32' === process.platform,
	} );

	if ( result.error ) {
		console.error( `Could not run ${ bin }: ${ result.error.message }` );
		return 1;
	}

	// A signal-terminated child reports status null; treat that as failure.
	return null === result.status ? 1 : result.status;
}

/**
 * Run a wp-env command.
 *
 * @param {string[]} args Arguments to wp-env.
 * @return {number} Exit status.
 */
function wpEnv( args ) {
	return run( 'wp-env', args );
}

let stopped = false;

/**
 * Stop the environment, once, however we got here.
 *
 * No try/catch: `spawnSync` reports a non-zero exit as `status` and a missing binary as
 * `error`, and neither throws, so a catch here would be unreachable. A failed teardown is
 * reported loudly instead of swallowed — containers outliving the run is the one outcome this
 * script exists to prevent.
 *
 * @return {void}
 */
function cleanUp() {
	if ( stopped ) {
		return;
	}

	stopped = true;

	if ( 0 !== wpEnv( [ 'stop' ] ) ) {
		console.error(
			'\nwp-env failed to stop. Containers may still be running and holding ports\n' +
				'8888/8889. Run `npm run env:stop` before starting another wp-env project.\n'
		);
	}
}

/*
 * Registered on `exit`, which is what makes "always" true rather than intended: `exit` fires
 * on normal completion, on an explicit `process.exit()`, and after an uncaught exception —
 * the last of which the signal handlers alone would miss. Signals do not raise `exit` on their
 * own, so their handlers call `process.exit()` and let this one path do the work.
 *
 * Registered before `wp-env start` on purpose: a start that fails partway can still leave
 * containers behind, and `cleanUp()` is idempotent.
 */
process.on( 'exit', cleanUp );

[ 'SIGINT', 'SIGTERM' ].forEach( ( signal ) => {
	process.on( signal, () => {
		process.exit( 1 );
	} );
} );

// `wp-env start` is idempotent, so this is safe whether or not the environment is already up.
if ( 0 !== wpEnv( [ 'start' ] ) ) {
	console.error(
		'\nwp-env failed to start. Two things account for almost every case:\n' +
			'  - Docker is not running.\n' +
			'  - Ports 8888/8889 are taken, usually by another wp-env project.\n' +
			'    Stop that one, or set "port"/"testsPort" in .wp-env.override.json\n' +
			'    (gitignored, so it stays local to your machine).\n'
	);
	process.exit( 1 );
}

const seeded = wpEnv( [
	'run',
	'cli',
	'wp',
	'eval-file',
	`${ THEME_PATH }/tests/a11y/seed.php`,
] );

if ( 0 !== seeded ) {
	console.error(
		'\nSeeding failed, so the suite has nothing trustworthy to audit and was not run.\n' +
			'The message above is from tests/a11y/seed.php.\n'
	);
	process.exit( seeded );
}

// Everything after `--` on the npm command line lands here and goes straight to Playwright.
const tests = run( 'playwright', [ 'test', ...process.argv.slice( 2 ) ] );

// Exit with the tests' status, not the teardown's. The `exit` handler stops the environment.
process.exit( tests );
