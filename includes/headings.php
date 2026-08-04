<?php
/**
 * Server-side H2 anchors and section extraction.
 *
 * H2s are structural here (see CLAUDE.md): the drawer sub-nav, the copy-link affordance
 * and search's subsection deep links all address them by `id`. This file is the single
 * owner of that id.
 *
 * It used to be brand-nav.js, which derived ids in the browser. Anything server-side that
 * wants to emit a `/page/#heading` link — search being the first — would have had to
 * reproduce that slug byte for byte, and PHP's `sanitize_title()` does not: it
 * transliterates accents and handles entities differently, so "Photography & Video" came
 * out one way in the browser and another in PHP, and the deep link silently landed at the
 * top of the page. Generating the id during `render_block` puts it in the HTML the server
 * sends, so the browser reads an id rather than inventing one.
 *
 * `ucf_brand_heading_slug()` is a deliberate port of the old JS `slugify()` rather than a
 * call to `sanitize_title()`, so anchors that were already shared or bookmarked keep
 * resolving.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Turn heading text into a URL-safe id.
 *
 * Port of the `slugify()` that shipped in brand-nav.js, kept step-for-step so previously
 * generated anchors still resolve: lowercase, trim, drop everything outside
 * `[a-z0-9\s-]`, then collapse whitespace and hyphen runs to single hyphens. Non-ASCII is
 * dropped rather than transliterated, and a trailing hyphen is preserved, because that is
 * what the browser did.
 *
 * @param string $text Raw heading text; may contain markup or entities.
 * @return string Slug, possibly empty.
 */
function ucf_brand_heading_slug( $text ) {
	$text = wp_strip_all_tags( $text );
	$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

	// JavaScript's `\s` matches the no-break space; PCRE's does not without /u, and
	// `&nbsp;` is common in editor-authored headings. Normalize before matching.
	$text = str_replace( "\xc2\xa0", ' ', $text );

	// Byte-wise on purpose: UTF-8 lead and continuation bytes all sit above the ASCII
	// range this touches, and anything non-ASCII is stripped on the next line anyway.
	$text = strtolower( $text );
	$text = trim( $text );
	$text = preg_replace( '/[^a-z0-9\s-]/', '', $text );
	$text = preg_replace( '/\s+/', '-', $text );
	$text = preg_replace( '/-+/', '-', $text );

	return $text;
}

/**
 * Disambiguate a slug against the ones already used on the same page.
 *
 * Mirrors the browser's old collision behavior exactly — the first duplicate becomes
 * `slug-2`, not `slug-1` — so a page with two "Overview" H2s keeps the anchors it had.
 *
 * @param string   $slug Base slug.
 * @param string[] $used Slugs already assigned, appended to by reference.
 * @return string Unique slug.
 */
function ucf_brand_unique_heading_slug( $slug, array &$used ) {
	$candidate = $slug;
	$suffix    = 1;

	while ( in_array( $candidate, $used, true ) ) {
		++$suffix;
		$candidate = $slug . '-' . $suffix;
	}

	$used[] = $candidate;

	return $candidate;
}

/**
 * Give every rendered H2 an id.
 *
 * An id the author set in the block's Advanced panel always wins — this only fills the
 * gap. The used-slug registry resets when the post being rendered changes, which keeps
 * numbering per-page rather than per-request.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block.
 * @return string Block HTML, with an id on the H2.
 */
function ucf_brand_add_heading_anchor( $block_content, $block ) {
	if ( empty( $block['blockName'] ) || 'core/heading' !== $block['blockName'] ) {
		return $block_content;
	}

	static $used    = array();
	static $post_id = null;

	$current = get_the_ID();

	if ( $current !== $post_id ) {
		$post_id = $current;
		$used    = array();
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag( array( 'tag_name' => 'H2' ) ) ) {
		return $block_content;
	}

	if ( null !== $processor->get_attribute( 'id' ) ) {
		return $block_content;
	}

	$slug = ucf_brand_heading_slug( $block_content );

	if ( '' === $slug ) {
		$slug = 'section';
	}

	$processor->set_attribute( 'id', ucf_brand_unique_heading_slug( $slug, $used ) );

	return $processor->get_updated_html();
}
add_filter( 'render_block', 'ucf_brand_add_heading_anchor', 10, 2 );

/**
 * Split a page's stored content into its H2 sections.
 *
 * Reads `post_content` directly rather than rendering it: `core/heading` is a static
 * block, so its H2s are literal HTML in the stored markup, and rendering every result row
 * on a search page would be wasteful. Walking the same H2s in the same order as
 * `ucf_brand_add_heading_anchor()` and reusing its two slug helpers is what keeps the
 * anchors this returns identical to the ones on the page itself.
 *
 * Content that precedes the first H2 is not a section and is skipped.
 *
 * @param WP_Post $post Post to read.
 * @return array[] Sections, each with `id`, `title` and `text` (tags stripped).
 */
function ucf_brand_get_post_sections( $post ) {
	if ( ! $post instanceof WP_Post || '' === trim( $post->post_content ) ) {
		return array();
	}

	$chunks = preg_split(
		'#<h2\b([^>]*)>(.*?)</h2>#is',
		$post->post_content,
		-1,
		PREG_SPLIT_DELIM_CAPTURE
	);

	// [ pre-heading content, attrs, inner, body, attrs, inner, body, ... ].
	$total = is_array( $chunks ) ? count( $chunks ) : 0;

	if ( $total < 4 ) {
		return array();
	}

	$sections = array();
	$used     = array();

	for ( $i = 1; $i + 2 <= $total; $i += 3 ) {
		$attrs = $chunks[ $i ];
		$inner = $chunks[ $i + 1 ];
		$body  = isset( $chunks[ $i + 2 ] ) ? $chunks[ $i + 2 ] : '';

		$title = trim( html_entity_decode( wp_strip_all_tags( $inner ), ENT_QUOTES, 'UTF-8' ) );

		if ( '' === $title ) {
			continue;
		}

		// An author-set anchor wins here for the same reason it does on render.
		if ( preg_match( '/\bid=["\']([^"\']+)["\']/i', $attrs, $match ) ) {
			$id     = $match[1];
			$used[] = $id;
		} else {
			$slug = ucf_brand_heading_slug( $inner );
			$id   = ucf_brand_unique_heading_slug( '' === $slug ? 'section' : $slug, $used );
		}

		// strip_tags() drops HTML comments too, which is what removes the block
		// delimiters and their JSON attributes — without that, a search for "block"
		// would match the `wp-block-*` classes in every section.
		$sections[] = array(
			'id'    => $id,
			'title' => $title,
			'text'  => trim( html_entity_decode( wp_strip_all_tags( $body ), ENT_QUOTES, 'UTF-8' ) ),
		);
	}

	return $sections;
}
