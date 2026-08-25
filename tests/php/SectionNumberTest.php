<?php
/**
 * Section numbering and the hero's bound eyebrow.
 *
 * One meta value feeds three consumers — the drawer label, the hero eyebrow, and the CSS
 * variable behind the H2 subsection badges — so the format is not cosmetic. A page whose
 * number formats differently in one place than another is exactly the drift the single
 * source of truth exists to prevent.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests;

use Brain\Monkey\Functions;
use WP_Block;

/**
 * @covers ::ucf_brand_format_number
 * @covers ::ucf_brand_binding_section_number
 */
final class SectionNumberTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->loadInclude( 'sections' );
	}

	/**
	 * @param mixed  $input    Raw meta value.
	 * @param string $expected Formatted label.
	 * @return void
	 * @dataProvider numberCases
	 */
	public function test_format_number( $input, $expected ) {
		$this->assertSame( $expected, ucf_brand_format_number( $input ) );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function numberCases() {
		return array(
			// SPEC: unpadded — the guide numbers sections "1", "2", "3", not "01".
			'single digit'          => array( 1, '1' ),
			'nine'                  => array( 9, '9' ),
			'ten'                   => array( 10, '10' ),
			'three digits'          => array( 100, '100' ),

			// Meta comes back from the database as a string.
			'numeric string'        => array( '7', '7' ),

			// Everything below means "unnumbered", and must render as an empty label —
			// `.brand-page-number:empty` is what hides the element. A "0" here would
			// print a badge on every unnumbered page.
			'zero is unset'         => array( 0, '' ),
			'negative is unset'     => array( -3, '' ),
			'empty string is unset' => array( '', '' ),
			'non-numeric is unset'  => array( 'abc', '' ),
			'null is unset'         => array( null, '' ),
		);
	}

	/**
	 * @return void
	 */
	public function test_binding_reads_the_block_context_post() {
		Functions\when( 'get_post_meta' )->alias(
			static function ( $post_id ) {
				return 42 === $post_id ? 5 : 0;
			}
		);
		Functions\when( 'get_queried_object_id' )->justReturn( 999 );

		$block = new WP_Block( array( 'postId' => 42 ) );

		$this->assertSame( '5', ucf_brand_binding_section_number( array(), $block ) );
	}

	/**
	 * Outside a post context the binding falls back to the queried object.
	 *
	 * @return void
	 */
	public function test_binding_falls_back_to_the_queried_object() {
		Functions\when( 'get_post_meta' )->alias(
			static function ( $post_id ) {
				return 7 === $post_id ? 3 : 0;
			}
		);
		Functions\when( 'get_queried_object_id' )->justReturn( 7 );

		$this->assertSame( '3', ucf_brand_binding_section_number( array(), null ) );
	}

	/**
	 * With a `label` argument the number expands into the hero eyebrow.
	 *
	 * @return void
	 */
	public function test_binding_expands_into_the_eyebrow_with_a_label() {
		Functions\when( 'get_post_meta' )->justReturn( 5 );
		Functions\when( 'get_queried_object_id' )->justReturn( 1 );

		$this->assertSame(
			'Brand Guidelines · Section 5',
			ucf_brand_binding_section_number( array( 'label' => 'Brand Guidelines' ), null )
		);
	}

	/**
	 * An unnumbered page returns '' whether or not a label was passed — otherwise the
	 * hero would print a bare "Brand Guidelines · Section " with nothing after it.
	 *
	 * @return void
	 */
	public function test_binding_returns_empty_for_an_unnumbered_page() {
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_queried_object_id' )->justReturn( 1 );

		$this->assertSame( '', ucf_brand_binding_section_number( array(), null ) );
		$this->assertSame(
			'',
			ucf_brand_binding_section_number( array( 'label' => 'Brand Guidelines' ), null )
		);
	}
}
