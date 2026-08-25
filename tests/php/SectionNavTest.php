<?php
/**
 * The drawer navigation, end to end.
 *
 * This covers `ucf_brand_get_ordered_sections()` and `ucf_brand_render_section_nav()`
 * together rather than mocking the boundary between them, because the ordering *is* the
 * behavior worth testing and the two are documented as one source of truth: the drawer and
 * each page's on-page label read the same list so they cannot disagree.
 *
 * The markup assertions are not incidental either — `_drawer.scss` styles these exact
 * classes and `brand-nav.js` injects the H2 sub-nav inside `.is-current`.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests;

use Brain\Monkey\Functions;
use WP_Post;

/**
 * @covers ::ucf_brand_get_ordered_sections
 * @covers ::ucf_brand_render_section_nav
 */
final class SectionNavTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->loadInclude( 'sections', 'section-nav' );
	}

	/**
	 * Serve a fake page set to the section functions.
	 *
	 * Pages are handed to get_posts() in an arbitrary order on purpose: the real query
	 * sorts by meta value, but the function re-sorts afterwards, and that usort is what
	 * these tests are checking.
	 *
	 * @param array<int, array{title: string, number: mixed}> $pages      Keyed by post ID.
	 * @param int                                             $current_id Queried page.
	 * @param int                                             $front_id   Front page.
	 * @return void
	 */
	private function seedPages( array $pages, $current_id = 0, $front_id = 0 ) {
		$posts = array();

		foreach ( $pages as $id => $data ) {
			$posts[] = new WP_Post(
				array(
					'ID'         => $id,
					'post_title' => $data['title'],
				)
			);
		}

		Functions\when( 'get_posts' )->justReturn( $posts );
		Functions\when( 'get_option' )->justReturn( $front_id );
		Functions\when( 'get_queried_object_id' )->justReturn( $current_id );

		Functions\when( 'get_post_meta' )->alias(
			static function ( $post_id ) use ( $pages ) {
				return isset( $pages[ $post_id ] ) ? $pages[ $post_id ]['number'] : '';
			}
		);

		Functions\when( 'get_the_title' )->alias(
			static function ( $post ) {
				return $post->post_title;
			}
		);

		Functions\when( 'get_permalink' )->alias(
			static function ( $post ) {
				return 'https://example.test/page-' . $post->ID . '/';
			}
		);
	}

	/**
	 * @return void
	 */
	public function test_orders_by_number_regardless_of_query_order() {
		$this->seedPages(
			array(
				10 => array(
					'title'  => 'Voice',
					'number' => 3,
				),
				11 => array(
					'title'  => 'Foundation',
					'number' => 1,
				),
				12 => array(
					'title'  => 'Identity',
					'number' => 2,
				),
			)
		);

		$titles = array_column( ucf_brand_get_ordered_sections(), 'title' );

		$this->assertSame( array( 'Foundation', 'Identity', 'Voice' ), $titles );
	}

	/**
	 * Equal numbers fall back to a case-insensitive title sort.
	 *
	 * @return void
	 */
	public function test_ties_break_on_title_case_insensitively() {
		$this->seedPages(
			array(
				10 => array(
					'title'  => 'beta',
					'number' => 1,
				),
				11 => array(
					'title'  => 'Alpha',
					'number' => 1,
				),
			)
		);

		$titles = array_column( ucf_brand_get_ordered_sections(), 'title' );

		$this->assertSame( array( 'Alpha', 'beta' ), $titles );
	}

	/**
	 * @return void
	 */
	public function test_unnumbered_pages_are_excluded() {
		$this->seedPages(
			array(
				10 => array(
					'title'  => 'Numbered',
					'number' => 1,
				),
				11 => array(
					'title'  => 'Unnumbered',
					'number' => '',
				),
				12 => array(
					'title'  => 'Zero',
					'number' => 0,
				),
			)
		);

		$titles = array_column( ucf_brand_get_ordered_sections(), 'title' );

		$this->assertSame( array( 'Numbered' ), $titles );
	}

	/**
	 * @return void
	 */
	public function test_the_front_page_is_excluded() {
		$this->seedPages(
			array(
				10 => array(
					'title'  => 'Home',
					'number' => 1,
				),
				11 => array(
					'title'  => 'Foundation',
					'number' => 2,
				),
			),
			0,
			10
		);

		$titles = array_column( ucf_brand_get_ordered_sections(), 'title' );

		$this->assertSame( array( 'Foundation' ), $titles );
	}

	/**
	 * @return void
	 */
	public function test_labels_are_zero_padded() {
		$this->seedPages(
			array(
				10 => array(
					'title'  => 'Foundation',
					'number' => 1,
				),
			)
		);

		$sections = ucf_brand_get_ordered_sections();

		$this->assertSame( '1', $sections[0]['label'] );
	}

	/**
	 * @return void
	 */
	public function test_the_queried_page_is_flagged_current() {
		$this->seedPages(
			array(
				10 => array(
					'title'  => 'Foundation',
					'number' => 1,
				),
				11 => array(
					'title'  => 'Identity',
					'number' => 2,
				),
			),
			11
		);

		$sections = ucf_brand_get_ordered_sections();

		$this->assertFalse( $sections[0]['is_current'] );
		$this->assertTrue( $sections[1]['is_current'] );
	}

	/**
	 * The rendered markup is a contract with _drawer.scss and brand-nav.js.
	 *
	 * @return void
	 */
	public function test_renders_the_nav_markup_the_css_and_js_expect() {
		$this->seedPages(
			array(
				10 => array(
					'title'  => 'Foundation',
					'number' => 1,
				),
			)
		);

		$html = ucf_brand_render_section_nav();

		$this->assertStringContainsString( '<nav class="brand-nav"', $html );
		$this->assertStringContainsString( 'aria-label="Brand sections"', $html );
		$this->assertStringContainsString( '<ul class="brand-nav__list">', $html );
		$this->assertStringContainsString( 'class="brand-nav__item"', $html );
		$this->assertStringContainsString( 'href="https://example.test/page-10/"', $html );
		$this->assertStringContainsString( '<span class="brand-nav__num">1</span>', $html );
		$this->assertStringContainsString( '<span class="brand-nav__text">Foundation</span>', $html );
		$this->assertStringContainsString( 'aria-hidden="true"', $html );
	}

	/**
	 * The current item carries both the class the CSS paints and the attribute assistive
	 * technology reads.
	 *
	 * @return void
	 */
	public function test_the_current_item_is_marked_in_markup() {
		$this->seedPages(
			array(
				10 => array(
					'title'  => 'Foundation',
					'number' => 1,
				),
			),
			10
		);

		$html = ucf_brand_render_section_nav();

		$this->assertStringContainsString( 'class="brand-nav__item is-current"', $html );
		$this->assertStringContainsString( 'aria-current="page"', $html );
	}

	/**
	 * With nothing numbered the block renders nothing at all, rather than an empty
	 * landmark that assistive technology would still announce.
	 *
	 * @return void
	 */
	public function test_renders_nothing_when_no_page_is_numbered() {
		$this->seedPages( array() );

		$this->assertSame( '', ucf_brand_render_section_nav() );
	}

	/**
	 * @return void
	 */
	public function test_titles_are_escaped() {
		$this->seedPages(
			array(
				10 => array(
					'title'  => 'Tom & <script>alert(1)</script>',
					'number' => 1,
				),
			)
		);

		$html = ucf_brand_render_section_nav();

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&amp;', $html );
	}
}
