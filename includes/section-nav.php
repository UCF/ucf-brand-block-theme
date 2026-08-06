<?php
/**
 * The drawer's navigation block.
 *
 * Server-rendered from the ordered section list in includes/sections.php, which is why it
 * lives here rather than in src/blocks/: src/blocks/ is static-only, and a dynamic block belongs
 * with the data it renders. See CLAUDE.md.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Register the drawer's dynamic navigation block.
 *
 * This is theme glue, not a distributable design block. It emits the `.brand-nav` markup
 * that _drawer.scss styles and brand-nav.js augments (H2 sub-nav, current-item highlight).
 *
 * @return void
 */
function ucf_brand_register_section_nav() {
	register_block_type(
		'ucf-brand/section-nav',
		array(
			'api_version'     => 3,
			'render_callback' => 'ucf_brand_render_section_nav',
		)
	);
}
add_action( 'init', 'ucf_brand_register_section_nav' );

/**
 * Render the drawer navigation from the ordered section list.
 *
 * @return string Navigation markup, or '' when there are no numbered sections.
 */
function ucf_brand_render_section_nav() {
	$sections = ucf_brand_get_ordered_sections();

	if ( empty( $sections ) ) {
		return '';
	}

	$items = '';

	foreach ( $sections as $section ) {
		$items .= sprintf(
			'<li class="brand-nav__item%1$s"><a class="brand-nav__link" href="%2$s"%3$s><span class="brand-nav__num">%4$s</span><span class="brand-nav__text">%5$s</span><span class="brand-nav__icon" aria-hidden="true"></span></a></li>',
			$section['is_current'] ? ' is-current' : '',
			esc_url( $section['url'] ),
			$section['is_current'] ? ' aria-current="page"' : '',
			esc_html( $section['label'] ),
			esc_html( $section['title'] )
		);
	}

	return sprintf(
		'<nav class="brand-nav" aria-label="%1$s"><ul class="brand-nav__list">%2$s</ul></nav>',
		esc_attr__( 'Brand sections', 'ucf-brand-block-theme' ),
		$items
	);
}
