<?php
/**
 * The on-page index block.
 *
 * `ucf_brand_render_section_index()` is covered together with `ucf_brand_get_post_sections()`
 * rather than against a mocked section list, because "the entries are the page's own H2s, in
 * the page's own order, under the page's own anchors" is the whole behavior — a mock at that
 * boundary would assert only that the renderer loops.
 *
 * The markup assertions are not incidental: `_index.scss` styles these exact classes, and the
 * anchors are what makes the block a jump list rather than a caption.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests;

use Brain\Monkey\Functions;
use WP_Block;
use WP_Post;

/**
 * @covers ::ucf_brand_render_section_index
 */
final class SectionIndexTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->loadInclude( 'sections', 'headings', 'section-index' );
	}

	/**
	 * Serve one page to the renderer.
	 *
	 * @param string $content Stored post content.
	 * @param mixed  $number  The page's `ucf_brand_number` meta value.
	 * @return void
	 */
	private function seedPage( $content, $number = 2 ) {
		Functions\when( 'get_post' )->justReturn( new WP_Post( array( 'post_content' => $content ) ) );
		Functions\when( 'get_post_meta' )->justReturn( $number );
		Functions\when( 'get_queried_object_id' )->justReturn( 12 );
		Functions\when( 'wp_unique_id' )->justReturn( 'brand-index-title-1' );
		// A pass-through, and deliberately not an assertion target: `wp_kses()` is the
		// sanitizer, so a fake one here would only test the fake. What it actually strips is
		// covered against real WordPress in tests/integration/SectionIndexTest.php.
		Functions\when( 'wp_kses' )->alias(
			static function ( $html ) {
				return $html;
			}
		);
		Functions\when( 'get_block_wrapper_attributes' )->alias(
			static function ( $extra = array() ) {
				return 'class="wp-block-ucf-brand-section-index ' . $extra['class'] . '"';
			}
		);
	}

	/**
	 * Two H2s and the copy between them.
	 *
	 * @return string Stored post content.
	 */
	private function twoSections() {
		return '<!-- wp:heading --><h2 class="wp-block-heading">Brand Voice</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>How we sound.</p><!-- /wp:paragraph -->'
			. '<!-- wp:heading --><h2 class="wp-block-heading">Brand Messages</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>What we say.</p><!-- /wp:paragraph -->';
	}

	/**
	 * @return void
	 */
	public function test_lists_every_h2_in_order_with_its_anchor() {
		$this->seedPage( $this->twoSections() );

		$html = ucf_brand_render_section_index();

		$this->assertStringContainsString( 'href="#brand-voice"', $html );
		$this->assertStringContainsString( 'href="#brand-messages"', $html );
		$this->assertLessThan(
			strpos( $html, 'brand-messages' ),
			strpos( $html, 'brand-voice' ),
			'Entries must render in the order the H2s appear on the page.'
		);
	}

	/**
	 * The index and the H2 badge print the same "page.subsection" pair — see
	 * `--brand-section` in _sections.scss.
	 *
	 * @return void
	 */
	public function test_numbers_entries_from_the_pages_brand_number() {
		$this->seedPage( $this->twoSections(), 2 );

		$html = ucf_brand_render_section_index();

		$this->assertStringContainsString( '>2.1</span>', $html );
		$this->assertStringContainsString( '>2.2</span>', $html );
	}

	/**
	 * @return void
	 */
	public function test_drops_the_number_column_on_an_unnumbered_page() {
		$this->seedPage( $this->twoSections(), 0 );

		$html = ucf_brand_render_section_index();

		$this->assertStringNotContainsString( 'brand-index__num', $html );
		$this->assertStringContainsString( 'Brand Voice', $html );
	}

	/**
	 * Descriptions are keyed by heading text, so an entry keeps its own description when the
	 * page is reordered — the property that makes position-keyed storage the wrong choice.
	 *
	 * @return void
	 */
	public function test_matches_descriptions_to_headings_by_title() {
		$this->seedPage(
			'<!-- wp:heading --><h2>Brand Messages</h2><!-- /wp:heading --><p>What we say.</p>'
			. '<!-- wp:heading --><h2>Brand Voice</h2><!-- /wp:heading --><p>How we sound.</p>'
		);

		$html = ucf_brand_render_section_index(
			array(
				'descriptions' => array(
					'Brand Voice'    => 'The characteristics of how we sound.',
					'Brand Messages' => 'Key phrases that highlight our impact.',
				),
			)
		);

		$this->assertMatchesRegularExpression(
			'#Brand Messages</span></a><p[^>]*>Key phrases that highlight our impact\.#',
			$html
		);
		$this->assertMatchesRegularExpression(
			'#Brand Voice</span></a><p[^>]*>The characteristics of how we sound\.#',
			$html
		);
	}

	/**
	 * @return void
	 */
	public function test_renders_an_entry_with_no_description() {
		$this->seedPage( $this->twoSections() );

		$html = ucf_brand_render_section_index(
			array( 'descriptions' => array( 'Brand Voice' => 'How we sound.' ) )
		);

		$this->assertSame( 1, substr_count( $html, 'brand-index__desc' ) );
		$this->assertSame( 2, substr_count( $html, 'brand-index__item' ) );
	}

	/**
	 * @return void
	 */
	public function test_names_the_nav_after_the_lead_in_heading() {
		$this->seedPage( $this->twoSections() );

		$html = ucf_brand_render_section_index( array( 'heading' => 'Writing Elements' ) );

		$this->assertStringContainsString( 'aria-labelledby="brand-index-title-1"', $html );
		$this->assertStringContainsString( 'id="brand-index-title-1"', $html );
		$this->assertStringNotContainsString( 'aria-label=', $html );
	}

	/**
	 * A11Y: a nav landmark with no name is indistinguishable from the drawer's.
	 *
	 * @return void
	 */
	public function test_falls_back_to_a_label_with_no_lead_in_heading() {
		$this->seedPage( $this->twoSections() );

		$html = ucf_brand_render_section_index();

		$this->assertStringContainsString( 'aria-label="On this page"', $html );
		$this->assertStringNotContainsString( '<h3', $html );
	}

	/**
	 * @return void
	 */
	public function test_renders_nothing_on_a_page_with_no_h2s() {
		$this->seedPage( '<!-- wp:paragraph --><p>Prose only.</p><!-- /wp:paragraph -->' );

		$this->assertSame( '', ucf_brand_render_section_index() );
	}

	/**
	 * The block's `postId` context wins over the queried object, which is what makes the
	 * editor preview and a query loop render the right page's headings.
	 *
	 * @return void
	 */
	public function test_prefers_the_blocks_post_id_context() {
		$this->seedPage( $this->twoSections() );

		$seen = null;

		Functions\when( 'get_post' )->alias(
			static function ( $post_id ) use ( &$seen ) {
				$seen = $post_id;

				return new WP_Post( array( 'post_content' => '' ) );
			}
		);

		ucf_brand_render_section_index( array(), '', new WP_Block( array( 'postId' => 44 ) ) );

		$this->assertSame( 44, $seen );
	}

	/**
	 * @return void
	 */
	public function test_escapes_a_heading_that_carries_markup() {
		$this->seedPage(
			'<!-- wp:heading --><h2>Logo <script>alert(1)</script></h2><!-- /wp:heading --><p>Copy.</p>'
		);

		$html = ucf_brand_render_section_index();

		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringContainsString( '>Logo</span>', $html );
	}
}
