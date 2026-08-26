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
 * @covers ::ucf_brand_search_query_title
 * @covers ::ucf_brand_search_excerpt_from_deck
 * @covers ::ucf_brand_hide_results_without_sections
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
				// The page needs a section that matches, or ucf_brand_hide_results_without_sections()
				// drops it and this test fails for a reason that has nothing to do with post types.
				'post_content' => self::PAGE_CONTENT,
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
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Zebra',
				'post_content' => '<!-- wp:paragraph --><p>Nothing relevant here.</p><!-- /wp:paragraph -->',
			)
		);

		$this->go_to( home_url( '/?s=zebra' ) );

		// The loop is empty now — ucf_brand_hide_results_without_sections() drops exactly this
		// page. The block's own guard still has to hold for any other caller, so the post is
		// set up by hand rather than walked to through the loop.
		$GLOBALS['post'] = get_post( $post_id );
		setup_postdata( $GLOBALS['post'] );

		$this->assertSame( '', do_blocks( '<!-- wp:ucf-brand/search-subsections /-->' ) );

		wp_reset_postdata();
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

	/**
	 * The comp reads "Results for: logo", so core's longer wording is swapped in the
	 * rendered query-title block. Rendering the real block is the point of the test: the
	 * filter matches core's own string, and a core rewording must show up as a failure here
	 * rather than as a mangled title on the page.
	 *
	 * @return void
	 */
	public function test_the_search_title_uses_the_comps_wording() {
		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Logo Usage',
			)
		);

		$this->go_to( home_url( '/?s=logo' ) );

		$html = do_blocks( '<!-- wp:query-title {"type":"search"} /-->' );

		$this->assertStringContainsString( 'Results for: logo', $html );
		$this->assertStringNotContainsString( 'Search results for', $html );
	}

	/**
	 * The filter is keyed to the search request, not just to the block's type. Off a search,
	 * even the search variant of the block must come through as core wrote it.
	 *
	 * @return void
	 */
	public function test_a_non_search_request_is_untouched() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Logo Usage',
				'post_content' => self::PAGE_CONTENT,
			)
		);

		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringNotContainsString(
			'Results for:',
			do_blocks( '<!-- wp:query-title {"type":"search"} /-->' )
		);
	}

	/**
	 * A page's deck is what introduces it, so it is what a result row shows. The generated
	 * excerpt on these pages is the opening of a section index, not prose.
	 *
	 * @return void
	 */
	public function test_the_deck_becomes_the_search_excerpt() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Logo Usage',
				'post_content' => self::PAGE_CONTENT,
			)
		);
		update_post_meta( $post_id, 'ucf_brand_deck', 'The mark, and the room it needs.' );

		$this->go_to( home_url( '/?s=pegasus' ) );

		$this->assertSame( 'The mark, and the room it needs.', get_the_excerpt( $post_id ) );
	}

	/**
	 * Without a deck the row keeps whatever excerpt it had. Blanking it would leave a result
	 * as a bare title — the front page has no deck.
	 *
	 * @return void
	 */
	public function test_a_page_without_a_deck_keeps_its_excerpt() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Logo Usage',
				'post_excerpt' => 'Hand-written excerpt.',
				'post_content' => self::PAGE_CONTENT,
			)
		);

		$this->go_to( home_url( '/?s=pegasus' ) );

		$this->assertSame( 'Hand-written excerpt.', get_the_excerpt( $post_id ) );
	}

	/**
	 * Off a search the deck must not displace the excerpt anywhere else on the site.
	 *
	 * @return void
	 */
	public function test_the_deck_is_not_used_outside_a_search() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Logo Usage',
				'post_excerpt' => 'Hand-written excerpt.',
				'post_content' => self::PAGE_CONTENT,
			)
		);
		update_post_meta( $post_id, 'ucf_brand_deck', 'The mark, and the room it needs.' );

		$this->go_to( get_permalink( $post_id ) );

		$this->assertSame( 'Hand-written excerpt.', get_the_excerpt( $post_id ) );
	}

	/**
	 * A deck may hold a link — decks are stored through wp_kses_post. The row is itself a
	 * link, and core trims the excerpt to a word count, so the markup is flattened first.
	 *
	 * @return void
	 */
	public function test_markup_in_a_deck_is_flattened() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Logo Usage',
				'post_content' => self::PAGE_CONTENT,
			)
		);
		update_post_meta( $post_id, 'ucf_brand_deck', 'See the <a href="/writing/">writing rules</a> first.' );

		$this->go_to( home_url( '/?s=pegasus' ) );

		$this->assertSame( 'See the writing rules first.', get_the_excerpt( $post_id ) );
	}

	/**
	 * A page matching only in prose under no matching heading has nothing to click into, so
	 * it is not a result. PAGE_CONTENT's sections mention "pegasus" and "pixels"; a term that
	 * appears only in the title matches the page but no section within it.
	 *
	 * @return void
	 */
	public function test_a_page_with_no_matching_section_is_dropped() {
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Zebra Crossing',
				'post_content' => self::PAGE_CONTENT,
			)
		);

		$this->go_to( home_url( '/?s=zebra' ) );

		$this->assertSame( 0, $GLOBALS['wp_query']->post_count );
		$this->assertSame( 0, $GLOBALS['wp_query']->found_posts );
	}

	/**
	 * The page whose section matches stays, and the count reflects what is on the page.
	 *
	 * @return void
	 */
	public function test_a_page_with_a_matching_section_is_kept() {
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Logo Usage',
				'post_content' => self::PAGE_CONTENT,
			)
		);
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Pegasus Mentioned In The Title Only',
				'post_content' => '<!-- wp:paragraph --><p>Nothing here.</p><!-- /wp:paragraph -->',
			)
		);

		$this->go_to( home_url( '/?s=pegasus' ) );

		$this->assertSame( 1, $GLOBALS['wp_query']->post_count );
		$this->assertSame( 1, $GLOBALS['wp_query']->found_posts );
		$this->assertSame( 'Logo Usage', $GLOBALS['wp_query']->posts[0]->post_title );
	}

	/**
	 * A secondary query is not the search results list and must keep every post it asked for.
	 *
	 * @return void
	 */
	public function test_a_secondary_query_keeps_results_without_sections() {
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Zebra Crossing',
				'post_content' => self::PAGE_CONTENT,
			)
		);

		$this->go_to( home_url( '/?s=zebra' ) );

		$secondary = new \WP_Query(
			array(
				's'         => 'zebra',
				'post_type' => 'page',
			)
		);

		$this->assertSame( 1, $secondary->post_count );
	}
}
