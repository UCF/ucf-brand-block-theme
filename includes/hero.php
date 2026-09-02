<?php
/**
 * The page hero's per-page light/dark treatment.
 *
 * The hero is a static block in templates/page.html, so the composition class it carries is
 * one setting for every page the template renders — which is the whole reason this file
 * exists. `ucf_brand_hero_treatment` names a treatment for one page and this swaps the class
 * on the way out, leaving the template as the site-wide default.
 *
 * WHY: a `render_block` filter rather than a dynamic hero. The block stays static — its
 * markup is still what `save()` wrote, so nothing about the editor, the template or block
 * validity changes; only the class on the wrapper is rewritten, and only when a page asks.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * The treatments a page may choose between.
 *
 * SYNC: four places. templates/page.html ships one of these as the `is-style-*` class on the
 * hero wrapper, the meta's sanitize callback in includes/meta.php allows exactly these, the
 * sidebar offers them in src/js/editor/hero-treatment.js, and src/scss/_hero.scss paints both
 * spellings — the template's `is-style-*` and the canvas's `is-hero-treatment-*`.
 *
 * @return string[] Treatment slugs.
 */
function ucf_brand_hero_treatments() {
	return array( 'dark', 'light' );
}

/**
 * The class one page's hero should carry, if it overrides the template.
 *
 * @param int $post_id Page to read.
 * @return string `is-style-*` class, or '' when the page follows the template.
 */
function ucf_brand_hero_treatment_class( $post_id ) {
	$treatment = get_post_meta( (int) $post_id, 'ucf_brand_hero_treatment', true );

	return in_array( $treatment, ucf_brand_hero_treatments(), true )
		? 'is-style-' . $treatment
		: '';
}

/**
 * Swap the composition class on the hero for the current page's own.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block.
 * @return string Block HTML, with the page's treatment on the wrapper.
 */
function ucf_brand_apply_hero_treatment( $block_content, $block ) {
	if ( empty( $block['blockName'] ) || 'ucf-brand/page-hero' !== $block['blockName'] ) {
		return $block_content;
	}

	$class = ucf_brand_hero_treatment_class( get_the_ID() );

	if ( '' === $class ) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag() ) {
		return $block_content;
	}

	// FIX: every treatment comes off first. Adding one on top of the template's left both
	// classes on the wrapper, and which of the two won was then down to their order in
	// main.css rather than to the page's choice.
	foreach ( ucf_brand_hero_treatments() as $treatment ) {
		$processor->remove_class( 'is-style-' . $treatment );
	}

	$processor->add_class( $class );

	return $processor->get_updated_html();
}
add_filter( 'render_block', 'ucf_brand_apply_hero_treatment', 10, 2 );
