<?php
/**
 * The University Header integration.
 *
 * Everything here is a contract with a host this theme does not control, and each one fails
 * silently when broken: the bar renders either way, just boxed at 940px instead of full
 * width, or in a spot nothing chose. That is the whole reason these are tested — a page
 * capture cannot tell the two apart, and neither can a reviewer.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

/**
 * @covers ::ucf_brand_university_header_src
 * @covers ::ucf_brand_enqueue_university_header
 * @covers ::ucf_brand_university_header_script_tag
 * @covers ::ucf_brand_university_header_placeholder
 */
final class UniversityHeaderTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->loadInclude( 'university-header' );
	}

	/**
	 * The query string is what selects the full-width build on the host *and* what that
	 * build reads back out of its own src. Losing it is the silent failure.
	 *
	 * @return void
	 */
	public function test_src_requests_the_full_width_build_over_https() {
		$this->assertSame(
			'https://universityheader.ucf.edu/bar/js/university-header.js?use-full-width=1',
			ucf_brand_university_header_src()
		);
	}

	/**
	 * The seam for the other documented options, without editing the theme.
	 *
	 * @return void
	 */
	public function test_src_is_filterable() {
		Filters\expectApplied( 'ucf_brand_university_header_src' )
			->once()
			->andReturn( 'https://universityheader.ucf.edu/bar/js/university-header.js?use-1200-breakpoint=1' );

		$this->assertSame(
			'https://universityheader.ucf.edu/bar/js/university-header.js?use-1200-breakpoint=1',
			ucf_brand_university_header_src()
		);
	}

	/**
	 * In the head, and with no version appended — `?ver=` would put this site's version
	 * on someone else's asset, and the header is versioned by its own server.
	 *
	 * @return void
	 */
	public function test_enqueues_into_the_head_unversioned() {
		$call = null;

		Functions\when( 'wp_enqueue_script' )->alias(
			static function ( ...$args ) use ( &$call ) {
				$call = $args;
			}
		);

		ucf_brand_enqueue_university_header();

		$this->assertSame(
			array(
				UCF_BRAND_UNIVERSITY_HEADER_HANDLE,
				'https://universityheader.ucf.edu/bar/js/university-header.js?use-full-width=1',
				array(),
				null,
				false,
			),
			$call
		);
	}

	/**
	 * Without the id the script cannot find itself, and every parameter-based option is
	 * ignored. The id core generates is `{handle}-js`.
	 *
	 * @return void
	 */
	public function test_the_script_tag_is_given_the_id_the_header_looks_itself_up_by() {
		$handle = UCF_BRAND_UNIVERSITY_HEADER_HANDLE;
		$src    = ucf_brand_university_header_src();
		$tag    = sprintf(
			'<script type="text/javascript" src="%s" id="%s-js"></script>' . "\n",
			$src,
			$handle
		);

		$filtered = ucf_brand_university_header_script_tag( $tag, $handle, $src );

		$this->assertStringContainsString( 'id="ucfhb-script"', $filtered );
		$this->assertStringNotContainsString( "{$handle}-js", $filtered );
	}

	/**
	 * WordPress has printed the id in single quotes in living memory, so the rewrite must
	 * not depend on which it gets.
	 *
	 * @return void
	 */
	public function test_the_id_is_rewritten_whichever_quotes_core_used() {
		$handle = UCF_BRAND_UNIVERSITY_HEADER_HANDLE;
		$src    = ucf_brand_university_header_src();
		$tag    = sprintf( "<script src='%s' id='%s-js'></script>", $src, $handle );

		$this->assertStringContainsString(
			"id='ucfhb-script'",
			ucf_brand_university_header_script_tag( $tag, $handle, $src )
		);
	}

	/**
	 * The filter runs against every script on the page. Anything else must come back
	 * byte-for-byte — a stray rewrite here would break an unrelated handle.
	 *
	 * @return void
	 */
	public function test_other_scripts_are_left_alone() {
		$tag = '<script src="https://example.test/other.js" id="other-js"></script>';

		$this->assertSame(
			$tag,
			ucf_brand_university_header_script_tag( $tag, 'other', 'https://example.test/other.js' )
		);
	}

	/**
	 * The placeholder is what stops the script choosing its own insertion point. It is an
	 * empty div by contract: UCF's terms are that nothing else may ride in it.
	 *
	 * @return void
	 */
	public function test_the_placeholder_is_an_empty_div_with_the_expected_id() {
		ob_start();
		ucf_brand_university_header_placeholder();
		$html = ob_get_clean();

		$this->assertSame( '<div id="ucfhb"></div>', $html );
	}
}
