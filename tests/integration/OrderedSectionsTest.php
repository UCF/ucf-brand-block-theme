<?php
/**
 * The drawer's section list, against real posts and real meta.
 *
 * The unit suite covers the ordering and the rendered markup with `get_posts()` stubbed. What
 * it cannot cover is the query itself — whether the `meta_key` / `meta_query` pair actually
 * selects the right posts out of a database. That is the half that breaks when someone
 * "tidies" the query, and it is what this file tests.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests\Integration;

use WP_UnitTestCase;

/**
 * @covers ::ucf_brand_get_ordered_sections
 */
final class OrderedSectionsTest extends WP_UnitTestCase {

	/**
	 * Create a published top-level page carrying a brand number.
	 *
	 * @param string   $title  Page title.
	 * @param int|null $number Brand number, or null to leave the meta unset.
	 * @return int Post ID.
	 */
	private function make_page( $title, $number = null ) {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $title,
			)
		);

		if ( null !== $number ) {
			update_post_meta( $post_id, 'ucf_brand_number', $number );
		}

		return $post_id;
	}

	/**
	 * @return void
	 */
	public function test_returns_numbered_pages_in_order() {
		$this->make_page( 'Voice', 3 );
		$this->make_page( 'Foundation', 1 );
		$this->make_page( 'Identity', 2 );

		$titles = wp_list_pluck( ucf_brand_get_ordered_sections(), 'title' );

		$this->assertSame( array( 'Foundation', 'Identity', 'Voice' ), $titles );
	}

	/**
	 * The meta_query is what excludes these. A page with no number is not a drawer section.
	 *
	 * @return void
	 */
	public function test_pages_without_a_number_are_excluded() {
		$this->make_page( 'Numbered', 1 );
		$this->make_page( 'No meta at all' );
		$this->make_page( 'Explicit zero', 0 );

		$titles = wp_list_pluck( ucf_brand_get_ordered_sections(), 'title' );

		$this->assertSame( array( 'Numbered' ), $titles );
	}

	/**
	 * @return void
	 */
	public function test_drafts_are_excluded() {
		$this->make_page( 'Published', 1 );

		$draft = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
				'post_title'  => 'Draft',
			)
		);
		update_post_meta( $draft, 'ucf_brand_number', 2 );

		$titles = wp_list_pluck( ucf_brand_get_ordered_sections(), 'title' );

		$this->assertSame( array( 'Published' ), $titles );
	}

	/**
	 * Only top-level pages are drawer sections; a numbered child is not one.
	 *
	 * @return void
	 */
	public function test_child_pages_are_excluded() {
		$parent = $this->make_page( 'Parent', 1 );

		$child = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Child',
				'post_parent' => $parent,
			)
		);
		update_post_meta( $child, 'ucf_brand_number', 2 );

		$titles = wp_list_pluck( ucf_brand_get_ordered_sections(), 'title' );

		$this->assertSame( array( 'Parent' ), $titles );
	}

	/**
	 * Posts are not pages, however they are numbered.
	 *
	 * @return void
	 */
	public function test_posts_are_excluded() {
		$this->make_page( 'A Page', 1 );

		$post = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'A Post',
			)
		);
		update_post_meta( $post, 'ucf_brand_number', 2 );

		$titles = wp_list_pluck( ucf_brand_get_ordered_sections(), 'title' );

		$this->assertSame( array( 'A Page' ), $titles );
	}

	/**
	 * @return void
	 */
	public function test_the_front_page_is_excluded() {
		$home = $this->make_page( 'Home', 1 );
		$this->make_page( 'Foundation', 2 );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home );

		$titles = wp_list_pluck( ucf_brand_get_ordered_sections(), 'title' );

		$this->assertSame( array( 'Foundation' ), $titles );
	}

	/**
	 * The meta is stored as a string; the ordering must still be numeric. A lexical sort
	 * would put 10 before 2 — the bug the `type => NUMERIC` in the meta_query prevents.
	 *
	 * @return void
	 */
	public function test_ordering_is_numeric_not_lexical() {
		$this->make_page( 'Ten', 10 );
		$this->make_page( 'Two', 2 );
		$this->make_page( 'One', 1 );

		$titles = wp_list_pluck( ucf_brand_get_ordered_sections(), 'title' );

		$this->assertSame( array( 'One', 'Two', 'Ten' ), $titles );
	}

	/**
	 * @return void
	 */
	public function test_entries_carry_label_url_and_current_flag() {
		$foundation = $this->make_page( 'Foundation', 1 );
		$this->make_page( 'Identity', 2 );

		$this->go_to( get_permalink( $foundation ) );

		$sections = ucf_brand_get_ordered_sections();

		$this->assertSame( '01', $sections[0]['label'] );
		$this->assertSame( get_permalink( $foundation ), $sections[0]['url'] );
		$this->assertTrue( $sections[0]['is_current'] );
		$this->assertFalse( $sections[1]['is_current'] );
	}

	/**
	 * The block renders nothing at all rather than an empty landmark that assistive
	 * technology would still announce.
	 *
	 * @return void
	 */
	public function test_the_nav_block_renders_nothing_without_sections() {
		$this->assertSame( '', ucf_brand_render_section_nav() );
	}

	/**
	 * @return void
	 */
	public function test_the_nav_block_renders_registered_markup() {
		$this->make_page( 'Foundation', 1 );

		$html = do_blocks( '<!-- wp:ucf-brand/section-nav /-->' );

		$this->assertStringContainsString( 'brand-nav__list', $html );
		$this->assertStringContainsString( 'Foundation', $html );
		$this->assertStringContainsString( '01', $html );
	}
}
