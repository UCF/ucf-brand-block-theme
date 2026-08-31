<?php
/**
 * The index block against the page it indexes, rendered by real WordPress.
 *
 * The unit suite covers the markup this block builds from a section list. What it cannot
 * cover is the claim the block actually rests on: that every `#anchor` it emits is an id the
 * same page really renders. Those two come from different code paths — the index reads stored
 * `post_content` through `ucf_brand_get_post_sections()`, while the ids are written into the
 * HTML by the `render_block` filter in includes/headings.php — and they agree only because
 * both walk the same H2s through the same two slug helpers in the same order.
 *
 * docs/architecture.md records what a disagreement costs: every deep link lands at the top of
 * the page, which is exactly the failure a jump list cannot survive and no unit test sees.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests\Integration;

use WP_UnitTestCase;

/**
 * @covers ::ucf_brand_render_section_index
 */
final class SectionIndexTest extends WP_UnitTestCase {

	/**
	 * Render a page holding the index block above its own headings.
	 *
	 * @param string $headings Block markup for the page's body.
	 * @param array  $attrs    Index block attributes.
	 * @param int    $number   The page's Brand order, or 0 for none.
	 * @return string Rendered HTML.
	 */
	private function render_page( $headings, array $attrs = array(), $number = 4 ) {
		// The attribute JSON is omitted rather than emitted empty: PHP encodes `array()` as
		// `[]`, and a block comment carrying that does not parse as a block at all — it
		// renders as literal text, and every assertion below then fails for the wrong reason.
		$json = $attrs ? wp_json_encode( $attrs ) . ' ' : '';

		$content = '<!-- wp:ucf-brand/section-index ' . $json . "/-->\n\n" . $headings;

		// UPSTREAM: wp_insert_post() expects slashed data and unslashes it, which eats the
		// backslashes JSON uses to escape the quotes inside an attribute string — the block
		// comment then holds invalid JSON, `attrs` parses as null, and every description
		// silently vanishes while the block itself still renders. The editor's REST save
		// slashes for you; a fixture has to do it by hand.
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => wp_slash( $content ),
			)
		);

		if ( $number ) {
			update_post_meta( $post_id, 'ucf_brand_number', $number );
		}

		$this->go_to( get_permalink( $post_id ) );
		the_post();

		return do_blocks( get_post_field( 'post_content', $post_id ) );
	}

	/**
	 * Two headings whose slugs are not their text: an ampersand entity, which is the pair
	 * that broke deep links the last time two slug implementations disagreed, and a duplicate
	 * of an earlier heading, which only the shared collision counter numbers the same way.
	 *
	 * @return string Block markup.
	 */
	private function awkward_headings() {
		return '<!-- wp:heading --><h2 class="wp-block-heading">Photography &amp; Video</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>Shooting for the brand.</p><!-- /wp:paragraph -->'
			. '<!-- wp:heading --><h2 class="wp-block-heading">Overview</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>The short version.</p><!-- /wp:paragraph -->'
			. '<!-- wp:heading --><h2 class="wp-block-heading">Overview</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>The longer version.</p><!-- /wp:paragraph -->';
	}

	/**
	 * Every href the index emits must resolve to an id on the rendered page.
	 *
	 * @return void
	 */
	public function test_every_entry_links_to_an_id_the_page_renders() {
		$html = $this->render_page( $this->awkward_headings() );

		preg_match_all( '/class="brand-index__link" href="#([^"]+)"/', $html, $links );

		$this->assertCount( 3, $links[1], 'One entry per H2 on the page.' );

		foreach ( $links[1] as $anchor ) {
			$this->assertStringContainsString(
				'id="' . $anchor . '"',
				$html,
				"The index links to #{$anchor}, which no heading on this page carries."
			);
		}

		// The two cases the assertion above would also pass on if both halves were wrong
		// in the same way. These are the ids headings.php is documented to produce.
		$this->assertSame(
			array( 'photography-video', 'overview', 'overview-2' ),
			$links[1]
		);
	}

	/**
	 * SYNC: `--brand-section` in _sections.scss prints "4.1" on the H2's own badge. The index
	 * and the heading it points at have to say the same number.
	 *
	 * @return void
	 */
	public function test_entry_numbers_match_the_pages_brand_number() {
		$html = $this->render_page( $this->awkward_headings(), array(), 4 );

		$this->assertStringContainsString( '>4.1</span>', $html );
		$this->assertStringContainsString( '>4.3</span>', $html );
	}

	/**
	 * A heading added to the page appears in the index with no edit to the block.
	 *
	 * @return void
	 */
	public function test_the_list_follows_the_page_rather_than_the_attributes() {
		$attrs = array(
			'heading'      => 'This section covers:',
			'descriptions' => array( 'Overview' => 'The short version.' ),
		);

		$before = $this->render_page(
			'<!-- wp:heading --><h2>Overview</h2><!-- /wp:heading --><p>The short version.</p>',
			$attrs
		);

		$after = $this->render_page(
			'<!-- wp:heading --><h2>Overview</h2><!-- /wp:heading --><p>The short version.</p>'
			. '<!-- wp:heading --><h2>Motion</h2><!-- /wp:heading --><p>Video and animation.</p>',
			$attrs
		);

		$this->assertSame( 1, substr_count( $before, 'brand-index__item' ) );
		$this->assertSame( 2, substr_count( $after, 'brand-index__item' ) );

		// The authored half survives the change; the new entry simply has no description yet.
		$this->assertSame( 1, substr_count( $after, 'brand-index__desc' ) );
		$this->assertStringContainsString( 'href="#motion"', $after );
	}

	/**
	 * A description is rich text: the inline formatting the editor's own formats emit reaches
	 * the page as markup, and everything outside that set is stripped rather than escaped.
	 *
	 * `wp_kses()` is the thing under test here, which is why this is not in the unit tier —
	 * a stubbed sanitizer would only ever confirm the stub.
	 *
	 * @return void
	 */
	public function test_descriptions_keep_inline_formatting_and_nothing_else() {
		// CONTEXT: as an administrator, so post-save KSES leaves the content alone. Without
		// this the `<script>` below never reaches the block: WordPress strips it on insert
		// for a user with no `unfiltered_html`, mangling the block comment on the way past,
		// and the test then passes without the renderer having sanitized anything.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$html = $this->render_page(
			'<!-- wp:heading --><h2>Overview</h2><!-- /wp:heading --><p>The short version.</p>',
			array(
				'descriptions' => array(
					'Overview' => 'How we <em>sound</em>, in <a href="/voice/">detail</a>. '
						. '<span class="badge">Coming Soon</span>'
						. '<script>alert(1)</script><img src=x onerror=alert(1)><div>block</div>',
				),
			)
		);

		$this->assertStringContainsString( '<em>sound</em>', $html );
		$this->assertStringContainsString( '<a href="/voice/">detail</a>', $html );
		$this->assertStringContainsString( '<span class="badge">Coming Soon</span>', $html );

		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringNotContainsString( 'onerror', $html );
		$this->assertStringNotContainsString( '<div>block', $html );
	}

	/**
	 * The block is registered by PHP and saves no markup, so an unregistered block would
	 * render as nothing at all — and a `render_page()` assertion for "no index" would pass.
	 *
	 * @return void
	 */
	public function test_block_is_registered() {
		$this->assertTrue(
			\WP_Block_Type_Registry::get_instance()->is_registered( 'ucf-brand/section-index' )
		);
	}
}
