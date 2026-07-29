<?php
/**
 * Title: Index
 * Slug: ucf-brand/index
 * Categories: ucf-brand-groups
 * Description: A numbered index of a section's contents — a lead-in heading beside a divided list of entries, each with a mono section number, title and short description.
 * Keywords: index, contents, toc, list, numbered, overview
 *
 * @package ucf-brand-block-theme
 */

/*
 * One row per entry, built from core blocks and theme tokens only — no pattern-local
 * CSS. The mono number reuses the `is-style-meta` label idiom in `text-secondary`
 * (gold on white fails AA at this size) via the block's own color control; titles are
 * H4 (kept off the H2-driven drawer, see
 * CLAUDE.md); rows are divided by a core Separator in the theme's `line` color.
 *
 * The four rows below are starter content — an editor edits them in place like any
 * other pattern. Numbers hang off section 02.
 */
?>
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:columns {"verticalAlignment":"top"} -->
<div class="wp-block-columns are-vertically-aligned-top">
	<!-- wp:column {"verticalAlignment":"top","width":"28%"} -->
	<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:28%">
		<!-- wp:heading {"level":3,"fontFamily":"display","fontSize":"heading-3","style":{"typography":{"textTransform":"uppercase","fontWeight":"700","lineHeight":"1.05"}}} -->
		<h3 class="wp-block-heading has-display-font-family has-heading-3-font-size" style="font-weight:700;line-height:1.05;text-transform:uppercase"><?php esc_html_e( 'UCF\'s Brand Writing Elements Include:', 'ucf-brand-block-theme' ); ?></h3>
		<!-- /wp:heading -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"verticalAlignment":"top","width":"72%"} -->
	<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:72%">
		<!-- wp:columns {"verticalAlignment":"top"} -->
		<div class="wp-block-columns are-vertically-aligned-top">
			<!-- wp:column {"verticalAlignment":"top","width":"14%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:14%">
				<!-- wp:paragraph {"className":"is-style-meta","textColor":"text-secondary","style":{"typography":{"fontWeight":"700"}}} -->
				<p class="is-style-meta has-text-secondary-color has-text-color" style="font-weight:700">02.01</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"40%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:40%">
				<!-- wp:heading {"level":4,"fontFamily":"display","fontSize":"heading-4","style":{"typography":{"textTransform":"uppercase","fontWeight":"700","lineHeight":"1.1"}}} -->
				<h4 class="wp-block-heading has-display-font-family has-heading-4-font-size" style="font-weight:700;line-height:1.1;text-transform:uppercase"><?php esc_html_e( 'Brand Voice', 'ucf-brand-block-theme' ); ?></h4>
				<!-- /wp:heading -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"46%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:46%">
				<!-- wp:paragraph {"textColor":"text-secondary"} -->
				<p class="has-text-secondary-color has-text-color"><?php esc_html_e( 'The characteristics of how we sound, complementing our brand personality', 'ucf-brand-block-theme' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:separator {"backgroundColor":"line","className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity has-text-color has-line-background-color is-style-wide"/>
		<!-- /wp:separator -->

		<!-- wp:columns {"verticalAlignment":"top"} -->
		<div class="wp-block-columns are-vertically-aligned-top">
			<!-- wp:column {"verticalAlignment":"top","width":"14%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:14%">
				<!-- wp:paragraph {"className":"is-style-meta","textColor":"text-secondary","style":{"typography":{"fontWeight":"700"}}} -->
				<p class="is-style-meta has-text-secondary-color has-text-color" style="font-weight:700">02.02</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"40%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:40%">
				<!-- wp:heading {"level":4,"fontFamily":"display","fontSize":"heading-4","style":{"typography":{"textTransform":"uppercase","fontWeight":"700","lineHeight":"1.1"}}} -->
				<h4 class="wp-block-heading has-display-font-family has-heading-4-font-size" style="font-weight:700;line-height:1.1;text-transform:uppercase"><?php esc_html_e( 'Brand Messages', 'ucf-brand-block-theme' ); ?></h4>
				<!-- /wp:heading -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"46%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:46%">
				<!-- wp:paragraph {"textColor":"text-secondary"} -->
				<p class="has-text-secondary-color has-text-color"><?php esc_html_e( 'Some key phrases and themes that highlight our strengths and impact', 'ucf-brand-block-theme' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:separator {"backgroundColor":"line","className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity has-text-color has-line-background-color is-style-wide"/>
		<!-- /wp:separator -->

		<!-- wp:columns {"verticalAlignment":"top"} -->
		<div class="wp-block-columns are-vertically-aligned-top">
			<!-- wp:column {"verticalAlignment":"top","width":"14%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:14%">
				<!-- wp:paragraph {"className":"is-style-meta","textColor":"text-secondary","style":{"typography":{"fontWeight":"700"}}} -->
				<p class="is-style-meta has-text-secondary-color has-text-color" style="font-weight:700">02.03</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"40%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:40%">
				<!-- wp:heading {"level":4,"fontFamily":"display","fontSize":"heading-4","style":{"typography":{"textTransform":"uppercase","fontWeight":"700","lineHeight":"1.1"}}} -->
				<h4 class="wp-block-heading has-display-font-family has-heading-4-font-size" style="font-weight:700;line-height:1.1;text-transform:uppercase"><?php esc_html_e( 'General Standards and Style', 'ucf-brand-block-theme' ); ?></h4>
				<!-- /wp:heading -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"46%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:46%">
				<!-- wp:paragraph {"textColor":"text-secondary"} -->
				<p class="has-text-secondary-color has-text-color"><?php esc_html_e( 'Details big and small for consistency across everything we write', 'ucf-brand-block-theme' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:separator {"backgroundColor":"line","className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity has-text-color has-line-background-color is-style-wide"/>
		<!-- /wp:separator -->

		<!-- wp:columns {"verticalAlignment":"top"} -->
		<div class="wp-block-columns are-vertically-aligned-top">
			<!-- wp:column {"verticalAlignment":"top","width":"14%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:14%">
				<!-- wp:paragraph {"className":"is-style-meta","textColor":"text-secondary","style":{"typography":{"fontWeight":"700"}}} -->
				<p class="is-style-meta has-text-secondary-color has-text-color" style="font-weight:700">02.04</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"40%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:40%">
				<!-- wp:heading {"level":4,"fontFamily":"display","fontSize":"heading-4","style":{"typography":{"textTransform":"uppercase","fontWeight":"700","lineHeight":"1.1"}}} -->
				<h4 class="wp-block-heading has-display-font-family has-heading-4-font-size" style="font-weight:700;line-height:1.1;text-transform:uppercase"><?php esc_html_e( 'Standards by Content Type', 'ucf-brand-block-theme' ); ?></h4>
				<!-- /wp:heading -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"46%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:46%">
				<!-- wp:paragraph {"textColor":"text-secondary"} -->
				<p class="has-text-secondary-color has-text-color"><?php esc_html_e( 'Recommendations or standards for specific mediums or purposes', 'ucf-brand-block-theme' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:separator {"backgroundColor":"line","className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity has-text-color has-line-background-color is-style-wide"/>
		<!-- /wp:separator -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
