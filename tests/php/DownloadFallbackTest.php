<?php
/**
 * Stand-ins for Download Monitor's shortcodes.
 *
 * Two behaviours matter here and neither is visible from reading the function. The plugin
 * has to win when it is installed — the fallbacks are registered late for exactly that
 * reason, and a stand-in that hijacked `[download]` would replace working downloads with a
 * notice. And when the plugin is gone the public must see nothing, because the failure this
 * guards against is `[download id="12"]` printed at visitors as literal text.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests;

use Brain\Monkey\Functions;

/**
 * @covers ::ucf_brand_register_download_fallbacks
 * @covers ::ucf_brand_download_fallback
 */
final class DownloadFallbackTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->loadInclude( 'download-monitor' );
	}

	/**
	 * Both shortcodes the theme renders are covered, not just `[download]`. The gate page
	 * carries `[dlm_no_access]`, and that page stays published when the plugin goes away.
	 *
	 * @return void
	 */
	public function test_registers_a_stand_in_for_each_shortcode_when_the_plugin_is_absent() {
		Functions\when( 'shortcode_exists' )->justReturn( false );

		$registered = array();
		Functions\when( 'add_shortcode' )->alias(
			function ( $tag, $callback ) use ( &$registered ) {
				$registered[ $tag ] = $callback;
			}
		);

		ucf_brand_register_download_fallbacks();

		$this->assertSame( array( 'download', 'dlm_no_access' ), array_keys( $registered ) );
	}

	/**
	 * The one that would do damage. `shortcode_exists()` only tells the truth after every
	 * plugin has registered on the default init priority, which is why the hook runs at 99 —
	 * register earlier, or skip this check, and the theme replaces every working download
	 * button on the site with a notice.
	 *
	 * @return void
	 */
	public function test_leaves_the_plugins_own_shortcodes_alone() {
		Functions\when( 'shortcode_exists' )->justReturn( true );

		$registered = array();
		Functions\when( 'add_shortcode' )->alias(
			function ( $tag ) use ( &$registered ) {
				$registered[] = $tag;
			}
		);

		ucf_brand_register_download_fallbacks();

		$this->assertSame( array(), $registered );
	}

	/**
	 * @return void
	 */
	public function test_renders_nothing_for_a_visitor() {
		Functions\when( 'current_user_can' )->justReturn( false );

		$this->assertSame( '', ucf_brand_download_fallback() );
	}

	/**
	 * Silence for the public is the point, but a failure nobody can see is how a missing
	 * download survives a content review, so an editor gets told.
	 *
	 * @return void
	 */
	public function test_tells_an_editor_why_the_download_is_missing() {
		Functions\when( 'current_user_can' )->justReturn( true );

		$output = ucf_brand_download_fallback();

		$this->assertStringContainsString( 'ucf-download-missing', $output );
		$this->assertStringContainsString( 'Download unavailable', $output );
	}
}
