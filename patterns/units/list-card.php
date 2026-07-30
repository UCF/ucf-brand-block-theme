<?php
/**
 * Title: List Card
 * Slug: ucf-brand/list-card
 * Categories: ucf-brand-units
 * Description: A bordered card holding a numbered list — each row a mono number beside a short title and a one-line description, rows divided by a bottom rule. Everything is set through core block controls, so the card recolors from the block sidebar.
 * Keywords: list card, list, numbered, steps, checklist, card, index
 *
 * @package ucf-brand-block-theme
 */

/*
 * Same row idiom as the Index pattern (ucf-brand/index), boxed. Vanilla Gutenberg only:
 * the border color comes from the palette through the block's own border controls and the
 * number's display family and size from the typography controls — no theme classes, so an
 * editor can change any of it from the sidebar. The card sets neither a background nor a
 * text color: it inherits both from the section or group style it sits in, so it stays
 * legible on a light or dark field without a variant per color.
 *
 * Each row is a Group carrying its own padding and its own bottom rule (the block's border
 * controls, `line` side border). Padding lives on the rows rather than the card, and the
 * card's block gap is 0, so each rule runs the full width of the card. The last row leaves
 * the rule off, so adding or removing a row means duplicating a group and toggling one
 * border — no separators to keep in sync.
 *
 * Titles are H4 so they stay off the H2-driven drawer sub-nav — see CLAUDE.md. The three
 * rows below are starter content an editor edits in place.
 */
?>
<!-- wp:group {"metadata":{"name":"List Card"},"style":{"border":{"width":"1px"},"spacing":{"blockGap":"0"}},"borderColor":"line","layout":{"type":"default"}} -->
<div class="wp-block-group has-border-color has-line-border-color" style="border-width:1px">
	<!-- wp:group {"metadata":{"name":"Row"},"style":{"border":{"bottom":{"color":"var:preset|color|line","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|40","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40"}}},"layout":{"type":"default"}} -->
	<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--line);border-bottom-width:1px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">
		<!-- wp:columns {"verticalAlignment":"top"} -->
		<div class="wp-block-columns are-vertically-aligned-top">
			<!-- wp:column {"verticalAlignment":"top","width":"10%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:10%">
				<!-- wp:paragraph {"fontFamily":"display","fontSize":"heading-3","style":{"typography":{"fontWeight":"700","lineHeight":"1.2"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
				<p class="has-display-font-family has-heading-3-font-size" style="margin-top:0;margin-bottom:0;font-weight:700;line-height:1.2">01</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"90%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:90%">
				<!-- wp:heading {"level":4,"fontFamily":"body","fontSize":"ui","style":{"typography":{"fontWeight":"700","lineHeight":"1.2"},"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|10"}}}} -->
				<h4 class="wp-block-heading has-body-font-family has-ui-font-size" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--10);font-weight:700;line-height:1.2"><?php esc_html_e( 'Pacing', 'ucf-brand-block-theme' ); ?></h4>
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

	<!-- wp:group {"metadata":{"name":"Row"},"style":{"border":{"bottom":{"color":"var:preset|color|line","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|40","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40"}}},"layout":{"type":"default"}} -->
	<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--line);border-bottom-width:1px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">
		<!-- wp:columns {"verticalAlignment":"top"} -->
		<div class="wp-block-columns are-vertically-aligned-top">
			<!-- wp:column {"verticalAlignment":"top","width":"10%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:10%">
				<!-- wp:paragraph {"fontFamily":"display","fontSize":"heading-3","style":{"typography":{"fontWeight":"700","lineHeight":"1.2"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
				<p class="has-display-font-family has-heading-3-font-size" style="margin-top:0;margin-bottom:0;font-weight:700;line-height:1.2">02</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"90%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:90%">
				<!-- wp:heading {"level":4,"fontFamily":"body","fontSize":"ui","style":{"typography":{"fontWeight":"700","lineHeight":"1.2"},"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|10"}}}} -->
				<h4 class="wp-block-heading has-body-font-family has-ui-font-size" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--10);font-weight:700;line-height:1.2"><?php esc_html_e( 'Sound', 'ucf-brand-block-theme' ); ?></h4>
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
				<!-- wp:paragraph {"fontFamily":"display","fontSize":"heading-3","style":{"typography":{"fontWeight":"700","lineHeight":"1.2"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
				<p class="has-display-font-family has-heading-3-font-size" style="margin-top:0;margin-bottom:0;font-weight:700;line-height:1.2">03</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"90%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:90%">
				<!-- wp:heading {"level":4,"fontFamily":"body","fontSize":"ui","style":{"typography":{"fontWeight":"700","lineHeight":"1.2"},"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|10"}}}} -->
				<h4 class="wp-block-heading has-body-font-family has-ui-font-size" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--10);font-weight:700;line-height:1.2"><?php esc_html_e( 'Editing', 'ucf-brand-block-theme' ); ?></h4>
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
