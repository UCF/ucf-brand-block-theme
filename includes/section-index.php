<?php
/**
 * The on-page index block.
 *
 * A numbered jump list of the current page's H2 subsections — the same set the drawer's
 * sub-nav is built from, and numbered with the same "page.subsection" label the H2 badges
 * print. Server-rendered, so it lives here with the data it renders rather than in
 * src/blocks/ (which is static-only); see CLAUDE.md.
 *
 * WHY: nothing about the list is authored. Headings are read through
 * `ucf_brand_get_post_sections()` in includes/headings.php, which is the only thing that
 * guarantees the anchors here match the ids the page actually renders — deriving them a
 * second time is the bug that file exists to prevent. Add, rename, reorder or delete an
 * H2 and the index follows on the next render.
 *
 * The one authored part is the per-entry description, stored in the `descriptions`
 * attribute keyed by heading text. It is rich text rather than a plain string: a description
 * takes the ordinary inline formatting — emphasis, a link, and the theme's own badge spans —
 * the way any other short line of copy in the guide does. Keyed by text and not by position, so reordering the
 * page keeps every description with its own heading; renaming a heading is what orphans
 * one, and the row then renders without a description until an editor retypes it.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Register the index block.
 *
 * SYNC: the attributes below are declared again client-side in
 * src/js/editor/section-index.js. A dynamic block needs both halves, and the editor's
 * copy is what the description fields write into.
 *
 * @return void
 */
function ucf_brand_register_section_index() {
	register_block_type(
		'ucf-brand/section-index',
		array(
			'api_version'     => 3,
			'attributes'      => array(
				'heading'      => array(
					'type' => 'string',
				),
				// WHY: no `default`. PHP has no empty-object literal — `array()` serializes
				// as `[]` — and an array reaching the editor where an object belongs makes
				// the first description write fail.
				'descriptions' => array(
					'type' => 'object',
				),
			),
			'uses_context'    => array( 'postId' ),
			'supports'        => array(
				'html'     => false,
				'reusable' => false,
			),
			'render_callback' => 'ucf_brand_render_section_index',
		)
	);
}
add_action( 'init', 'ucf_brand_register_section_index' );

/**
 * The inline markup a description may carry.
 *
 * SAFETY: an allowlist, not `esc_html()` and not `wp_kses_post()`. Descriptions arrive from
 * the block's own attribute JSON, which anyone who can edit a page can write by hand in the
 * code editor, so this is the boundary. It is the inline set the editor's own formats emit —
 * `class` on the span is what makes that true of the badge formats, where the tone *is* the
 * class (src/js/badge-format.js, src/scss/_badge.scss).
 *
 * SYNC: `allowedFormats` on the description field in src/js/editor/section-index.js offers
 * exactly this set. A format offered there and missing here is one an author can apply and
 * then watch disappear on save.
 *
 * @return array<string, array<string, bool>> Tag/attribute map for wp_kses().
 */
function ucf_brand_section_index_allowed_html() {
	return array(
		'span'   => array( 'class' => true ),
		'strong' => array(),
		'em'     => array(),
		'a'      => array(
			'href'   => true,
			'rel'    => true,
			'target' => true,
		),
		'br'     => array(),
	);
}

/**
 * Render the index from the current page's H2s.
 *
 * @param array         $attributes Block attributes.
 * @param string        $content    Inner content. Unused; the block saves nothing.
 * @param WP_Block|null $block      Block instance, for its `postId` context.
 * @return string Index markup, or '' when the page has no H2s.
 */
function ucf_brand_render_section_index( $attributes = array(), $content = '', $block = null ) {
	$post_id = 0;

	// The `postId` context is what makes this render the right page inside a query loop or
	// an editor preview; the queried object is the fallback for a bare front-end render.
	if ( $block instanceof WP_Block && ! empty( $block->context['postId'] ) ) {
		$post_id = (int) $block->context['postId'];
	}

	if ( ! $post_id ) {
		$post_id = (int) get_queried_object_id();
	}

	$sections = ucf_brand_get_post_sections( get_post( $post_id ) );

	if ( empty( $sections ) ) {
		return '';
	}

	$descriptions = isset( $attributes['descriptions'] ) && is_array( $attributes['descriptions'] )
		? $attributes['descriptions']
		: array();

	// SYNC: `--brand-section` in _sections.scss prints the same "page.subsection" pair on
	// each H2 badge, so an entry here and its heading downpage read as one number. An
	// unnumbered page hides the badge; this drops the number column for the same reason.
	$number = ucf_brand_format_number( get_post_meta( $post_id, 'ucf_brand_number', true ) );

	$items = '';

	foreach ( $sections as $position => $section ) {
		$label = '' === $number
			? ''
			: sprintf(
				'<span class="brand-index__num is-style-meta">%s</span>',
				esc_html( $number . '.' . ( $position + 1 ) )
			);

		$description = isset( $descriptions[ $section['title'] ] )
			? trim( wp_kses( (string) $descriptions[ $section['title'] ], ucf_brand_section_index_allowed_html() ) )
			: '';

		// The description sits outside the anchor deliberately, as it does in
		// ucf_brand_render_search_subsections(): inside, it joins the link's accessible
		// name and every entry announces as a paragraph of prose.
		// A11Y: the title is the entry's heading, inside the link rather than around it, so
		// heading navigation lands on "Brand Essence" and not on "1.1 Brand Essence". An `a`
		// takes flow content — this is the transparent content model, not a nesting bug.
		$items .= sprintf(
			'<li class="brand-index__item"><a class="brand-index__link" href="#%1$s">%2$s<h3 class="brand-index__label">%3$s</h3></a>%4$s</li>',
			esc_attr( $section['id'] ),
			$label,
			esc_html( $section['title'] ),
			// SAFETY: already through wp_kses() above — escaping it again would print the
			// author's own `<span class="badge">` as text.
			'' === $description ? '' : '<p class="brand-index__desc">' . $description . '</p>'
		);
	}

	$heading  = isset( $attributes['heading'] ) ? trim( (string) $attributes['heading'] ) : '';
	$title_id = wp_unique_id( 'brand-index-title-' );

	// A11Y: the lead-in heading names the nav when there is one, so the list is announced as
	// "UCF's Brand Writing Elements Include:" rather than by a generic label. Without one the
	// nav still needs a name, because a page can hold more than one navigation landmark.
	$naming = '' === $heading
		? sprintf( ' aria-label="%s"', esc_attr__( 'On this page', 'ucf-brand-block-theme' ) )
		: sprintf( ' aria-labelledby="%s"', esc_attr( $title_id ) );

	// A11Y: an H2, so the index sits in the outline as a peer of the sections it lists and
	// its entries nest under it. SYNC: it is excluded from the badge counter (_sections.scss)
	// and from the drawer sub-nav and copy-link anchors (src/js/brand-nav.js) by that class —
	// it names the list, it is not a section of the page.
	$lead = '' === $heading
		? ''
		: sprintf(
			'<h2 class="brand-index__title" id="%1$s">%2$s</h2>',
			esc_attr( $title_id ),
			esc_html( $heading )
		);

	return sprintf(
		'<nav %1$s%2$s>%3$s<ul class="brand-index__list">%4$s</ul></nav>',
		get_block_wrapper_attributes( array( 'class' => 'brand-index' ) ),
		$naming,
		$lead,
		$items
	);
}
