<?php
/**
 * Base case for the WordPress-free unit suite.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Sets up Brain Monkey and the small set of WordPress functions the theme's pure
 * helpers actually call.
 */
abstract class TestCase extends PHPUnitTestCase {

	/**
	 * Include files already loaded, so the theme's add_action() calls run once.
	 *
	 * @var array<string, bool>
	 */
	private static $loaded = array();

	/**
	 * Start Brain Monkey and stub WordPress.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Load one of the theme's include files.
	 *
	 * Must happen after Monkey\setUp(), because every include calls add_action() or
	 * add_filter() at include time and Brain Monkey is what defines those. Loading is
	 * idempotent: PHP function definitions persist for the whole process, so only the
	 * first test that asks for a file actually includes it.
	 *
	 * @param string ...$names Include basenames, e.g. 'headings'.
	 * @return void
	 */
	protected function loadInclude( ...$names ) {
		foreach ( $names as $name ) {
			if ( isset( self::$loaded[ $name ] ) ) {
				continue;
			}

			require_once UCF_BRAND_THEME_DIR . "/includes/{$name}.php";
			self::$loaded[ $name ] = true;
		}
	}

	/*
	 * The escaping and translation functions used to be aliased here per test. They now
	 * live in tests/php/stubs/wp-escaping.php as real definitions, loaded once by the
	 * bootstrap, because tests/php/render-patterns.php needs the identical
	 * implementations and runs as a plain CLI script with no Brain Monkey around it.
	 *
	 * Only functions whose return value a test needs to *vary* — get_post_meta(),
	 * get_posts(), get_queried_object_id() and friends — are still stubbed per test, with
	 * Brain Monkey, in the test that cares.
	 */
}
