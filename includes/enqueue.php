<?php
/**
 * Asset delivery — front end and block editor.
 *
 * Every way CSS or JS reaches a browser is in this file, so "what does this theme load,
 * and on which screen" is one place rather than a hunt. That includes the editor canvas,
 * which is an iframe and therefore does not take an enqueue at all — see
 * `ucf_brand_editor_section_style()` at the bottom. What each script *does* is documented
 * at the top of the script itself.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Enqueue a script built by `npm run build`, using its generated asset manifest.
 *
 * Every entry in webpack.config.js emits a `<name>.asset.php` next to its `<name>.js`,
 * holding the WordPress script handles that entry imported and a content hash. Reading
 * those means a dependency list is never restated here — adding an `@wordpress/*` import
 * to the source is the whole change.
 *
 * Silently does nothing when the manifest is missing, which is the pre-build state.
 *
 * @param string $handle    Script handle to register.
 * @param string $name      Entry name, matching build/<name>.js.
 * @param bool   $in_footer Whether to print the tag in the footer.
 * @return void
 */
function ucf_brand_enqueue_build_script( $handle, $name, $in_footer = true ) {
	$asset_path = get_theme_file_path( "build/{$name}.asset.php" );

	if ( ! file_exists( $asset_path ) ) {
		return;
	}

	$asset = require $asset_path;

	wp_enqueue_script(
		$handle,
		get_theme_file_uri( "build/{$name}.js" ),
		$asset['dependencies'],
		$asset['version'],
		$in_footer
	);
}

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
	$css_path = get_theme_file_path( 'build/css/main.css' );
	wp_enqueue_style(
		'ucf-brand-theme',
		get_theme_file_uri( 'build/css/main.css' ),
		array(),
		file_exists( $css_path ) ? filemtime( $css_path ) : false
	);

	// Expose the current page's number to CSS so each H2 badge can prefix its subsection
	// counter with it (1.1, 1.2 …). Unset on pages with no Brand order, which makes the
	// badge's `content` invalid and hides it — see _sections.scss. The editor canvas gets
	// the same variable by a different route; see ucf_brand_editor_section_style() below.
	// The formatter lives in includes/sections.php, loaded ahead of this file.
	if ( is_singular() ) {
		$section = ucf_brand_format_number(
			get_post_meta( get_queried_object_id(), 'ucf_brand_number', true )
		);

		if ( '' !== $section ) {
			wp_add_inline_style(
				'ucf-brand-theme',
				sprintf( '.brand-content{--brand-section:"%s.";}', $section )
			);
		}
	}

	// The drawer's sub-nav and scroll-spy. Source: src/js/brand-nav.js.
	ucf_brand_enqueue_build_script( 'ucf-brand-nav', 'brand-nav' );
}
add_action( 'wp_enqueue_scripts', 'ucf_brand_enqueue_assets' );

/**
 * Enqueue the block-editor scripts.
 *
 * Two entries, both built from src/js/:
 *
 * - `editor`       the glue in src/js/editor/ — the Brand panel, the hero allowlist, the
 *                  section-number binding, the stretch-link toggle and the editor
 *                  stand-ins for the two PHP-rendered blocks.
 * - `badge-format` the Badge button on the RichText formatting toolbar, which opens a
 *                  swatch popover (built from the same ColorPalette as core's Highlight
 *                  dialog) and wraps the selection in <span class="badge…">. Its look
 *                  comes from the compiled stylesheet (src/scss/_badge.scss), so the
 *                  editor and the front end agree.
 *
 * @return void
 */
function ucf_brand_enqueue_editor_assets() {
	ucf_brand_enqueue_build_script( 'ucf-brand-editor', 'editor' );
	ucf_brand_enqueue_build_script( 'ucf-brand-badge-format', 'badge-format' );
}
add_action( 'enqueue_block_editor_assets', 'ucf_brand_enqueue_editor_assets' );

/**
 * Expose the page's number to the editor canvas, so the H2 subsection badges render
 * there the way they do on the front end.
 *
 * The front-end equivalent is the inline style in ucf_brand_enqueue_assets(); this is the
 * canvas's copy. It goes through the editor's `styles` setting rather than
 * wp_enqueue_style() because the canvas is an iframe with its own document — an enqueued
 * editor style never reaches inside it, while everything in `styles` is rendered into the
 * canvas by Gutenberg on every mount and re-mount (device preview, editor mode switches).
 * That lifecycle handling is the reason this is here and not all in JS.
 *
 * The value is fixed at page load. src/js/editor/section-variable.js overrides it inline when an author
 * edits the Brand order field, so the badges stay live without this needing to know.
 *
 * @param array                   $settings Block editor settings.
 * @param WP_Block_Editor_Context $context  The current editor context.
 * @return array Filtered settings.
 */
function ucf_brand_editor_section_style( $settings, $context ) {
	if ( empty( $context->post ) || 'page' !== $context->post->post_type ) {
		return $settings;
	}

	$section = ucf_brand_format_number(
		get_post_meta( $context->post->ID, 'ucf_brand_number', true )
	);

	if ( '' === $section ) {
		return $settings;
	}

	if ( empty( $settings['styles'] ) || ! is_array( $settings['styles'] ) ) {
		$settings['styles'] = array();
	}

	$settings['styles'][] = array(
		'css' => sprintf( '.is-root-container{--brand-section:"%s.";}', $section ),
	);

	return $settings;
}
add_filter( 'block_editor_settings_all', 'ucf_brand_editor_section_style', 10, 2 );
