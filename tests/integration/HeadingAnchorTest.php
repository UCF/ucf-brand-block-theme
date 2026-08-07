<?php
/**
 * H2 anchors, through the real render_block pipeline.
 *
 * `ucf_brand_add_heading_anchor()` cannot be tested honestly without WordPress: it is a
 * `render_block` filter, it drives WP_HTML_Tag_Processor, and its used-slug registry is
 * `static` state keyed on `get_the_ID()`. The unit suite covers the two slug helpers it calls;
 * this covers the filter itself doing its job when a page actually renders.
 *
 * These anchors are load-bearing. The drawer sub-nav, the copy-link affordance and search's
 * deep links all address H2s by `id`, and docs/architecture.md records what happened the last
 * time two implementations of the slug disagreed: every shared `/page/#heading` link silently
 * landed at the top of the page.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests\Integration;

use WP_UnitTestCase;

/**
 * @covers ::ucf_brand_add_heading_anchor
 */
final class HeadingAnchorTest extends WP_UnitTestCase {

	/**
	 * Render post content through the real block pipeline, in a post context.
	 *
	 * `do_blocks()` is what runs `render_block`, and the filter reads `get_the_ID()` to decide
	 * whether its slug registry belongs to a new page — so the global post has to be set up
	 * the way a real render would leave it.
	 *
	 * @param string $content Block markup.
	 * @return string Rendered HTML.
	 */
	private function render_page( $content ) {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => $content,
			)
		);

		$this->go_to( get_permalink( $post_id ) );
		the_post();

		return do_blocks( get_post_field( 'post_content', $post_id ) );
	}

	/**
	 * @return void
	 */
	public function test_h2_gets_an_id_derived_from_its_text() {
		$html = $this->render_page(
			'<!-- wp:heading --><h2 class="wp-block-heading">Clear Space</h2><!-- /wp:heading -->'
		);

		$this->assertStringContainsString( 'id="clear-space"', $html );
	}

	/**
	 * The entity case that broke deep links once already.
	 *
	 * @return void
	 */
	public function test_entities_in_the_heading_resolve_to_one_slug() {
		$html = $this->render_page(
			'<!-- wp:heading --><h2>Photography &amp; Video</h2><!-- /wp:heading -->'
		);

		$this->assertStringContainsString( 'id="photography-video"', $html );
	}

	/**
	 * An id typed into the block's Advanced panel wins; the filter only fills the gap.
	 *
	 * @return void
	 */
	public function test_an_author_set_id_is_left_alone() {
		$html = $this->render_page(
			'<!-- wp:heading {"anchor":"legacy-anchor"} --><h2 id="legacy-anchor">Clear Space</h2><!-- /wp:heading -->'
		);

		$this->assertStringContainsString( 'id="legacy-anchor"', $html );
		$this->assertStringNotContainsString( 'id="clear-space"', $html );
	}

	/**
	 * @return void
	 */
	public function test_duplicate_headings_get_distinct_ids() {
		$html = $this->render_page(
			'<!-- wp:heading --><h2>Overview</h2><!-- /wp:heading -->'
			. '<!-- wp:heading --><h2>Overview</h2><!-- /wp:heading -->'
		);

		$this->assertStringContainsString( 'id="overview"', $html );
		$this->assertStringContainsString( 'id="overview-2"', $html );
	}

	/**
	 * H3 is the escape hatch for a subheading that must not appear in the drawer, so it must
	 * not be given an anchor by this filter.
	 *
	 * @return void
	 */
	public function test_h3_is_left_alone() {
		$html = $this->render_page(
			'<!-- wp:heading {"level":3} --><h3>Not Structural</h3><!-- /wp:heading -->'
		);

		$this->assertStringNotContainsString( 'id="not-structural"', $html );
	}

	/**
	 * A heading with no sluggable text still needs an anchor to link to.
	 *
	 * @return void
	 */
	public function test_a_symbol_only_heading_falls_back_to_section() {
		$html = $this->render_page( '<!-- wp:heading --><h2>!!!</h2><!-- /wp:heading -->' );

		$this->assertStringContainsString( 'id="section"', $html );
	}

	/**
	 * The registry is `static`, so without the post-id reset the second page rendered in a
	 * request would start numbering from where the first left off — every anchor on it
	 * suffixed, and every link to it broken. This is the test that pins that reset.
	 *
	 * @return void
	 */
	public function test_the_slug_registry_resets_between_posts() {
		$first  = $this->render_page( '<!-- wp:heading --><h2>Overview</h2><!-- /wp:heading -->' );
		$second = $this->render_page( '<!-- wp:heading --><h2>Overview</h2><!-- /wp:heading -->' );

		$this->assertStringContainsString( 'id="overview"', $first );
		$this->assertStringContainsString( 'id="overview"', $second );
		$this->assertStringNotContainsString( 'overview-2', $second );
	}

	/**
	 * The ids search emits must equal the ids the page carries. Two implementations of one
	 * slug is exactly the bug docs/architecture.md warns about, so this asserts the two
	 * agree rather than asserting each is individually plausible.
	 *
	 * @return void
	 */
	public function test_rendered_ids_match_what_search_derives() {
		$content = '<!-- wp:heading --><h2>Photography &amp; Video</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>Body.</p><!-- /wp:paragraph -->'
			. '<!-- wp:heading --><h2>Overview</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>More.</p><!-- /wp:paragraph -->'
			. '<!-- wp:heading --><h2>Overview</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>Again.</p><!-- /wp:paragraph -->';

		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => $content,
			)
		);

		$this->go_to( get_permalink( $post_id ) );
		the_post();

		$rendered = do_blocks( $content );

		foreach ( ucf_brand_get_post_sections( get_post( $post_id ) ) as $section ) {
			$this->assertStringContainsString(
				'id="' . $section['id'] . '"',
				$rendered,
				"Search derived the anchor '{$section['id']}', which is not on the rendered page."
			);
		}
	}
}
