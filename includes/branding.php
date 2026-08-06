<?php
/**
 * The university lockup in the site header.
 *
 * `ucf-brand/site-mark` is theme glue rather than a distributable design block, so it lives
 * here with the asset path it renders instead of in src/blocks/, which is static-only. Same
 * reasoning as includes/section-nav.php: a dynamic block belongs with the data it renders.
 * See docs/architecture.md.
 *
 * The mark is a file on disk, never markup. Swapping it is either dropping a replacement at
 * the path below or filtering `ucf_brand_site_mark_src` from a child theme or a plugin —
 * nothing about the logo is baked into a template part, a stylesheet, or a media-library
 * attachment, and none of those routes needs a rebuild.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Register the header's university lockup block.
 *
 * The destination and the wordmark are attributes with defaults rather than hard-coded
 * strings, so parts/header.html can override either without this file changing. They are
 * not translated: both name a specific institution.
 *
 * @return void
 */
function ucf_brand_register_site_mark() {
	register_block_type(
		'ucf-brand/site-mark',
		array(
			'api_version'     => 3,
			'attributes'      => array(
				'url'   => array(
					'type'    => 'string',
					'default' => 'https://www.ucf.edu',
				),
				'label' => array(
					'type'    => 'string',
					'default' => 'University of Central Florida',
				),
			),
			'render_callback' => 'ucf_brand_render_site_mark',
		)
	);
}
add_action( 'init', 'ucf_brand_register_site_mark' );

/**
 * The URL of the mark shown in the header.
 *
 * Returning an empty string is supported and drops the image, leaving the wordmark alone —
 * that is the seam for a deployment that wants the text lockup only.
 *
 * @return string Image URL, or '' to render no mark.
 */
function ucf_brand_site_mark_src() {
	/**
	 * Filters the header mark's image URL.
	 *
	 * @param string $src Absolute URL of the mark.
	 */
	return apply_filters(
		'ucf_brand_site_mark_src',
		get_theme_file_uri( 'assets/images/UCF-STACKED-BOLDGOLD.svg' )
	);
}

/**
 * Render the header lockup: the mark, a rule, and the university's name.
 *
 * One link wraps both halves rather than two links pointing at the same place, so assistive
 * technology gets a single control with a single name. That name is the `aria-label` — which
 * is why the image is `alt=""` and why the wordmark can be hidden on narrow screens (see
 * _header.scss) without the link going nameless.
 *
 * No width or height is emitted. The mark's box is `height` plus `width: auto` in CSS, so a
 * replacement with different proportions needs no change here or there.
 *
 * @param array $attributes Block attributes: `url` and `label`.
 * @return string Lockup markup.
 */
function ucf_brand_render_site_mark( $attributes ) {
	$url   = isset( $attributes['url'] ) ? $attributes['url'] : 'https://www.ucf.edu';
	$label = isset( $attributes['label'] ) ? $attributes['label'] : 'University of Central Florida';
	$src   = ucf_brand_site_mark_src();

	$mark = '';

	if ( '' !== $src ) {
		$mark = sprintf(
			'<span class="brand-header__mark"><img src="%s" alt="" /></span>',
			esc_url( $src )
		);
	}

	return sprintf(
		'<a class="brand-header__lockup" href="%1$s" aria-label="%2$s">%3$s<span class="brand-header__wordmark">%4$s</span></a>',
		esc_url( $url ),
		esc_attr( $label ),
		$mark,
		esc_html( $label )
	);
}
