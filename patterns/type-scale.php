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
$ucf_scale = array(
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

$ucf_samples = array(
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

foreach ( $ucf_scale as $ucf_row ) {
	list( $ucf_slug, $ucf_label, $ucf_value, $ucf_family, $ucf_caps ) = $ucf_row;

	$ucf_style = $ucf_caps ? array( 'typography' => array( 'textTransform' => 'uppercase' ) ) : array();
	$ucf_attrs = array(
		'fontFamily' => $ucf_family,
		'fontSize'   => $ucf_slug,
	);
	if ( $ucf_style ) {
		$ucf_attrs['style'] = $ucf_style;
	}

	$ucf_classes = sprintf( 'has-%s-font-family has-%s-font-size', $ucf_family, $ucf_slug );
	$ucf_inline  = $ucf_caps ? ' style="text-transform:uppercase"' : '';
	?>
<!-- wp:group {"className":"is-style-specimen","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-specimen">
	<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
	<p class="is-style-eyebrow"><?php echo esc_html( "$ucf_label · $ucf_value" ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph <?php echo wp_json_encode( $ucf_attrs ); ?> -->
	<p class="<?php echo esc_attr( $ucf_classes ); ?>"<?php echo $ucf_inline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed literal. ?>><?php echo esc_html( $ucf_samples[ $ucf_slug ] ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
	<?php
}
