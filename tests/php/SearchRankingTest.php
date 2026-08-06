<?php
/**
 * Ranking a page's H2 sections against the query.
 *
 * The ordering rule is deliberate and worth pinning: a section touching *more of the
 * distinct words the reader typed* beats one that merely repeats a single word many times,
 * and a term in the heading is worth five in the body because the heading is what shows in
 * the result. Collapse those two passes into one raw frequency count and the top result
 * silently becomes the wrong section.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests;

use PHPUnit\Framework\Attributes\CoversFunction;
use WP_Post;

/**
 * @covers ::ucf_brand_find_matching_sections
 */
#[CoversFunction( 'ucf_brand_find_matching_sections' )]
final class SearchRankingTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->loadInclude( 'headings', 'search' );
	}

	/**
	 * @param string $content Stored post content.
	 * @return WP_Post
	 */
	private function post( $content ) {
		return new WP_Post( array( 'post_content' => $content ) );
	}

	/**
	 * Breadth beats depth: "Color" mentions both terms, "Logo Usage" only one, even
	 * though the latter has a heading hit.
	 *
	 * @return void
	 */
	public function test_more_distinct_terms_outranks_raw_frequency() {
		$post = $this->post(
			'<h2>Logo Usage</h2><p>Use the logo, the logo, and the logo.</p>'
			. '<h2>Color</h2><p>The logo and color palette.</p>'
		);

		$sections = ucf_brand_find_matching_sections( $post, array( 'logo', 'color' ) );

		$this->assertSame( 'Color', $sections[0]['title'] );
		$this->assertSame( 'Logo Usage', $sections[1]['title'] );
	}

	/**
	 * With breadth equal, a heading hit outweighs a body hit.
	 *
	 * @return void
	 */
	public function test_a_heading_hit_outweighs_a_body_hit() {
		$post = $this->post(
			'<h2>Typography</h2><p>Nothing relevant here.</p>'
			. '<h2>Basics</h2><p>Some typography guidance.</p>'
		);

		$sections = ucf_brand_find_matching_sections( $post, array( 'typography' ) );

		$this->assertSame( 'Typography', $sections[0]['title'] );
		$this->assertSame( 'Basics', $sections[1]['title'] );
	}

	/**
	 * @return void
	 */
	public function test_sections_with_no_hit_are_dropped() {
		$post = $this->post(
			'<h2>Logo</h2><p>About the logo.</p><h2>Unrelated</h2><p>Nothing here.</p>'
		);

		$sections = ucf_brand_find_matching_sections( $post, array( 'logo' ) );

		$this->assertCount( 1, $sections );
		$this->assertSame( 'Logo', $sections[0]['title'] );
	}

	/**
	 * @return void
	 */
	public function test_results_are_capped() {
		$post = $this->post(
			'<h2>One</h2><p>logo</p><h2>Two</h2><p>logo</p>'
			. '<h2>Three</h2><p>logo</p><h2>Four</h2><p>logo</p>'
		);

		$this->assertCount( 3, ucf_brand_find_matching_sections( $post, array( 'logo' ) ) );
		$this->assertCount( 1, ucf_brand_find_matching_sections( $post, array( 'logo' ), 1 ) );
	}

	/**
	 * The cap floors at one rather than returning nothing for a zero limit.
	 *
	 * @return void
	 */
	public function test_a_zero_limit_still_returns_one() {
		$post = $this->post( '<h2>One</h2><p>logo</p><h2>Two</h2><p>logo</p>' );

		$this->assertCount( 1, ucf_brand_find_matching_sections( $post, array( 'logo' ), 0 ) );
	}

	/**
	 * @return void
	 */
	public function test_no_terms_means_no_sections() {
		$post = $this->post( '<h2>Logo</h2><p>About the logo.</p>' );

		$this->assertSame( array(), ucf_brand_find_matching_sections( $post, array() ) );
	}

	/**
	 * Each result carries the anchor the page itself will have.
	 *
	 * @return void
	 */
	public function test_results_carry_the_page_anchor() {
		$post = $this->post( '<h2>Photography &amp; Video</h2><p>About logo work.</p>' );

		$sections = ucf_brand_find_matching_sections( $post, array( 'logo' ) );

		$this->assertSame( 'photography-video', $sections[0]['id'] );
	}
}
