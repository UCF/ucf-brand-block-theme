<?php
/**
 * Bootstrap for the integration suite.
 *
 * This one DOES load WordPress — a real database, the real hook pipeline, real posts. It is
 * the counterpart to tests/php/bootstrap.php, which deliberately loads none of that.
 *
 * Everything here needs Docker (wp-env), which is exactly why it is a separate suite with a
 * separate config. `npm test` must stay sub-second and container-free, so it runs the unit
 * suite only; this runs on demand and in CI. See tests/README.md.
 *
 * What belongs here is anything that cannot be tested honestly without WordPress:
 *
 * - `ucf_brand_add_heading_anchor()`, which is a `render_block` filter and needs
 *   WP_HTML_Tag_Processor plus a real render pass.
 * - `ucf_brand_get_ordered_sections()`, which is a meta query against real posts.
 * - The search behaviour, which needs a real main WP_Query.
 *
 * Mocking those into the fast suite would test the mock. That boundary is the whole point of
 * having two tiers.
 *
 * @package ucf-brand-block-theme
 */

$ucf_brand_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $ucf_brand_tests_dir ) {
	$ucf_brand_tests_dir = dirname( __DIR__, 2 ) . '/vendor/wp-phpunit/wp-phpunit';
}

$ucf_brand_tests_dir = rtrim( $ucf_brand_tests_dir, '/\\' );

if ( ! file_exists( $ucf_brand_tests_dir . '/includes/functions.php' ) ) {
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI diagnostic, not markup.
	fwrite( STDERR, "Could not find the WordPress test library at {$ucf_brand_tests_dir}.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI script; WP_Filesystem has no notion of stderr.
	fwrite( STDERR, "This suite needs wp-env: npm run env:start && npm run test:integration\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- As above.
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// Must be loaded before the bootstrap so tests_add_filter() exists.
require_once $ucf_brand_tests_dir . '/includes/functions.php';

/**
 * Activate the theme under test before WordPress finishes loading.
 *
 * The suite boots with whatever theme the test install defaults to, which is not this one.
 * Switching at `muplugins_loaded` means every hook the theme registers is in place by the
 * time the first test runs — including the `render_block` filter that owns heading anchors,
 * which would otherwise simply never fire.
 *
 * @return void
 */
function ucf_brand_activate_theme_for_tests() {
	switch_theme( 'ucf-brand-block-theme' );
}
tests_add_filter( 'muplugins_loaded', 'ucf_brand_activate_theme_for_tests' );

require $ucf_brand_tests_dir . '/includes/bootstrap.php';
