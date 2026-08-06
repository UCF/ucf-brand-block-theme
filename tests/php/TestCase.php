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
		$this->stubWordPress();
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

	/**
	 * Stub the WordPress functions the covered code calls.
	 *
	 * These mirror core's real behavior rather than returning the input untouched,
	 * because the behavior is load-bearing in the code under test:
	 *
	 * - `esc_html()` must NOT double-encode. Core's `_wp_specialchars()` defaults
	 *   `$double_encode` to false, so `&amp;` stays `&amp;`. Highlighting escapes each
	 *   run between matches separately, and a stub that double-encoded would make those
	 *   assertions pass against behavior WordPress does not have.
	 * - `wp_strip_all_tags()` strips script/style bodies before `strip_tags()` and trims.
	 *   Heading slugs and section text both run through it.
	 *
	 * @return void
	 */
	protected function stubWordPress() {
		Monkey\Functions\when( 'esc_html' )->alias(
			static function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false );
			}
		);

		Monkey\Functions\when( 'esc_attr' )->alias(
			static function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false );
			}
		);

		// Core's esc_url does a great deal more (protocol allowlisting, entity fixing).
		// Only the ampersand rewrite is observable in the markup these tests assert on.
		Monkey\Functions\when( 'esc_url' )->alias(
			static function ( $url ) {
				return str_replace( '&', '&#038;', (string) $url );
			}
		);

		Monkey\Functions\when( 'wp_strip_all_tags' )->alias(
			static function ( $text, $remove_breaks = false ) {
				if ( ! is_scalar( $text ) ) {
					return '';
				}

				$text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $text );
				$text = strip_tags( $text );

				if ( $remove_breaks ) {
					$text = preg_replace( '/[\r\n\t ]+/', ' ', $text );
				}

				return trim( $text );
			}
		);

		// Translation is identity here; the suite asserts on English output.
		Monkey\Functions\when( '__' )->returnArg( 1 );
		Monkey\Functions\when( 'esc_attr__' )->returnArg( 1 );
		Monkey\Functions\when( 'esc_html__' )->returnArg( 1 );
	}
}
