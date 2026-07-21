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
 * Note the `ucf-brand-` prefix: the bare `ucf-sections` slug is reserved by the UCF
 * Section plugin and must not be reused here.
 *
 * @return void
 */
function ucf_brand_register_pattern_categories() {
	register_block_pattern_category(
		'ucf-brand-sections',
		array( 'label' => __( 'UCF Brand: Sections', 'ucf-brand-block-theme' ) )
	);

	register_block_pattern_category(
		'ucf-brand-blocks',
		array( 'label' => __( 'UCF Brand: Blocks', 'ucf-brand-block-theme' ) )
	);
}
add_action( 'init', 'ucf_brand_register_pattern_categories' );
