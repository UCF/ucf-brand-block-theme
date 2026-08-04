<?php
/**
 * Title: Detail Card
 * Slug: ucf-brand/detail-card
 * Categories: ucf-brand-units
 * Description: A bordered card with an accent header bar over an open body. The bar and the outline follow the card's composition — select the card and switch its style (Light, Paper, Dark, Bold Gold) and both recolor with it. Fill the body with whatever the section needs.
 * Keywords: detail card, card, do, dont, callout, header, accent
 *
 * @package ucf-brand-block-theme
 */

?>
<!-- wp:group {"metadata":{"name":"Detail Card"},"className":"is-style-light","style":{"border":{"width":"1px"},"spacing":{"blockGap":"0","padding":{"top":"0","right":"0","bottom":"0","left":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group is-style-light" style="border-width:1px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:heading {"level":4,"metadata":{"name":"Header"},"className":"accent-fill","fontSize":"ui","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|20","right":"var:preset|spacing|40","bottom":"var:preset|spacing|20","left":"var:preset|spacing|40"}}}} -->
	<h4 class="wp-block-heading accent-fill has-ui-font-size" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--40)"><?php esc_html_e( 'Do', 'ucf-brand-block-theme' ); ?></h4>
	<!-- /wp:heading -->

	<!-- wp:group {"metadata":{"name":"Body"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"></div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
