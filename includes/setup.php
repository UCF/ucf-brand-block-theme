<?php
/**
 * Theme supports and editor rendering modes.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

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
	add_editor_style( 'build/css/main.css' );
}
add_action( 'after_setup_theme', 'ucf_brand_setup' );

/**
 * Open the page editor with the template rendered around the content.
 *
 * Pages default to `post-only` in core, which hides everything the template supplies —
 * including the page header — so an author never sees it while writing. `template-locked`
 * is the "Show template" state: the template renders, every block in it is disabled, and
 * core re-enables exactly `core/post-title`, `core/post-featured-image` and
 * `core/post-content` (see `DisableNonPageContentBlocks` in @wordpress/editor). The header
 * is therefore visible *and* its title stays editable in place.
 *
 * Two consequences worth knowing:
 *
 * - This is a default, not a lock. The per-user preference (`core` → `renderingModes` →
 *   stylesheet → post type) wins, so an author who switches "Show template" off stays off.
 * - It follows that anything meant to be editable must live in `templates/page.html`
 *   directly. Template parts and their children are disabled outright in this mode.
 *
 * @return void
 */
function ucf_brand_page_rendering_mode() {
	add_post_type_support( 'page', 'editor', array( 'default-mode' => 'template-locked' ) );
}
add_action( 'init', 'ucf_brand_page_rendering_mode' );
