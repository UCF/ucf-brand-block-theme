<?php
/**
 * Title: Detail Card
 * Slug: ucf-brand/detail-card
 * Categories: ucf-brand-units
 * Description: A bordered card with a colored header bar over a list body. Switch the tone — Gold, Grey or Red — from the block Styles panel.
 * Keywords: detail card, card, do, dont, callout, header, list, tone
 *
 * @package ucf-brand-block-theme
 */

?>
<!-- wp:group {"className":"brand-detail-card is-style-detail-card-gold","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group brand-detail-card is-style-detail-card-gold">
	<!-- wp:paragraph {"className":"brand-detail-card__header"} -->
	<p class="brand-detail-card__header"><?php esc_html_e( 'Do', 'ucf-brand-block-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"className":"brand-detail-card__body","layout":{"type":"default"}} -->
	<div class="wp-block-group brand-detail-card__body">
		<!-- wp:list -->
		<ul class="wp-block-list">
			<!-- wp:list-item -->
			<li><?php esc_html_e( 'Keep clear space of at least the height of the “U.”', 'ucf-brand-block-theme' ); ?></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><?php esc_html_e( 'Use the approved colors only.', 'ucf-brand-block-theme' ); ?></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><?php esc_html_e( 'Scale proportionally from the vector source.', 'ucf-brand-block-theme' ); ?></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><?php esc_html_e( 'Maintain the outer white outline.', 'ucf-brand-block-theme' ); ?></li>
			<!-- /wp:list-item -->
		</ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
