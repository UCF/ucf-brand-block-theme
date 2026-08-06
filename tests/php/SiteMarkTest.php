<?php
/**
 * The header lockup block.
 *
 * Two things here are contracts rather than implementation details. The `<img alt="">` plus
 * the link's `aria-label` is what lets the wordmark be hidden on narrow screens without the
 * link going nameless — swap the alt back in and the control announces twice. And
 * `ucf_brand_site_mark_src` is the documented seam for replacing the logo without touching
 * a template or rebuilding, so it is covered as public API.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * @covers ::ucf_brand_site_mark_src
 * @covers ::ucf_brand_render_site_mark
 */
#[CoversFunction( 'ucf_brand_site_mark_src' )]
#[CoversFunction( 'ucf_brand_render_site_mark' )]
final class SiteMarkTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->loadInclude( 'branding' );

		Functions\when( 'get_theme_file_uri' )->alias(
			static function ( $path ) {
				return 'https://example.test/theme/' . $path;
			}
		);
	}

	/**
	 * @return void
	 */
	public function test_default_src_points_at_the_theme_asset() {
		$this->assertSame(
			'https://example.test/theme/assets/images/UCF-STACKED-BOLDGOLD.svg',
			ucf_brand_site_mark_src()
		);
	}

	/**
	 * The documented swap path: no template edit, no rebuild.
	 *
	 * @return void
	 */
	public function test_src_is_filterable() {
		Filters\expectApplied( 'ucf_brand_site_mark_src' )
			->once()
			->andReturn( 'https://cdn.example/custom-mark.svg' );

		$this->assertSame( 'https://cdn.example/custom-mark.svg', ucf_brand_site_mark_src() );
	}

	/**
	 * @return void
	 */
	public function test_renders_a_single_link_wrapping_both_halves() {
		$html = ucf_brand_render_site_mark(
			array(
				'url'   => 'https://www.ucf.edu',
				'label' => 'University of Central Florida',
			)
		);

		$this->assertSame( 1, substr_count( $html, '<a ' ), 'The lockup must be exactly one link.' );
		$this->assertStringContainsString( 'href="https://www.ucf.edu"', $html );
		$this->assertStringContainsString( 'aria-label="University of Central Florida"', $html );
		$this->assertStringContainsString( '<span class="brand-header__wordmark">University of Central Florida</span>', $html );
	}

	/**
	 * The image is decorative because the link is already named. If this becomes a real
	 * alt, the control announces its name twice.
	 *
	 * @return void
	 */
	public function test_the_mark_image_is_decorative() {
		$html = ucf_brand_render_site_mark( array() );

		$this->assertStringContainsString( 'alt=""', $html );
		$this->assertStringContainsString( 'assets/images/UCF-STACKED-BOLDGOLD.svg', $html );
	}

	/**
	 * No width/height is emitted, so a replacement of different proportions needs no
	 * change here or in CSS.
	 *
	 * @return void
	 */
	public function test_no_intrinsic_dimensions_are_emitted() {
		$html = ucf_brand_render_site_mark( array() );

		$this->assertStringNotContainsString( 'width=', $html );
		$this->assertStringNotContainsString( 'height=', $html );
	}

	/**
	 * Registered defaults apply when the block comment carries no attributes.
	 *
	 * @return void
	 */
	public function test_falls_back_to_the_documented_defaults() {
		$html = ucf_brand_render_site_mark( array() );

		$this->assertStringContainsString( 'href="https://www.ucf.edu"', $html );
		$this->assertStringContainsString( 'University of Central Florida', $html );
	}

	/**
	 * Filtering the src to '' drops the image and leaves the text lockup — the supported
	 * "wordmark only" configuration. The link must still be present and named.
	 *
	 * @return void
	 */
	public function test_an_empty_src_drops_the_image_but_keeps_the_link() {
		Filters\expectApplied( 'ucf_brand_site_mark_src' )->andReturn( '' );

		$html = ucf_brand_render_site_mark( array() );

		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringContainsString( 'brand-header__wordmark', $html );
		$this->assertStringContainsString( 'aria-label="University of Central Florida"', $html );
	}

	/**
	 * Attributes reach the output escaped — they are authorable from the template part.
	 *
	 * @return void
	 */
	public function test_attributes_are_escaped() {
		$html = ucf_brand_render_site_mark(
			array(
				'url'   => 'https://example.test/?a=1&b=2',
				'label' => 'Tom & "Jerry" <script>',
			)
		);

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&amp;', $html );
		$this->assertStringNotContainsString( '"Jerry"', $html );
	}
}
