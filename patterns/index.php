<?php
/**
 * Title: Index
 * Slug: ucf-brand/index
 * Categories: ucf-brand-sections
 * Description: A numbered index of a section's contents — a lead-in heading beside a divided list of entries, each with a mono section number, title and short description.
 * Keywords: index, contents, toc, list, numbered, overview
 *
 * @package ucf-brand-block-theme
 */

/*
 * One row per entry, built from core blocks and theme tokens only — no pattern-local
 * CSS. The mono gold number reuses the `is-style-meta` label idiom recolored via the
 * block's own color control; titles are H4 (kept off the H2-driven drawer, see
 * CLAUDE.md); rows are divided by a core Separator in the theme's `line` color.
 *
 * `$ucf_index_section` is the two-digit section number the entries hang off; each entry
 * is [ title, description ].
 */
$ucf_index_section = '02';
$ucf_index_entries = array(
	array(
		__( 'Brand Voice', 'ucf-brand-block-theme' ),
		__( 'The characteristics of how we sound, complementing our brand personality', 'ucf-brand-block-theme' ),
	),
	array(
		__( 'Brand Messages', 'ucf-brand-block-theme' ),
		__( 'Some key phrases and themes that highlight our strengths and impact', 'ucf-brand-block-theme' ),
	),
	array(
		__( 'General Standards and Style', 'ucf-brand-block-theme' ),
		__( 'Details big and small for consistency across everything we write', 'ucf-brand-block-theme' ),
	),
	array(
		__( 'Standards by Content Type', 'ucf-brand-block-theme' ),
		__( 'Recommendations or standards for specific mediums or purposes', 'ucf-brand-block-theme' ),
	),
);
?>
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
		<?php
		foreach ( $ucf_index_entries as $ucf_index_i => $ucf_index_entry ) :
			$ucf_index_number = $ucf_index_section . '.' . str_pad( (string) ( $ucf_index_i + 1 ), 2, '0', STR_PAD_LEFT );
			?>
		<!-- wp:columns {"verticalAlignment":"top"} -->
		<div class="wp-block-columns are-vertically-aligned-top">
			<!-- wp:column {"verticalAlignment":"top","width":"14%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:14%">
				<!-- wp:paragraph {"className":"is-style-meta","textColor":"gold","style":{"typography":{"fontWeight":"700"}}} -->
				<p class="is-style-meta has-text-color has-gold-color" style="font-weight:700"><?php echo esc_html( $ucf_index_number ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"40%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:40%">
				<!-- wp:heading {"level":4,"fontFamily":"display","fontSize":"heading-4","style":{"typography":{"textTransform":"uppercase","fontWeight":"700","lineHeight":"1.1"}}} -->
				<h4 class="wp-block-heading has-display-font-family has-heading-4-font-size" style="font-weight:700;line-height:1.1;text-transform:uppercase"><?php echo esc_html( $ucf_index_entry[0] ); ?></h4>
				<!-- /wp:heading -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"46%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:46%">
				<!-- wp:paragraph {"textColor":"text-secondary"} -->
				<p class="has-text-secondary-color has-text-color"><?php echo esc_html( $ucf_index_entry[1] ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:separator {"backgroundColor":"line","className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity has-text-color has-line-background-color is-style-wide"/>
		<!-- /wp:separator -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
