/**
 * Run the WordPress integration suite, and always leave the environment stopped.
 *
 * The suite needs Docker containers that `wp-env` boots. Left running they hold ports 8888
 * and 8889 plus four containers, which is how you end up unable to start a *different*
 * project's wp-env with nothing but "address already in use" to go on. So this starts the
 * environment, runs the tests, and stops it again — including when the tests fail, and when
 * the run is interrupted.
 *
 * Three properties this exists to guarantee, none of which a chained npm script gives you
 * reliably across shells:
 *
 * 1. PHPUnit's exit code is what the process exits with, so CI and `&&` chains see a real
 *    pass or fail rather than the exit code of `wp-env stop`.
 * 2. The environment is stopped even when the tests fail — the common case, and exactly when
 *    a `&&` chain would skip the cleanup.
 * 3. Ctrl-C stops it too, rather than orphaning four containers.
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

/**
 * Stop the environment. Never throws — cleanup must not mask the test result.
 *
 * @return {void}
 */
function stopEnvironment() {
	try {
		wpEnv( [ 'stop' ] );
	} catch ( error ) {
		console.error( `Failed to stop wp-env: ${ error.message }` );
	}
}

let stopped = false;

/**
 * Stop once, however we got here.
 *
 * @return {void}
 */
function cleanUp() {
	if ( ! stopped ) {
		stopped = true;
		stopEnvironment();
	}
}

// Ctrl-C and `kill` should still tear the containers down.
[ 'SIGINT', 'SIGTERM' ].forEach( ( signal ) => {
	process.on( signal, () => {
		cleanUp();
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
	cleanUp();
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

cleanUp();

// Exit with the tests' status, not the teardown's.
process.exit( tests );
