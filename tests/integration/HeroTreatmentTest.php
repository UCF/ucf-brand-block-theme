<?php
/**
 * The hero's per-page light/dark treatment, through the real render pipeline.
 *
 * `ucf_brand_apply_hero_treatment()` is a `render_block` filter driving
 * WP_HTML_Tag_Processor off post meta, so none of it can be tested honestly without
 * WordPress. What it protects is the reason the field exists: the hero is one block in
 * templates/page.html, shared by every brand page, so a page choosing a treatment must not
 * change what any other page renders.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests\Integration;

use WP_UnitTestCase;

/**
 * @covers ::ucf_brand_apply_hero_treatment
 * @covers ::ucf_brand_hero_treatment_class
 */
final class HeroTreatmentTest extends WP_UnitTestCase {

	/**
	 * The hero as templates/page.html ships it: the wrapper, carrying the site-wide default.
	 *
	 * @param string $class Composition class on the wrapper.
	 * @return string Block markup.
	 */
	private function hero( $class = 'is-style-dark' ) {
		return '<!-- wp:ucf-brand/page-hero {"align":"full","className":"' . $class . '"} -->'
			. '<div class="wp-block-ucf-brand-page-hero alignfull brand-hero ' . $class . '">'
			. '<!-- wp:paragraph --><p>Hero copy.</p><!-- /wp:paragraph -->'
			. '</div>'
			. '<!-- /wp:ucf-brand/page-hero -->';
	}

	/**
	 * Render the hero in the context of a page, the way the template does.
	 *
	 * The filter reads `get_the_ID()`, so the global post has to be set up as a real render
	 * would leave it.
	 *
	 * @param string $treatment Value for the `ucf_brand_hero_treatment` meta, or ''.
	 * @param string $class     Composition class the template ships.
	 * @return string Rendered HTML.
	 */
	private function render_hero( $treatment, $class = 'is-style-dark' ) {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => $this->hero( $class ),
			)
		);

		if ( '' !== $treatment ) {
			update_post_meta( $post_id, 'ucf_brand_hero_treatment', $treatment );
		}

		$this->go_to( get_permalink( $post_id ) );
		the_post();

		return do_blocks( get_post_field( 'post_content', $post_id ) );
	}

	/**
	 * @return void
	 */
	public function test_a_page_with_no_treatment_keeps_the_templates_class() {
		$html = $this->render_hero( '' );

		$this->assertStringContainsString( 'is-style-dark', $html );
		$this->assertStringNotContainsString( 'is-style-light', $html );
	}

	/**
	 * @return void
	 */
	public function test_a_page_set_to_light_renders_the_light_treatment() {
		$html = $this->render_hero( 'light' );

		$this->assertStringContainsString( 'is-style-light', $html );
	}

	/**
	 * The class the template ships comes off. Both on the wrapper leaves which one paints to
	 * the order of the rules in main.css rather than to the page's choice.
	 *
	 * @return void
	 */
	public function test_the_templates_treatment_is_replaced_not_added_to() {
		$html = $this->render_hero( 'light' );

		$this->assertStringNotContainsString( 'is-style-dark', $html );
		$this->assertSame( 1, substr_count( $html, 'is-style-light' ) );
	}

	/**
	 * A page can also pin Dark explicitly, which matters once the template's own default is
	 * something else.
	 *
	 * @return void
	 */
	public function test_a_page_can_pin_dark_against_a_light_template() {
		$html = $this->render_hero( 'dark', 'is-style-light' );

		$this->assertStringContainsString( 'is-style-dark', $html );
		$this->assertStringNotContainsString( 'is-style-light', $html );
	}

	/**
	 * The whole point of a per-page field: one page's choice does not reach the next.
	 *
	 * @return void
	 */
	public function test_one_pages_treatment_does_not_follow_the_template_to_another() {
		$this->assertStringContainsString( 'is-style-light', $this->render_hero( 'light' ) );
		$this->assertStringContainsString( 'is-style-dark', $this->render_hero( '' ) );
	}

	/**
	 * The meta's sanitize callback drops a value that is not a treatment, so nothing but the
	 * two ever reaches the database.
	 *
	 * @return void
	 */
	public function test_an_unknown_treatment_is_not_stored() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		update_post_meta( $post_id, 'ucf_brand_hero_treatment', 'chartreuse' );

		$this->assertSame( '', get_post_meta( $post_id, 'ucf_brand_hero_treatment', true ) );
	}

	/**
	 * And the read side refuses it too, for a value that lands another way — a direct write,
	 * an import, or a treatment removed from the theme after pages had chosen it.
	 *
	 * WHY: the sanitize filter is dropped first. With it in place `update_post_meta()` stores
	 * '' and the assertion below passes against a guard that does nothing.
	 *
	 * @return void
	 */
	public function test_an_unknown_treatment_is_never_printed_as_a_class() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => $this->hero(),
			)
		);

		// UPSTREAM: `register_post_meta()` with a subtype hangs its callback on the
		// `_for_{$subtype}` filter, so removing the unsuffixed one alone leaves it sanitizing.
		remove_all_filters( 'sanitize_post_meta_ucf_brand_hero_treatment' );
		remove_all_filters( 'sanitize_post_meta_ucf_brand_hero_treatment_for_page' );
		update_post_meta( $post_id, 'ucf_brand_hero_treatment', 'chartreuse' );

		$this->assertSame(
			'chartreuse',
			get_post_meta( $post_id, 'ucf_brand_hero_treatment', true ),
			'The fixture has to hold the stray value for the guard to be under test.'
		);

		$this->go_to( get_permalink( $post_id ) );
		the_post();

		$html = do_blocks( get_post_field( 'post_content', $post_id ) );

		$this->assertStringNotContainsString( 'chartreuse', $html );
		$this->assertStringContainsString( 'is-style-dark', $html );
	}

	/**
	 * The filter is the hero's alone — every other block renders untouched.
	 *
	 * @return void
	 */
	public function test_other_blocks_are_left_alone() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:group {"className":"is-style-dark"} -->'
					. '<div class="wp-block-group is-style-dark">'
					. '<!-- wp:paragraph --><p>Group copy.</p><!-- /wp:paragraph -->'
					. '</div><!-- /wp:group -->',
			)
		);

		update_post_meta( $post_id, 'ucf_brand_hero_treatment', 'light' );
		$this->go_to( get_permalink( $post_id ) );
		the_post();

		$html = do_blocks( get_post_field( 'post_content', $post_id ) );

		$this->assertStringContainsString( 'is-style-dark', $html );
		$this->assertStringNotContainsString( 'is-style-light', $html );
	}
}
