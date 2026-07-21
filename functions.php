<?php
/**
 * Theme bootstrap.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

require_once get_theme_file_path( 'includes/patterns.php' );

/**
 * Theme supports and editor styles.
 *
 * The editor loads the same compiled stylesheet as the front end so the two stay in
 * parity — a rule authored in src/scss/ is visible while writing, not just after
 * publishing.
 *
 * @return void
 */
function ucf_brand_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/main.css' );
}
add_action( 'after_setup_theme', 'ucf_brand_setup' );

/**
 * Enqueue front-end assets.
 *
 * Webfonts are deliberately absent here — they are declared as `fontFace` entries in
 * theme.json and served from assets/fonts/, so nothing is requested from a third-party
 * font CDN at render time.
 *
 * @return void
 */
function ucf_brand_enqueue_assets() {
	$css_path = get_theme_file_path( 'assets/css/main.css' );
	wp_enqueue_style(
		'ucf-brand-theme',
		get_theme_file_uri( 'assets/css/main.css' ),
		array(),
		file_exists( $css_path ) ? filemtime( $css_path ) : false
	);

	$js_path = get_theme_file_path( 'assets/js/brand-nav.js' );
	wp_enqueue_script(
		'ucf-brand-nav',
		get_theme_file_uri( 'assets/js/brand-nav.js' ),
		array(),
		file_exists( $js_path ) ? filemtime( $js_path ) : false,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'ucf_brand_enqueue_assets' );

/**
 * Register the theme's custom blocks.
 *
 * Every block here is static — its `save()` emits real markup and there is no
 * `render.php`, so nothing is rendered on the server. Sources live in `blocks/`,
 * compiled to `build/` by `npm run build:blocks`.
 *
 * These are expected to move to a distribution plugin eventually. Nothing in the
 * block sources references the theme, so that move is a copy of `blocks/` plus this
 * registration loop.
 *
 * @return void
 */
function ucf_brand_register_blocks() {
	$blocks = array( 'color-swatches', 'color-swatch' );

	foreach ( $blocks as $block ) {
		$path = get_theme_file_path( "build/$block" );

		if ( file_exists( "$path/block.json" ) ) {
			register_block_type( $path );
		}
	}
}
add_action( 'init', 'ucf_brand_register_blocks' );

/**
 * Register block styles for the section treatments the brand guide uses.
 *
 * These are the prototype's `.on-dark` / `.gold-edge` / `.ht` section modifiers,
 * expressed so an editor can apply them from the block sidebar instead of hand-writing
 * a class. Definitions live in src/scss/_sections.scss and _typography.scss.
 *
 * @return void
 */
function ucf_brand_register_block_styles() {
	$group_styles = array(
		'on-dark'   => __( 'On Dark', 'ucf-brand-block-theme' ),
		'gold-edge' => __( 'Gold Edge', 'ucf-brand-block-theme' ),
		'halftone'  => __( 'Halftone', 'ucf-brand-block-theme' ),
		'specimen'  => __( 'Type Specimen', 'ucf-brand-block-theme' ),
	);

	foreach ( $group_styles as $name => $label ) {
		register_block_style(
			'core/group',
			array(
				'name'  => $name,
				'label' => $label,
			)
		);
	}

	$text_styles = array(
		'lead'    => __( 'Lead', 'ucf-brand-block-theme' ),
		'eyebrow' => __( 'Eyebrow', 'ucf-brand-block-theme' ),
		'meta'    => __( 'Meta', 'ucf-brand-block-theme' ),
	);

	foreach ( $text_styles as $name => $label ) {
		foreach ( array( 'core/paragraph', 'core/heading' ) as $block ) {
			register_block_style(
				$block,
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}
	}
}
add_action( 'init', 'ucf_brand_register_block_styles' );

/**
 * Drop core's page-list fallback for an empty Navigation block.
 *
 * The fallback renders a nested page tree, but the brand drawer is explicitly one level
 * deep — sub-navigation comes from the current page's H2s at runtime, not from page
 * hierarchy. Matches the same filter in ucf-wordpress-block-theme.
 *
 * @param array $blocks Fallback blocks.
 * @return array Fallback blocks, minus core/page-list.
 */
function ucf_brand_navigation_fallback( $blocks ) {
	return array_values(
		array_filter(
			(array) $blocks,
			static function ( $block ) {
				return ! isset( $block['blockName'] ) || 'core/page-list' !== $block['blockName'];
			}
		)
	);
}
add_filter( 'block_core_navigation_render_fallback', 'ucf_brand_navigation_fallback' );
