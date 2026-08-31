<?php
/**
 * Characters a word processor leaves behind.
 *
 * The table in `ucf_brand_paste_artifacts()` is a list of editorial decisions, not a lookup:
 * three of its near neighbours are deliberately *not* in it, and the cost of adding one by
 * accident is corrupted text rather than a wrong pixel. These tests pin both halves — what is
 * replaced, and what must survive untouched.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests;

/**
 * @covers ::ucf_brand_normalize_paste_artifacts
 * @covers ::ucf_brand_clean_saved_paste_artifacts
 */
final class PasteArtifactsTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->loadInclude( 'paste-artifacts' );
	}

	/**
	 * The case that shipped: a narrow no-break space inside a bold label, and another
	 * standing in for the space before a parenthesis.
	 *
	 * The doubled gap is the point. U+202F is not collapsible whitespace, so the browser
	 * folds neither it nor the ordinary space beside it; once both are ordinary spaces, HTML
	 * collapses the pair on its own and no PHP has to.
	 *
	 * @return void
	 */
	public function test_replaces_the_narrow_no_break_space_with_an_ordinary_space() {
		$this->assertSame(
			'<strong>Goals: </strong> Clearly define your objectives (brand awareness)',
			ucf_brand_normalize_paste_artifacts(
				"<strong>Goals:\u{202F}</strong> Clearly define your objectives\u{202F}(brand awareness)"
			)
		);
	}

	/**
	 * @return void
	 */
	public function test_replaces_the_figure_space_with_an_ordinary_space() {
		$this->assertSame( '1 000 students', ucf_brand_normalize_paste_artifacts( "1\u{2007}000 students" ) );
	}

	/**
	 * @dataProvider zeroWidthProvider
	 *
	 * @param string $character A zero-width character.
	 * @param string $label     Its name, for the failure message.
	 * @return void
	 */
	public function test_drops_zero_width_formatting_characters( $character, $label ) {
		$this->assertSame(
			'wordmark',
			ucf_brand_normalize_paste_artifacts( 'word' . $character . 'mark' ),
			$label . ' should be removed, not replaced — it occupies no width in the copy.'
		);
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function zeroWidthProvider() {
		return array(
			'zero width space' => array( "\u{200B}", 'U+200B ZERO WIDTH SPACE' ),
			'word joiner'      => array( "\u{2060}", 'U+2060 WORD JOINER' ),
			'byte-order mark'  => array( "\u{FEFF}", 'U+FEFF ZERO WIDTH NO-BREAK SPACE' ),
		);
	}

	/**
	 * The three near neighbours that must survive.
	 *
	 * A no-break space is authored on purpose — the theme's own patterns use `&nbsp;`. The
	 * zero-width joiner and non-joiner are letters' worth of meaning in several scripts and
	 * are what fuse a multi-part emoji into one glyph; a soft hyphen is invisible until a
	 * line breaks at it, which is its whole job. Stripping any of them corrupts text rather
	 * than cleaning it.
	 *
	 * @dataProvider preservedProvider
	 *
	 * @param string $text  Text that must come back unchanged.
	 * @param string $label What it is.
	 * @return void
	 */
	public function test_leaves_meaningful_characters_alone( $text, $label ) {
		$this->assertSame( $text, ucf_brand_normalize_paste_artifacts( $text ), $label );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function preservedProvider() {
		return array(
			'no-break space'    => array( "Section\u{00A0}3", 'U+00A0 is an editorial choice, not an artifact.' ),
			'zero width joiner' => array( "\u{1F468}\u{200D}\u{1F393}", 'U+200D fuses an emoji sequence into one glyph.' ),
			'non-joiner'        => array( "\u{0645}\u{200C}\u{0646}", 'U+200C is meaningful in Arabic and Indic scripts.' ),
			'soft hyphen'       => array( "photo\u{00AD}graphy", 'U+00AD only appears when a line breaks there.' ),
		);
	}

	/**
	 * @return void
	 */
	public function test_returns_non_strings_untouched() {
		$this->assertNull( ucf_brand_normalize_paste_artifacts( null ) );
		$this->assertSame( array(), ucf_brand_normalize_paste_artifacts( array() ) );
		$this->assertSame( '', ucf_brand_normalize_paste_artifacts( '' ) );
	}

	/**
	 * All three authored fields are cleaned, and nothing else in the row is rewritten.
	 *
	 * @return void
	 */
	public function test_cleans_every_authored_field_on_save() {
		$data = ucf_brand_clean_saved_paste_artifacts(
			array(
				'post_content' => "Goals:\u{202F}one",
				'post_title'   => "Logo\u{2060}Guide",
				'post_excerpt' => "A\u{200B}summary",
				'post_status'  => "publish\u{202F}",
			)
		);

		$this->assertSame( 'Goals: one', $data['post_content'] );
		$this->assertSame( 'LogoGuide', $data['post_title'] );
		$this->assertSame( 'Asummary', $data['post_excerpt'] );
		$this->assertSame( "publish\u{202F}", $data['post_status'], 'Only authored text is normalized.' );
	}

	/**
	 * A partial row is what an autosave or a meta-only update hands the filter.
	 *
	 * @return void
	 */
	public function test_tolerates_a_row_missing_those_fields() {
		$this->assertSame(
			array( 'ID' => 12 ),
			ucf_brand_clean_saved_paste_artifacts( array( 'ID' => 12 ) )
		);
	}
}
