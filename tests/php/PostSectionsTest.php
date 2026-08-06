<?php
/**
 * Splitting stored page content into its H2 sections.
 *
 * `ucf_brand_get_post_sections()` is what search uses to build `/page/#heading` deep links.
 * It reads `post_content` rather than rendering it, and reuses the same two slug helpers in
 * the same order as `ucf_brand_add_heading_anchor()` — that ordering is the only reason the
 * ids it returns match the ids actually on the page. These tests pin that agreement.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests;

use PHPUnit\Framework\Attributes\CoversFunction;
use WP_Post;

/**
 * @covers ::ucf_brand_get_post_sections
 */
#[CoversFunction( 'ucf_brand_get_post_sections' )]
final class PostSectionsTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->loadInclude( 'headings' );
	}

	/**
	 * Build a post from block markup.
	 *
	 * @param string $content Stored post content.
	 * @return WP_Post
	 */
	private function post( $content ) {
		return new WP_Post( array( 'post_content' => $content ) );
	}

	/**
	 * @return void
	 */
	public function test_returns_id_title_and_text_per_section() {
		$sections = ucf_brand_get_post_sections(
			$this->post(
				'<!-- wp:heading --><h2 class="wp-block-heading">Clear Space</h2><!-- /wp:heading -->'
				. '<!-- wp:paragraph --><p>Leave room around the mark.</p><!-- /wp:paragraph -->'
				. '<!-- wp:heading --><h2 class="wp-block-heading">Minimum Size</h2><!-- /wp:heading -->'
				. '<!-- wp:paragraph --><p>Never below 24px.</p><!-- /wp:paragraph -->'
			)
		);

		$this->assertCount( 2, $sections );

		$this->assertSame( 'clear-space', $sections[0]['id'] );
		$this->assertSame( 'Clear Space', $sections[0]['title'] );
		$this->assertStringContainsString( 'Leave room around the mark.', $sections[0]['text'] );

		$this->assertSame( 'minimum-size', $sections[1]['id'] );
		$this->assertSame( 'Minimum Size', $sections[1]['title'] );
	}

	/**
	 * Block delimiters are HTML comments, and strip_tags() drops those along with the tags.
	 * Without that, every section's text would carry `wp-block-*` class names and a search
	 * for "block" would match every page on the site.
	 *
	 * @return void
	 */
	public function test_section_text_excludes_block_delimiters_and_classes() {
		$sections = ucf_brand_get_post_sections(
			$this->post(
				'<!-- wp:heading --><h2>Voice</h2><!-- /wp:heading -->'
				. '<!-- wp:paragraph {"className":"lead"} --><p class="lead">Confident.</p><!-- /wp:paragraph -->'
			)
		);

		$this->assertSame( 'Confident.', $sections[0]['text'] );
		$this->assertStringNotContainsString( 'wp:paragraph', $sections[0]['text'] );
		$this->assertStringNotContainsString( 'className', $sections[0]['text'] );
		$this->assertStringNotContainsString( 'lead', $sections[0]['text'] );
	}

	/**
	 * An id typed into the block's Advanced panel wins, exactly as it does on render.
	 *
	 * @return void
	 */
	public function test_author_set_anchor_wins() {
		$sections = ucf_brand_get_post_sections(
			$this->post( '<h2 id="legacy-anchor">Clear Space</h2><p>Body.</p>' )
		);

		$this->assertSame( 'legacy-anchor', $sections[0]['id'] );
	}

	/**
	 * An author-set id joins the used registry, so a later generated slug cannot collide
	 * with it.
	 *
	 * @return void
	 */
	public function test_author_set_anchor_reserves_the_slug() {
		$sections = ucf_brand_get_post_sections(
			$this->post( '<h2 id="voice">First</h2><p>a</p><h2>Voice</h2><p>b</p>' )
		);

		$this->assertSame( 'voice', $sections[0]['id'] );
		$this->assertSame( 'voice-2', $sections[1]['id'] );
	}

	/**
	 * @return void
	 */
	public function test_duplicate_headings_get_distinct_anchors() {
		$sections = ucf_brand_get_post_sections(
			$this->post( '<h2>Overview</h2><p>a</p><h2>Overview</h2><p>b</p>' )
		);

		$this->assertSame( 'overview', $sections[0]['id'] );
		$this->assertSame( 'overview-2', $sections[1]['id'] );
	}

	/**
	 * Content before the first H2 is not a section.
	 *
	 * @return void
	 */
	public function test_intro_copy_before_the_first_heading_is_skipped() {
		$sections = ucf_brand_get_post_sections(
			$this->post( '<p>Intro paragraph.</p><h2>Only Section</h2><p>Body.</p>' )
		);

		$this->assertCount( 1, $sections );
		$this->assertSame( 'Only Section', $sections[0]['title'] );
		$this->assertStringNotContainsString( 'Intro paragraph', $sections[0]['text'] );
	}

	/**
	 * @return void
	 */
	public function test_entities_and_markup_in_the_heading_are_resolved() {
		$sections = ucf_brand_get_post_sections(
			$this->post( '<h2>Photography &amp; <em>Video</em></h2><p>Body.</p>' )
		);

		$this->assertSame( 'Photography & Video', $sections[0]['title'] );
		$this->assertSame( 'photography-video', $sections[0]['id'] );
	}

	/**
	 * An H2 with no text has nothing to link to and no name to show.
	 *
	 * @return void
	 */
	public function test_empty_heading_is_skipped() {
		$sections = ucf_brand_get_post_sections(
			$this->post( '<h2>  </h2><p>orphan</p><h2>Real</h2><p>body</p>' )
		);

		$this->assertCount( 1, $sections );
		$this->assertSame( 'Real', $sections[0]['title'] );
	}

	/**
	 * A final heading with no body still counts — it is a jump target.
	 *
	 * @return void
	 */
	public function test_trailing_heading_without_body_is_kept() {
		$sections = ucf_brand_get_post_sections( $this->post( '<h2>Resources</h2>' ) );

		$this->assertCount( 1, $sections );
		$this->assertSame( 'resources', $sections[0]['id'] );
		$this->assertSame( '', $sections[0]['text'] );
	}

	/**
	 * @return void
	 */
	public function test_content_without_headings_returns_nothing() {
		$this->assertSame( array(), ucf_brand_get_post_sections( $this->post( '<p>Just copy.</p>' ) ) );
	}

	/**
	 * @return void
	 */
	public function test_empty_or_non_post_input_returns_nothing() {
		$this->assertSame( array(), ucf_brand_get_post_sections( $this->post( '   ' ) ) );
		$this->assertSame( array(), ucf_brand_get_post_sections( null ) );
		$this->assertSame( array(), ucf_brand_get_post_sections( 'not a post' ) );
	}

	/**
	 * H3s are not structural — only H2s drive the drawer and the deep links.
	 *
	 * @return void
	 */
	public function test_h3_headings_are_not_sections() {
		$sections = ucf_brand_get_post_sections(
			$this->post( '<h2>Section</h2><p>a</p><h3>Subheading</h3><p>b</p>' )
		);

		$this->assertCount( 1, $sections );
		$this->assertStringContainsString( 'Subheading', $sections[0]['text'] );
	}
}
