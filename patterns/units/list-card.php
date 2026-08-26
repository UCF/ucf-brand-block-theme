<?php
/**
 * Title: List Card
 * Slug: ucf-brand/list-card
 * Categories: ucf-brand-units
 * Description: A bordered card holding a numbered list — each row a number beside a short title and a description, rows divided by a bottom rule. Everything is set through core block controls, so the card recolors from the block sidebar.
 * Keywords: list card, list, numbered, steps, checklist, card, index
 *
 * @package ucf-brand-block-theme
 */

/*
 * Same row idiom as the Index pattern (ucf-brand/index), boxed. The card names no colors
 * at all — border widths come from the block's own border controls and the `hairline`
 * class points them at the `--brand-line` role, so the outline follows whatever
 * composition the card sits in. Background and text color are likewise inherited, so it
 * stays legible on a light or dark field without a variant per color.
 *
 * Each row is a Group carrying its own padding and its own bottom rule (a border width on
 * the bottom side only, colored by the same `hairline` class). Padding lives on the rows
 * rather than the card, and the card's block gap is 0, so each rule runs the full width of
 * the card. The last row omits the rule, so adding or removing a row means duplicating a
 * group and toggling one border — no separators to keep in sync.
 *
 * Titles are H3: the card normally follows an H2 section heading directly, so H3 is the next
 * level down and keeps the outline from skipping one. H3 also stays off the H2-driven drawer
 * sub-nav — see CLAUDE.md. Under a lead-in H3 (the Index layout), drop the rows to H4 on that
 * instance. The three
 * rows below are starter content an editor edits in place.
 */
?>
<!-- wp:group {"metadata":{"name":"List Card"},"className":"hairline","style":{"border":{"width":"1px"},"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group hairline" style="border-width:1px">
	<!-- wp:group {"metadata":{"name":"Row"},"className":"hairline","style":{"border":{"bottom":{"width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|40","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40"}}},"layout":{"type":"default"}} -->
	<div class="wp-block-group hairline" style="border-bottom-width:1px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">
		<!-- wp:columns {"verticalAlignment":"top"} -->
		<div class="wp-block-columns are-vertically-aligned-top">
			<!-- wp:column {"verticalAlignment":"top","width":"10%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:10%">
				<!-- wp:paragraph {"fontFamily":"display","fontSize":"heading-3","style":{"typography":{"fontWeight":"700","letterSpacing":"0.01em","lineHeight":"1.2"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
				<p class="has-display-font-family has-heading-3-font-size" style="margin-top:0;margin-bottom:0;font-weight:700;letter-spacing:0.01em;line-height:1.2">01</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"90%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:90%">
				<!-- wp:heading {"level":3,"fontFamily":"body","fontSize":"ui","style":{"typography":{"fontWeight":"700","lineHeight":"1.2"},"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|10"}}}} -->
				<h3 class="wp-block-heading has-body-font-family has-ui-font-size" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--10);font-weight:700;line-height:1.2"><?php esc_html_e( 'Pacing', 'ucf-brand-block-theme' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"fontSize":"ui","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
				<p class="has-ui-font-size" style="margin-top:0;margin-bottom:0"><?php esc_html_e( 'Cut with purpose. Frantic editing undercuts the message.', 'ucf-brand-block-theme' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"metadata":{"name":"Row"},"className":"hairline","style":{"border":{"bottom":{"width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|40","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40"}}},"layout":{"type":"default"}} -->
	<div class="wp-block-group hairline" style="border-bottom-width:1px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">
		<!-- wp:columns {"verticalAlignment":"top"} -->
		<div class="wp-block-columns are-vertically-aligned-top">
			<!-- wp:column {"verticalAlignment":"top","width":"10%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:10%">
				<!-- wp:paragraph {"fontFamily":"display","fontSize":"heading-3","style":{"typography":{"fontWeight":"700","letterSpacing":"0.01em","lineHeight":"1.2"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
				<p class="has-display-font-family has-heading-3-font-size" style="margin-top:0;margin-bottom:0;font-weight:700;letter-spacing:0.01em;line-height:1.2">02</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"90%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:90%">
				<!-- wp:heading {"level":3,"fontFamily":"body","fontSize":"ui","style":{"typography":{"fontWeight":"700","lineHeight":"1.2"},"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|10"}}}} -->
				<h3 class="wp-block-heading has-body-font-family has-ui-font-size" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--10);font-weight:700;line-height:1.2"><?php esc_html_e( 'Sound', 'ucf-brand-block-theme' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"fontSize":"ui","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
				<p class="has-ui-font-size" style="margin-top:0;margin-bottom:0"><?php esc_html_e( 'Keep dialogue clean and ambient sound natural. Music supports; it does not compete.', 'ucf-brand-block-theme' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"metadata":{"name":"Row"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|40","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40"}}},"layout":{"type":"default"}} -->
	<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">
		<!-- wp:columns {"verticalAlignment":"top"} -->
		<div class="wp-block-columns are-vertically-aligned-top">
			<!-- wp:column {"verticalAlignment":"top","width":"10%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:10%">
				<!-- wp:paragraph {"fontFamily":"display","fontSize":"heading-3","style":{"typography":{"fontWeight":"700","letterSpacing":"0.01em","lineHeight":"1.2"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
				<p class="has-display-font-family has-heading-3-font-size" style="margin-top:0;margin-bottom:0;font-weight:700;letter-spacing:0.01em;line-height:1.2">03</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"90%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:90%">
				<!-- wp:heading {"level":3,"fontFamily":"body","fontSize":"ui","style":{"typography":{"fontWeight":"700","lineHeight":"1.2"},"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|10"}}}} -->
				<h3 class="wp-block-heading has-body-font-family has-ui-font-size" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--10);font-weight:700;line-height:1.2"><?php esc_html_e( 'Editing', 'ucf-brand-block-theme' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"fontSize":"ui","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
				<p class="has-ui-font-size" style="margin-top:0;margin-bottom:0"><?php esc_html_e( 'Open on your strongest moment. Close on the UCF mark, held at least two seconds.', 'ucf-brand-block-theme' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
