<?php
/**
 * Bootstrap for the PHP unit suite.
 *
 * These tests run WITHOUT WordPress — no database, no wp-env, no Docker. That is a
 * deliberate trade: the functions covered here are string transforms and markup builders,
 * and a suite that runs in under a second is one that actually gets run before a commit.
 * Anything that genuinely needs WordPress (a meta query against real posts,
 * WP_HTML_Tag_Processor, the render_block filter) belongs in the integration tier instead —
 * mocking those into this suite would only test the mock.
 *
 * The theme's own includes are NOT required here. Each calls add_action()/add_filter() at
 * include time, and those only exist while Brain Monkey is running, which is per-test. The
 * base TestCase requires them inside setUp() for that reason. See tests/php/TestCase.php.
 *
 * @package ucf-brand-block-theme
 */

// Every include guards on this. The value is irrelevant; only its presence is checked.
define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );

define( 'UCF_BRAND_THEME_DIR', dirname( __DIR__, 2 ) );

require_once UCF_BRAND_THEME_DIR . '/vendor/autoload.php';

// Data-only stand-ins for the two core classes the covered functions type-check against.
require_once __DIR__ . '/stubs/class-wp-post.php';
require_once __DIR__ . '/stubs/class-wp-block.php';

// The escaping and translation functions, as real definitions rather than per-test Brain
// Monkey aliases. They are shared with tests/php/render-patterns.php, which runs as a plain
// CLI script with no PHPUnit around it — one implementation, so the markup the validity
// sweep parses is escaped exactly the way the unit suite asserts it is.
require_once __DIR__ . '/stubs/wp-escaping.php';
