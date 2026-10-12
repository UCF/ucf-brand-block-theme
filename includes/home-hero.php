<?php
/**
 * The home hero's background video, held back from phones.
 *
 * PERF: the video is hidden below $breakpoint-video, but `autoplay` makes a hidden video
 * download anyway — 6.8 MB a visit on the live site, often twice. This parks the source so
 * nothing fetches it until src/js/hero-video.js puts it back on a wide screen.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Move the home hero video's `src` aside and stop it autoplaying.
 *
 * WHY: a `render_block` filter, so the cover stays a static core block — what is saved in
 * the template is unchanged, and so is the editor.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block.
 * @return string Block HTML, with the video unloaded.
 */
function ucf_brand_defer_home_hero_video( $block_content, $block ) {
	if ( empty( $block['blockName'] ) || 'core/cover' !== $block['blockName'] ) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );

	// SYNC: the class templates/front-page.html puts on the cover.
	if ( ! $processor->next_tag() || ! $processor->has_class( 'brand-home-hero__band' ) ) {
		return $block_content;
	}

	if ( ! $processor->next_tag( 'video' ) ) {
		return $block_content;
	}

	$src = $processor->get_attribute( 'src' );

	if ( ! is_string( $src ) || '' === $src ) {
		return $block_content;
	}

	// SYNC: src/js/hero-video.js reads `data-brand-src`. WHY: not `data-src`, which lazy-load
	// plugins claim and would load on scroll — on a phone, the exact download this prevents.
	$processor->set_attribute( 'data-brand-src', $src );
	$processor->remove_attribute( 'src' );
	$processor->remove_attribute( 'autoplay' );
	$processor->set_attribute( 'preload', 'none' );

	return $processor->get_updated_html();
}
add_filter( 'render_block', 'ucf_brand_defer_home_hero_video', 10, 2 );
