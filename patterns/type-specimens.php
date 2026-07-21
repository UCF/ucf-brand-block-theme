<?php
/**
 * Title: Type Specimens
 * Slug: ucf-brand/type-specimens
 * Categories: ucf-brand-blocks
 * Description: One row per typeface, showing the face at display size with its role and weight range.
 * Keywords: type, typography, specimen, typeface, font
 *
 * @package ucf-brand-block-theme
 */

?>
<!-- wp:group {"className":"is-style-specimen","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-specimen">
	<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
	<p class="is-style-eyebrow"><?php esc_html_e( 'Display · Oswald · 200–700', 'ucf-brand-block-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {"fontFamily":"display","fontSize":"heading-1","style":{"typography":{"lineHeight":"1.04","textTransform":"uppercase","letterSpacing":"0.01em"}}} -->
	<p class="has-display-font-family has-heading-1-font-size" style="letter-spacing:0.01em;line-height:1.04;text-transform:uppercase"><?php esc_html_e( 'Daring to build what’s next', 'ucf-brand-block-theme' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"is-style-specimen","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-specimen">
	<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
	<p class="is-style-eyebrow"><?php esc_html_e( 'Body · Montserrat · 100–900', 'ucf-brand-block-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {"fontFamily":"body","fontSize":"heading-3","style":{"typography":{"fontWeight":"600","lineHeight":"1.4"}}} -->
	<p class="has-body-font-family has-heading-3-font-size" style="font-weight:600;line-height:1.4"><?php esc_html_e( 'Every screen is the brand. ABCDEFGHIJKLM abcdefghijklm 0123456789', 'ucf-brand-block-theme' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"is-style-specimen","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-specimen">
	<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
	<p class="is-style-eyebrow"><?php esc_html_e( 'Technical · JetBrains Mono · 100–800', 'ucf-brand-block-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {"fontFamily":"mono","fontSize":"heading-4","style":{"typography":{"letterSpacing":"0.06em","textTransform":"uppercase"}}} -->
	<p class="has-mono-font-family has-heading-4-font-size" style="letter-spacing:0.06em;text-transform:uppercase">Pantone 124 · #EDB80D · 11.46:1</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
