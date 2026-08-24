<?php
/**
 * Theme bootstrap.
 *
 * This file loads includes/ and does nothing else. Every behavior the theme adds lives in
 * one topic file there, so "where does X happen" is answered by the list below rather than
 * by scrolling. Anything new belongs in the file that owns its topic — or in a new one —
 * not here.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/*
 * Load order is the reading order, coarse to fine: what the theme *is*, then what it
 * loads, then what it registers, then the features built on top. It matters in one
 * place — includes/enqueue.php calls ucf_brand_format_number() from includes/sections.php —
 * but every file here only defines functions and adds hooks at include time, so nothing
 * runs before WordPress calls it.
 */
$ucf_brand_includes = array(
	'setup',              // Theme supports and editor rendering modes.
	'enqueue',            // Every way CSS and JS reach the front end or editor canvas.
	'blocks',             // Static custom blocks compiled from src/blocks/.
	'block-styles',       // register_block_style() for core blocks.
	'pattern-categories', // The units → groups → sections → pages ladder.
	'university-header',  // The UCF University Header: its script tag and placeholder.
	'meta',               // Per-page fields: brand number, hero deck and note.
	'sections',           // Section numbering, ordering and the number binding.
	'section-nav',        // The drawer's server-rendered navigation block.
	'headings',           // H2 anchor ids and section extraction.
	'search',             // Search scoping and subsection deep links.
);

foreach ( $ucf_brand_includes as $ucf_brand_include ) {
	require_once get_theme_file_path( "includes/{$ucf_brand_include}.php" );
}

unset( $ucf_brand_includes, $ucf_brand_include );
