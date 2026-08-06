<?php
/**
 * Search term parsing and the one pattern that defines "a hit".
 *
 * `ucf_brand_term_pattern()` is deliberately the single owner of the matching rule, so
 * scoring, snippet windowing and highlighting can never disagree about what matched. Two
 * properties in it are load-bearing and easy to lose in a refactor: matches are
 * word-bounded (so "art" does not hit "start" and rank an unrelated section first), and
 * alternatives are sorted longest-first (so a quoted phrase claims the run before the
 * individual words inside it do).
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * @covers ::ucf_brand_search_terms
 * @covers ::ucf_brand_term_pattern
 * @covers ::ucf_brand_count_term
 */
#[CoversFunction( 'ucf_brand_search_terms' )]
#[CoversFunction( 'ucf_brand_term_pattern' )]
#[CoversFunction( 'ucf_brand_count_term' )]
final class SearchTermsTest extends TestCase {

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
	public function test_splits_on_whitespace() {
		$this->assertSame( array( 'logo', 'usage' ), ucf_brand_search_terms( 'logo usage' ) );
	}

	/**
	 * A quoted run stays whole so "clear space" ranks as a phrase rather than as two
	 * common words.
	 *
	 * @return void
	 */
	public function test_keeps_a_quoted_phrase_together() {
		$this->assertSame( array( 'clear space' ), ucf_brand_search_terms( '"clear space"' ) );
	}

	/**
	 * @return void
	 */
	public function test_mixes_a_phrase_with_loose_words() {
		$this->assertSame(
			array( 'clear space', 'logo' ),
			ucf_brand_search_terms( '"clear space" logo' )
		);
	}

	/**
	 * Single characters are too noisy to rank on.
	 *
	 * @return void
	 */
	public function test_drops_single_characters() {
		$this->assertSame( array( 'logo' ), ucf_brand_search_terms( 'a logo x' ) );
	}

	/**
	 * @return void
	 */
	public function test_deduplicates_and_reindexes() {
		$terms = ucf_brand_search_terms( 'logo logo usage' );

		$this->assertSame( array( 'logo', 'usage' ), $terms );
		$this->assertSame( array( 0, 1 ), array_keys( $terms ) );
	}

	/**
	 * @return void
	 */
	public function test_empty_and_whitespace_queries_yield_nothing() {
		$this->assertSame( array(), ucf_brand_search_terms( '' ) );
		$this->assertSame( array(), ucf_brand_search_terms( '   ' ) );
	}

	/**
	 * @return void
	 */
	public function test_defaults_to_the_active_query() {
		Functions\when( 'get_search_query' )->justReturn( 'typography' );

		$this->assertSame( array( 'typography' ), ucf_brand_search_terms() );
	}

	/**
	 * Word-bounded matching. An unbounded pattern would score "art" against "start".
	 *
	 * @return void
	 */
	public function test_matching_is_word_bounded() {
		$this->assertSame( 0, ucf_brand_count_term( 'art', 'start standard' ) );
		$this->assertSame( 1, ucf_brand_count_term( 'art', 'the art of it' ) );
	}

	/**
	 * @return void
	 */
	public function test_counting_is_case_insensitive() {
		$this->assertSame( 2, ucf_brand_count_term( 'logo', 'Logo and logo' ) );
	}

	/**
	 * Regex metacharacters in user input must be quoted, not executed.
	 *
	 * @return void
	 */
	public function test_special_characters_are_escaped() {
		$this->assertSame( 0, ucf_brand_count_term( 'c++', 'plain text' ) );
		$this->assertSame( 1, ucf_brand_count_term( 'c++', 'we use c++ here' ) );
	}

	/**
	 * Longest-first alternation is what lets a phrase claim the run ahead of its own
	 * words. Losing it renders "clear space" as two adjacent marks with a seam.
	 *
	 * @return void
	 */
	public function test_pattern_orders_alternatives_longest_first() {
		$pattern = ucf_brand_term_pattern( array( 'a', 'clear space', 'logo' ) );

		$this->assertMatchesRegularExpression( '#^/\\\\b\(clear space\|logo\|a\)/iu$#', $pattern );
	}

	/**
	 * @return void
	 */
	public function test_pattern_is_empty_when_there_is_nothing_to_match() {
		$this->assertSame( '', ucf_brand_term_pattern( array() ) );
		$this->assertSame( '', ucf_brand_term_pattern( array( '', '   ' ) ) );
	}

	/**
	 * @return void
	 */
	public function test_counting_against_empty_input_is_zero() {
		$this->assertSame( 0, ucf_brand_count_term( 'logo', '' ) );
		$this->assertSame( 0, ucf_brand_count_term( '', 'logo' ) );
	}
}
