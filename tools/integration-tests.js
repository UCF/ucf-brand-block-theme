/**
 * Run the WordPress integration suite, and always leave the environment stopped.
 *
 * The suite needs Docker containers that `wp-env` boots. Left running they hold ports 8888
 * and 8889 plus four containers, which is how you end up unable to start a *different*
 * project's wp-env with nothing but "address already in use" to go on. So this starts the
 * environment, runs the tests, and stops it again.
 *
 * Four properties this exists to guarantee, none of which a chained npm script gives you
 * reliably across shells:
 *
 * 1. PHPUnit's exit code is what the process exits with, so CI and `&&` chains see a real
 *    pass or fail rather than the exit code of `wp-env stop`.
 * 2. The environment is stopped even when the tests fail — the common case, and exactly when
 *    a `&&` chain would skip the cleanup.
 * 3. It is stopped on Ctrl-C, and on an uncaught exception, rather than orphaning four
 *    containers. See the note on the `exit` handler below for why both routes are needed.
 * 4. A teardown that itself fails says so, loudly, instead of leaving you to discover it
 *    the next time some other project will not start.
 *
 * Run with `npm run test:integration`.
 *
 * While iterating, the start/stop cycle is roughly a minute of pure overhead. Use
 * `npm run env:start` once and then `npm run test:integration:only` for a ~1s loop, and
 * `npm run env:stop` when you are done.
 */
const { spawnSync } = require( 'child_process' );
const path = require( 'path' );

const root = path.join( __dirname, '..' );

/** Where the theme is mounted inside the container. */
const THEME_CWD = 'wp-content/themes/ucf-brand-block-theme';

/**
 * Run a wp-env command, inheriting stdio so its output reaches the terminal.
 *
 * @param {string[]} args Arguments to wp-env.
 * @return {number} Exit status.
 */
function wpEnv( args ) {
	const result = spawnSync( 'npx', [ 'wp-env', ...args ], {
		cwd: root,
		stdio: 'inherit',
		shell: process.platform === 'win32',
	} );

	if ( result.error ) {
		console.error( `Could not run wp-env: ${ result.error.message }` );
		return 1;
	}

	// A signal-terminated child reports status null; treat that as failure.
	return result.status === null ? 1 : result.status;
}

let stopped = false;

/**
 * Stop the environment, once, however we got here.
 *
 * No try/catch: `spawnSync` does not throw for the cases that actually happen — a command
 * exiting non-zero comes back as `status`, and a missing binary as `error` — and `wpEnv()`
 * already turns both into a return code. A catch here would be unreachable, and would have
 * made the failure below look handled when it was not.
 *
 * A failed teardown is reported loudly rather than swallowed. It is the one outcome this
 * whole script exists to prevent, and the person running it needs to know the containers
 * outlived the run.
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
 * Teardown is registered on `exit`, which is what makes "always" literally true rather than
 * merely intended.
 *
 * `exit` fires on normal completion, on an explicit `process.exit()`, and after an uncaught
 * exception — the last of which is the case an earlier version of this file missed, because
 * only `exit` runs then, not the signal handlers. Node 15+ also treats an unhandled rejection
 * as fatal, so that route ends up here too (there is no async code in this script, but the
 * guarantee should not depend on that staying true).
 *
 * Signals do not raise `exit` on their own, so their handlers call `process.exit()` and let
 * the one teardown path below do the work.
 *
 * `spawnSync` is synchronous, which is the reason an `exit` handler can do this at all —
 * anything asynchronous would be abandoned mid-flight.
 *
 * Registered before `wp-env start` on purpose: a start that fails partway can still leave
 * containers behind, and `cleanUp()` is idempotent, so running it after a no-op start costs
 * only a few seconds.
 */
process.on( 'exit', cleanUp );

[ 'SIGINT', 'SIGTERM' ].forEach( ( signal ) => {
	process.on( signal, () => {
		process.exit( 1 );
	} );
} );

// `wp-env start` is idempotent, so this is safe whether or not the environment is already up.
const started = wpEnv( [ 'start' ] );

if ( 0 !== started ) {
	console.error(
		'\nwp-env failed to start. Two things account for almost every case:\n' +
			'  - Docker is not running.\n' +
			'  - Ports 8888/8889 are taken, usually by another wp-env project.\n' +
			'    Stop that one, or set "port"/"testsPort" in .wp-env.override.json\n' +
			'    (gitignored, so it stays local to your machine).\n'
	);
	process.exit( started );
}

const tests = wpEnv( [
	'run',
	'tests-wordpress',
	`--env-cwd=${ THEME_CWD }`,
	'vendor/bin/phpunit',
	'-c',
	'phpunit-integration.xml.dist',
] );

// Exit with the tests' status, not the teardown's. The `exit` handler stops the environment.
process.exit( tests );
