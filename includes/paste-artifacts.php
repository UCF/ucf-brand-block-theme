<?php
/**
 * Word-processor characters that survive a paste.
 *
 * Copy from Word, Google Docs or a PDF and the text arrives carrying characters that look
 * like ordinary spaces in the editor and are not: a narrow no-break space where a plain
 * space belongs, a word joiner welding two words together, a stray byte-order mark. Nothing
 * in the editor shows them, so an author cannot see what to fix, and they reach the page.
 *
 * What that looks like on the front end, measured on a real section page carrying 191 of
 * them: `<strong>Goals:U+202F</strong> Clearly define…` renders a doubled gap after the
 * label, because U+202F is not collapsible whitespace and the ordinary space after it is —
 * two spaces, one of which HTML will not fold. And `objectivesU+202F(brand awareness` is a
 * space too narrow to read as one, which the browser may not break at, so a long phrase can
 * push past its column.
 *
 * The mapping is deliberately small, and what it leaves alone matters as much as what it
 * changes — see ucf_brand_paste_artifacts() for the reasoning per character.
 *
 * Two boundaries, because one alone is not enough:
 *
 * - **On save**, so the characters never reach the database from an editor session. This is
 *   the closest the theme can get to catching the paste itself: Gutenberg's raw-handling
 *   pipeline runs a fixed list of filters and exposes no hook to add one, so there is nowhere
 *   to intervene on the clipboard. `wp_insert_post_data` is the next boundary the content
 *   crosses, and unlike a paste handler it also covers imports, the REST API and WP-CLI.
 * - **On display**, because the archive already holds them. One section page carries 191, and
 *   they stay until something rewrites the row. Cleaning on render fixes every page now
 *   rather than as each is next edited.
 *
 * `tools/paste-artifacts.php` is the third piece: it reports what is already stored, and
 * rewrites it on request.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * The substitution table: what a pasted document leaves behind, and what it should be.
 *
 * Two groups. The first are spaces that are not spaces — they take a normal space, which is
 * collapsible, breakable and the width the copy was set in. The second are zero-width
 * formatting characters that carry no meaning in prose and are simply dropped.
 *
 * Three near neighbours are deliberately absent:
 *
 * - U+00A0 NO-BREAK SPACE is authored on purpose. `&nbsp;` is a legitimate editorial tool
 *   for holding "Section 3" or a unit on one line, and this theme's own patterns use it.
 * - U+200C / U+200D, the zero-width non-joiner and joiner, are *load-bearing*. A ZWJ is what
 *   makes a multi-part emoji one glyph, and both are letters' worth of meaning in Arabic and
 *   several Indic scripts. Stripping them corrupts text rather than cleaning it.
 * - U+00AD SOFT HYPHEN is invisible until a line breaks there, which is what it is for.
 *
 * @return array<string, string> Search => replace, for strtr().
 */
function ucf_brand_paste_artifacts() {
	return array(
		// Spaces that are not spaces.
		"\u{202F}" => ' ', // NARROW NO-BREAK SPACE — the one that actually shipped.
		"\u{2007}" => ' ', // FIGURE SPACE — digit-width and non-breaking, from aligned tables.
		// Zero-width formatting, meaningless in prose.
		"\u{200B}" => '', // ZERO WIDTH SPACE.
		"\u{2060}" => '', // WORD JOINER.
		"\u{FEFF}" => '', // ZERO WIDTH NO-BREAK SPACE, i.e. a byte-order mark mid-document.
	);
}

/**
 * Replace the paste artifacts in a run of text.
 *
 * `strtr()` rather than a regex: the table is a fixed set of byte sequences, no pattern is
 * involved, and strtr() takes the longest match at each position without backtracking.
 *
 * @param mixed $text Text to normalize. Non-strings are returned untouched, because filters
 *                    on `the_content` are handed other types by some plugins.
 * @return mixed Normalized text.
 */
function ucf_brand_normalize_paste_artifacts( $text ) {
	if ( ! is_string( $text ) || '' === $text ) {
		return $text;
	}

	return strtr( $text, ucf_brand_paste_artifacts() );
}

/*
 * WHY: priority 20, after `wptexturize()` and `wpautop()` at 10. Nothing in core's chain
 * emits these characters, so the order is not load-bearing for correctness — it runs late so
 * that what a reader sees is what this saw.
 *
 * The excerpt is filtered as well as the content: search results print excerpts, and an
 * auto-generated one is cut straight out of the same pasted copy.
 */
add_filter( 'the_content', 'ucf_brand_normalize_paste_artifacts', 20 );
add_filter( 'the_excerpt', 'ucf_brand_normalize_paste_artifacts', 20 );
add_filter( 'get_the_excerpt', 'ucf_brand_normalize_paste_artifacts', 20 );

/**
 * Clean the three authored fields on their way into the database.
 *
 * WHY: here rather than on `content_save_pre`, which only sees the content. One filter covers
 * the title and the excerpt too, and it runs for every route into `wp_insert_post()` — the
 * block editor's REST save, an import, WP-CLI — not just the editor.
 *
 * SAFETY: the data arrives slashed and stays slashed. Every sequence in the table is a
 * character in its own right, so nothing here can meet a backslash, split one, or change the
 * length of an escape.
 *
 * @param array $data Sanitized post data, slashed, on its way to the database.
 * @return array The same data with the artifacts normalized.
 */
function ucf_brand_clean_saved_paste_artifacts( $data ) {
	foreach ( array( 'post_content', 'post_title', 'post_excerpt' ) as $field ) {
		if ( isset( $data[ $field ] ) ) {
			$data[ $field ] = ucf_brand_normalize_paste_artifacts( $data[ $field ] );
		}
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'ucf_brand_clean_saved_paste_artifacts' );
