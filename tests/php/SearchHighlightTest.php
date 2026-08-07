<?php
/**
 * Highlighting and snippet windowing.
 *
 * `ucf_brand_highlight_terms()` escapes each run *between* matches separately and only then
 * re-joins, so `<mark>` is the one piece of markup in the output. The comment in search.php
 * spells out why the obvious alternative is wrong in both directions: escaping first shifts
 * every byte offset, and it makes a search for "amp" start matching inside `&amp;`. These
 * tests hold that line — an XSS regression here is a stored-content injection on the search
 * results page.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests;

/**
 * @covers ::ucf_brand_highlight_terms
 * @covers ::ucf_brand_section_snippet
 */
final class SearchHighlightTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->loadInclude( 'search' );
	}

	/**
	 * @return void
	 */
	public function test_wraps_a_match_in_mark() {
		$this->assertSame(
			'Use the <mark>logo</mark> here.',
			ucf_brand_highlight_terms( 'Use the logo here.', array( 'logo' ) )
		);
	}

	/**
	 * @return void
	 */
	public function test_marks_every_occurrence() {
		$this->assertSame(
			'<mark>Logo</mark> and <mark>logo</mark>',
			ucf_brand_highlight_terms( 'Logo and logo', array( 'logo' ) )
		);
	}

	/**
	 * Two terms separated by nothing but whitespace merge into one highlight. The reader
	 * typed one phrase and should see one chip, not two with a seam down the middle.
	 *
	 * @return void
	 */
	public function test_adjacent_matches_merge_into_one_mark() {
		$this->assertSame(
			'<mark>clear space</mark> matters',
			ucf_brand_highlight_terms( 'clear space matters', array( 'clear', 'space' ) )
		);
	}

	/**
	 * @return void
	 */
	public function test_non_adjacent_matches_stay_separate() {
		$this->assertSame(
			'<mark>clear</mark> the <mark>space</mark>',
			ucf_brand_highlight_terms( 'clear the space', array( 'clear', 'space' ) )
		);
	}

	/**
	 * Text around a match is escaped.
	 *
	 * @return void
	 */
	public function test_surrounding_text_is_escaped() {
		$this->assertSame(
			'<mark>Tom</mark> &amp; Jerry',
			ucf_brand_highlight_terms( 'Tom & Jerry', array( 'tom' ) )
		);
	}

	/**
	 * The text *around* a match is escaped.
	 *
	 * @return void
	 */
	public function test_text_after_a_match_is_escaped() {
		$out = ucf_brand_highlight_terms( 'alert <script>bad</script>', array( 'alert' ) );

		$this->assertStringNotContainsString( '<script>', $out );
		$this->assertStringContainsString( '&lt;script&gt;', $out );
	}

	/**
	 * And so is the matched run itself — the harder half.
	 *
	 * Both halves of this are reachable from user input: the term comes from `?s=`, the
	 * haystack from stored page content, and a brand guide documenting markup genuinely
	 * contains strings like `<script`. An earlier version of this test put the payload
	 * *after* the match, which meant it passed through the trailing-text escape and the
	 * test kept passing with `esc_html()` deleted from inside the `<mark>`. Keep the
	 * payload inside the matched span.
	 *
	 * @return void
	 */
	public function test_the_matched_run_itself_is_escaped() {
		$out = ucf_brand_highlight_terms(
			'the x<script>alert(1)</script> example',
			array( 'x<script>alert(1)</script>' )
		);

		$this->assertStringNotContainsString( '<script>', $out );
		$this->assertStringContainsString( '<mark>x&lt;script&gt;', $out );
	}

	/**
	 * The same property with a plainer payload: an ampersand inside the match.
	 *
	 * @return void
	 */
	public function test_entities_inside_the_matched_run_are_escaped() {
		$this->assertSame(
			'we use <mark>a&amp;b</mark> here',
			ucf_brand_highlight_terms( 'we use a&b here', array( 'a&b' ) )
		);
	}

	/**
	 * With no match the whole string is still escaped — the early return must not become
	 * a raw passthrough.
	 *
	 * @return void
	 */
	public function test_unmatched_text_is_still_escaped() {
		$this->assertSame(
			'&lt;b&gt;hi&lt;/b&gt;',
			ucf_brand_highlight_terms( '<b>hi</b>', array( 'nothing' ) )
		);

		$this->assertSame(
			'&lt;b&gt;hi&lt;/b&gt;',
			ucf_brand_highlight_terms( '<b>hi</b>', array() )
		);
	}

	/**
	 * A short section is returned whole: no window, no ellipses.
	 *
	 * @return void
	 */
	public function test_short_text_is_returned_whole() {
		$this->assertSame(
			'Short <mark>body</mark> copy.',
			ucf_brand_section_snippet( 'Short body copy.', array( 'body' ) )
		);
	}

	/**
	 * @return void
	 */
	public function test_whitespace_is_collapsed() {
		$this->assertSame(
			'one two',
			ucf_brand_section_snippet( "  one \n\n  two  ", array() )
		);
	}

	/**
	 * @return void
	 */
	public function test_empty_text_yields_no_snippet() {
		$this->assertSame( '', ucf_brand_section_snippet( '', array( 'logo' ) ) );
		$this->assertSame( '', ucf_brand_section_snippet( '   ', array( 'logo' ) ) );
	}

	/**
	 * A long section is clipped and flagged with a trailing ellipsis.
	 *
	 * @return void
	 */
	public function test_long_text_is_clipped_with_a_trailing_ellipsis() {
		$text = str_repeat( 'alpha bravo charlie delta ', 20 );

		$snippet = ucf_brand_section_snippet( $text, array(), 60 );

		$this->assertStringEndsWith( '…', $snippet );
		$this->assertLessThan( 80, strlen( $snippet ) );

		// Words are never cut in half.
		$this->assertStringNotContainsString( 'alph…', $snippet );
	}

	/**
	 * When the match sits deep in the section the window opens before it, so the hit
	 * reads in context rather than flush against the left edge — and the leading
	 * ellipsis signals the text was entered mid-stream.
	 *
	 * @return void
	 */
	public function test_window_opens_before_a_deep_match() {
		$text = str_repeat( 'filler words here ', 30 ) . 'the pegasus mark';

		$snippet = ucf_brand_section_snippet( $text, array( 'pegasus' ), 90 );

		$this->assertStringStartsWith( '…', $snippet );
		$this->assertStringContainsString( '<mark>pegasus</mark>', $snippet );
	}

	/**
	 * A heading-only match has nothing to center on, so the window opens at the start of
	 * the body — which still tells the reader what they are jumping into.
	 *
	 * @return void
	 */
	public function test_window_opens_at_the_start_when_the_body_has_no_match() {
		$text = str_repeat( 'body copy that never matches ', 10 );

		$snippet = ucf_brand_section_snippet( $text, array( 'pegasus' ), 60 );

		$this->assertStringStartsWith( 'body copy', $snippet );
		$this->assertStringEndsWith( '…', $snippet );
	}
}
