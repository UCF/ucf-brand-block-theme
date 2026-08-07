<?php
/**
 * Heading slugs — the anchor ids every H2 deep link resolves against.
 *
 * These are the highest-value assertions in the suite. `ucf_brand_heading_slug()` is a
 * step-for-step port of the `slugify()` that used to run in brand-nav.js, and
 * docs/architecture.md is explicit that it must NOT become `sanitize_title()`: core
 * transliterates accents and handles entities differently, which once made the browser and
 * PHP disagree and silently landed every shared deep link at the top of the page.
 *
 * So these tests pin the port's quirks on purpose — dropped (not transliterated) non-ASCII,
 * a preserved trailing hyphen, `-2` as the first collision suffix. Each one looks like
 * something a tidy-minded refactor would "fix". Failing here means anchors people have
 * already bookmarked stopped resolving.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests;

/**
 * @covers ::ucf_brand_heading_slug
 * @covers ::ucf_brand_unique_heading_slug
 */
final class HeadingSlugTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->loadInclude( 'headings' );
	}

	/**
	 * @param string $input    Raw heading text.
	 * @param string $expected Slug.
	 * @return void
	 * @dataProvider slugCases
	 */
	public function test_heading_slug( $input, $expected ) {
		$this->assertSame( $expected, ucf_brand_heading_slug( $input ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function slugCases() {
		return array(
			'lowercases and hyphenates' => array( 'Clear Space', 'clear-space' ),
			'trims surrounding space'   => array( '   Voice   ', 'voice' ),
			'collapses internal runs'   => array( "Logo\t\n  Usage", 'logo-usage' ),
			'collapses hyphen runs'     => array( 'Co--Branding', 'co-branding' ),

			// The case that broke deep links: an ampersand entity in editor-authored copy.
			'decodes entities'          => array( 'Photography &amp; Video', 'photography-video' ),
			'strips markup'             => array( '<strong>Bold</strong> Gold', 'bold-gold' ),

			// JS `\s` matches U+00A0; PCRE's does not without /u, hence the explicit
			// normalization in the port. A regression turns this into "clearspace".
			'treats nbsp as whitespace' => array( "Clear\xc2\xa0Space", 'clear-space' ),

			// Dropped, NOT transliterated. sanitize_title() would yield "cafe-standards".
			'drops non-ascii'           => array( 'Café Standards', 'caf-standards' ),

			// Preserved because that is what the browser produced.
			'keeps a trailing hyphen'   => array( 'Overview -', 'overview-' ),

			'drops punctuation'         => array( 'Do’s & Don’ts!', 'dos-donts' ),
			'digits survive'            => array( 'Section 01 Basics', 'section-01-basics' ),
			'symbol-only text is empty' => array( '!!!', '' ),
			'empty string stays empty'  => array( '', '' ),
		);
	}

	/**
	 * The first duplicate is `-2`, not `-1`. Mirrors the browser's old behavior so a page
	 * with two "Overview" H2s keeps the anchors it already had.
	 *
	 * @return void
	 */
	public function test_unique_slug_numbers_duplicates_from_two() {
		$used = array();

		$this->assertSame( 'overview', ucf_brand_unique_heading_slug( 'overview', $used ) );
		$this->assertSame( 'overview-2', ucf_brand_unique_heading_slug( 'overview', $used ) );
		$this->assertSame( 'overview-3', ucf_brand_unique_heading_slug( 'overview', $used ) );
	}

	/**
	 * @return void
	 */
	public function test_unique_slug_appends_to_the_registry() {
		$used = array( 'voice' );

		ucf_brand_unique_heading_slug( 'voice', $used );

		$this->assertSame( array( 'voice', 'voice-2' ), $used );
	}

	/**
	 * A slug that already ends in a number must not collide with a generated suffix.
	 *
	 * @return void
	 */
	public function test_unique_slug_skips_an_occupied_suffix() {
		$used = array( 'logo', 'logo-2' );

		$this->assertSame( 'logo-3', ucf_brand_unique_heading_slug( 'logo', $used ) );
	}
}
