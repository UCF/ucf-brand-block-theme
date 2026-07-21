<?php
/**
 * Title: Type Scale
 * Slug: ucf-brand/type-scale
 * Categories: ucf-brand-blocks
 * Description: Every size in the theme's type scale, each rendered at its own preset so the demo can never drift from the tokens.
 * Keywords: type, typography, scale, size, fluid
 *
 * @package ucf-brand-block-theme
 */

/*
 * Each row's sample uses the preset it documents, so this page is a live read of
 * theme.json rather than a transcription of it. Change a size there and this updates.
 */
$ucf_brand_scale = array(
	array( 'display-1', 'Display', 'clamp(2.6rem, 9vw, 6.4rem)', 'display', true ),
	array( 'heading-1', 'Heading 1', 'clamp(2.4rem, 5vw, 4.2rem)', 'display', true ),
	array( 'heading-2', 'Heading 2', 'clamp(1.9rem, 3.6vw, 2.8rem)', 'display', true ),
	array( 'heading-3', 'Heading 3', 'clamp(1.3rem, 2.6vw, 1.9rem)', 'display', true ),
	array( 'heading-4', 'Heading 4', '1.28rem', 'display', true ),
	array( 'lead', 'Lead', 'clamp(1.05rem, 1.7vw, 1.28rem)', 'body', false ),
	array( 'body', 'Body', '1rem', 'body', false ),
	array( 'ui', 'UI', '0.86rem', 'body', false ),
	array( 'eyebrow', 'Eyebrow', '0.72rem', 'mono', true ),
	array( 'meta', 'Meta', '0.68rem', 'mono', false ),
);

$ucf_brand_samples = array(
	'display-1' => __( 'Knights', 'ucf-brand-block-theme' ),
	'heading-1' => __( 'Page title', 'ucf-brand-block-theme' ),
	'heading-2' => __( 'Section heading', 'ucf-brand-block-theme' ),
	'heading-3' => __( 'Subsection heading', 'ucf-brand-block-theme' ),
	'heading-4' => __( 'Card heading', 'ucf-brand-block-theme' ),
	'lead'      => __( 'The opening paragraph of a section, set slightly larger and heavier than body copy.', 'ucf-brand-block-theme' ),
	'body'      => __( 'The size everything else is measured against. Body copy sets at a 720px measure so a reader’s eye never loses the line.', 'ucf-brand-block-theme' ),
	'ui'        => __( 'Navigation, buttons and interface labels.', 'ucf-brand-block-theme' ),
	'eyebrow'   => __( 'Section label', 'ucf-brand-block-theme' ),
	'meta'      => __( 'Captions, credits and fine print.', 'ucf-brand-block-theme' ),
);

foreach ( $ucf_brand_scale as $ucf_brand_row ) {
	list( $ucf_brand_slug, $ucf_brand_label, $ucf_brand_value, $ucf_brand_family, $ucf_brand_caps ) = $ucf_brand_row;

	$ucf_brand_style = $ucf_brand_caps ? array( 'typography' => array( 'textTransform' => 'uppercase' ) ) : array();
	$ucf_brand_attrs = array(
		'fontFamily' => $ucf_brand_family,
		'fontSize'   => $ucf_brand_slug,
	);
	if ( $ucf_brand_style ) {
		$ucf_brand_attrs['style'] = $ucf_brand_style;
	}

	$ucf_brand_classes = sprintf( 'has-%s-font-family has-%s-font-size', $ucf_brand_family, $ucf_brand_slug );
	$ucf_brand_inline  = $ucf_brand_caps ? ' style="text-transform:uppercase"' : '';
	?>
<!-- wp:group {"className":"is-style-specimen","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-specimen">
	<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
	<p class="is-style-eyebrow"><?php echo esc_html( "$ucf_brand_label · $ucf_brand_value" ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph <?php echo wp_json_encode( $ucf_brand_attrs ); ?> -->
	<p class="<?php echo esc_attr( $ucf_brand_classes ); ?>"<?php echo $ucf_brand_inline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed literal. ?>><?php echo esc_html( $ucf_brand_samples[ $ucf_brand_slug ] ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
	<?php
}
