<?php
/**
 * Block pattern registration.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Register pattern categories.
 *
 * Four rungs of a compositional ladder, registered small-to-large so they read in order
 * in the inserter: a Unit is a single primitive; a Group clusters units into one component;
 * a Section is a full-width content band with its own frame; a Page is a whole-page layout.
 *
 * Note the `ucf-brand-` prefix: the bare `ucf-sections` slug is reserved by the UCF
 * Section plugin and must not be reused here.
 *
 * @return void
 */
function ucf_brand_register_pattern_categories() {
	$categories = array(
		'ucf-brand-units'    => array(
			'label'       => __( 'UCF Brand: Units', 'ucf-brand-block-theme' ),
			'description' => __( 'Single primitives — the smallest reusable building blocks.', 'ucf-brand-block-theme' ),
		),
		'ucf-brand-groups'   => array(
			'label'       => __( 'UCF Brand: Groups', 'ucf-brand-block-theme' ),
			'description' => __( 'Clusters of units that read as one component.', 'ucf-brand-block-theme' ),
		),
		'ucf-brand-sections' => array(
			'label'       => __( 'UCF Brand: Sections', 'ucf-brand-block-theme' ),
			'description' => __( 'Full-width content bands with their own padding and background.', 'ucf-brand-block-theme' ),
		),
		'ucf-brand-pages'    => array(
			'label'       => __( 'UCF Brand: Pages', 'ucf-brand-block-theme' ),
			'description' => __( 'Whole-page compositions of sections.', 'ucf-brand-block-theme' ),
		),
	);

	foreach ( $categories as $slug => $args ) {
		register_block_pattern_category( $slug, $args );
	}
}
add_action( 'init', 'ucf_brand_register_pattern_categories' );
