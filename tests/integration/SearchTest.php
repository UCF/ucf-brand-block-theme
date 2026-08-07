<?php
/**
 * Search scoping and the subsection deep links, against a real query.
 *
 * The unit suite covers the pure halves of search — term parsing, matching, highlighting,
 * snippet windowing, ranking. None of that can tell you whether the `pre_get_posts` filter
 * actually narrows the main query, or whether the subsections block renders inside a real
 * search loop. Those are what this file covers.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests\Integration;

use WP_UnitTestCase;

/**
 * @covers ::ucf_brand_limit_main_search_to_pages
 * @covers ::ucf_brand_render_search_subsections
 */
final class SearchTest extends WP_UnitTestCase {

	/**
	 * Page content with two H2 sections, one of which mentions the search term.
	 */
	const PAGE_CONTENT = '<!-- wp:heading --><h2>Clear Space</h2><!-- /wp:heading -->'
		. '<!-- wp:paragraph --><p>Leave room around the pegasus mark on every layout.</p><!-- /wp:paragraph -->'
		. '<!-- wp:heading --><h2>Minimum Size</h2><!-- /wp:heading -->'
		. '<!-- wp:paragraph --><p>Never reproduce it below twenty four pixels.</p><!-- /wp:paragraph -->';

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		update_option( 'permalink_structure', '/%postname%/' );
	}

	/**
	 * A hand-typed or shared `?s=` link must resolve the same way the sidebar's Search block
	 * does, which posts `post_type=page` explicitly.
	 *
	 * @return void
	 */
	public function test_the_main_search_query_is_narrowed_to_pages() {
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Pegasus Page',
				'post_content' => 'About the pegasus.',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => 'Pegasus Post',
				'post_content' => 'Also about the pegasus.',
			)
		);

		$this->go_to( home_url( '/?s=pegasus' ) );

		$this->assertTrue( is_search() );
		$this->assertSame( 'page', $GLOBALS['wp_query']->get( 'post_type' ) );

		$titles = wp_list_pluck( $GLOBALS['wp_query']->posts, 'post_title' );

		$this->assertContains( 'Pegasus Page', $titles );
		$this->assertNotContains( 'Pegasus Post', $titles );
	}

	/**
	 * Admin and secondary queries must be untouched — the filter guards on both, and losing
	 * either guard would quietly reshape queries all over the site.
	 *
	 * @return void
	 */
	public function test_secondary_queries_are_untouched() {
		self::factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => 'Pegasus Post',
				'post_content' => 'About the pegasus.',
			)
		);

		$secondary = new \WP_Query(
			array(
				's'         => 'pegasus',
				'post_type' => 'post',
			)
		);

		$this->assertNotEmpty( $secondary->posts );
		$this->assertSame( 'Pegasus Post', $secondary->posts[0]->post_title );
	}

	/**
	 * Render the subsections block the way a search results template would: inside the loop,
	 * on a real search request.
	 *
	 * @param string $term Search term.
	 * @return string Rendered block output for the first result.
	 */
	private function render_subsections_for( $term ) {
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Logo Usage',
				'post_content' => self::PAGE_CONTENT,
			)
		);

		$this->go_to( home_url( '/?s=' . rawurlencode( $term ) ) );

		$this->assertTrue( have_posts(), "Nothing matched the search for '{$term}'." );
		the_post();

		return do_blocks( '<!-- wp:ucf-brand/search-subsections /-->' );
	}

	/**
	 * @return void
	 */
	public function test_renders_deep_links_to_the_matching_section() {
		$html = $this->render_subsections_for( 'pegasus' );

		$this->assertStringContainsString( 'brand-search__list', $html );
		$this->assertStringContainsString( '#clear-space', $html );

		// The section that does not mention the term is not a useful jump target.
		$this->assertStringNotContainsString( '#minimum-size', $html );
	}

	/**
	 * The deep link has to be a real permalink plus the anchor the page actually carries —
	 * the whole feature is worthless if it lands at the top of the page.
	 *
	 * @return void
	 */
	public function test_the_deep_link_resolves_to_a_real_anchor_on_the_page() {
		$html = $this->render_subsections_for( 'pegasus' );

		$post_id  = get_the_ID();
		$expected = get_permalink( $post_id ) . '#clear-space';

		$this->assertStringContainsString( esc_url( $expected ), $html );

		// And that anchor exists in the rendered page.
		$rendered = do_blocks( get_post_field( 'post_content', $post_id ) );
		$this->assertStringContainsString( 'id="clear-space"', $rendered );
	}

	/**
	 * The matched term is marked in both the heading and the snippet.
	 *
	 * @return void
	 */
	public function test_the_term_is_highlighted() {
		$html = $this->render_subsections_for( 'pegasus' );

		$this->assertStringContainsString( '<mark>pegasus</mark>', $html );
	}

	/**
	 * The snippet sits outside the anchor deliberately: inside, it would join the link's
	 * accessible name and every result would announce as a paragraph of prose.
	 *
	 * @return void
	 */
	public function test_the_snippet_is_outside_the_link() {
		$html = $this->render_subsections_for( 'pegasus' );

		$this->assertStringContainsString( '</a><p class="brand-search__snippet">', $html );
	}

	/**
	 * A term that only matches the page title, with nothing below it, has no subsection to
	 * offer — and an empty landmark would still be announced.
	 *
	 * @return void
	 */
	public function test_renders_nothing_when_only_the_title_matched() {
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Zebra',
				'post_content' => '<!-- wp:paragraph --><p>Nothing relevant here.</p><!-- /wp:paragraph -->',
			)
		);

		$this->go_to( home_url( '/?s=zebra' ) );
		$this->assertTrue( have_posts() );
		the_post();

		$this->assertSame( '', do_blocks( '<!-- wp:ucf-brand/search-subsections /-->' ) );
	}

	/**
	 * Outside a search request the block renders nothing at all.
	 *
	 * @return void
	 */
	public function test_renders_nothing_outside_a_search() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Logo Usage',
				'post_content' => self::PAGE_CONTENT,
			)
		);

		$this->go_to( get_permalink( $post_id ) );
		the_post();

		$this->assertSame( '', do_blocks( '<!-- wp:ucf-brand/search-subsections /-->' ) );
	}
}
