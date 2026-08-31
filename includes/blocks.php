<?php
/**
 * Custom block registration.
 *
 * This file owns the *static* blocks only — the ones compiled from src/blocks/ whose `save()`
 * emits real markup. The theme's three server-rendered blocks are deliberately not here: a
 * dynamic block lives in the file that owns the data it renders, so `ucf-brand/section-nav`
 * is in includes/section-nav.php, `ucf-brand/search-subsections` is in includes/search.php
 * and `ucf-brand/section-index` is in includes/section-index.php.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Register the theme's custom blocks.
 *
 * Every block here is static — its `save()` emits real markup and there is no
 * `render.php`, so nothing is rendered on the server. Sources live in `src/blocks/`,
 * compiled to `build/` by `npm run build:blocks`.
 *
 * Discovered from disk rather than listed: a compiled block folder is one that has a
 * `block.json` in it, which is the same test `register_block_type()` applies. Listing
 * them by hand made adding a block a two-place edit, and the list falling out of step
 * showed up only as the block silently missing from the inserter.
 *
 * These are expected to move to a distribution plugin eventually. Nothing in the
 * block sources references the theme, so that move is a copy of `src/blocks/` plus this
 * registration loop.
 *
 * @return void
 */
function ucf_brand_register_blocks() {
	// glob() returns false rather than an empty array when the directory is missing —
	// before a first build, say — and foreach over false is a PHP 8 warning.
	$manifests = (array) glob( get_theme_file_path( 'build/*/block.json' ) );

	foreach ( $manifests as $manifest ) {
		register_block_type( dirname( $manifest ) );
	}
}
add_action( 'init', 'ucf_brand_register_blocks' );
