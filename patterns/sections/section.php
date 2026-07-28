<?php
/**
 * Title: Section
 * Slug: ucf-brand/section
 * Categories: ucf-brand-sections
 * Description: A full-width content section: an H2 (with its subsection badge) and intro paragraphs over a drop zone for group patterns. Give it a background from the block's color control to make it read as a band.
 * Keywords: section, band, heading, intro, drop zone, container
 *
 * @package ucf-brand-block-theme
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"brand-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull brand-section">
	<!-- wp:heading -->
	<h2 class="wp-block-heading"><?php esc_html_e( 'Section Heading', 'ucf-brand-block-theme' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'A supporting paragraph with a little more detail. Keep this to a sentence or two before the section’s content begins.', 'ucf-brand-block-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"layout":{"type":"constrained"}} -->
	<div class="wp-block-group"></div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
