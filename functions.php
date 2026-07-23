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

	// Expose the current page's number to CSS so each H2 badge can prefix its subsection
	// counter with it (01.01, 01.02 …). Unset on pages with no Brand order, which makes the
	// badge's `content` invalid and hides it — see _sections.scss.
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
	$blocks = array(
		'color-swatches',
		'color-swatch',
		'tabs',
		'tab',
		'tab-label',
		'tab-panel',
	);

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

	// Glyph: a transparent, borderless button for a clickable icon/glyph. This is a
	// look, so it stays a block style. The orthogonal "stretch to container" behavior
	// is a toggle attribute added to core/button in blocks/index.js so it composes
	// with any look. Styling for both lives in `src/scss/_stretch-link.scss`.
	register_block_style(
		'core/button',
		array(
			'name'  => 'glyph',
			'label' => __( 'Glyph', 'ucf-brand-block-theme' ),
		)
	);
}
add_action( 'init', 'ucf_brand_register_block_styles' );

/**
 * ── Section numbering ─────────────────────────────────────────────────────────
 *
 * Each brand page carries a `ucf_brand_number` — set by the editor in the Brand panel
 * (see blocks/index.js). That single value both orders the page in the drawer and prints
 * as its decimal label (1 → "01"). One PHP source of truth,
 * `ucf_brand_get_ordered_sections()`, feeds two consumers: the drawer menu (the
 * `ucf-brand/section-nav` dynamic block) and each page's on-page label (the
 * `ucf-brand/section-number` block binding), so the two can never disagree.
 */

/**
 * Register the per-page order/label field, exposed to the editor and REST.
 *
 * @return void
 */
function ucf_brand_register_meta() {
	register_post_meta(
		'page',
		'ucf_brand_number',
		array(
			'type'              => 'integer',
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_pages' );
			},
		)
	);
}
add_action( 'init', 'ucf_brand_register_meta' );

/**
 * Format a section number as its zero-padded decimal label (1 → "01").
 *
 * @param int $number Raw section number.
 * @return string Two-digit-minimum label, or '' when unset.
 */
function ucf_brand_format_number( $number ) {
	$number = (int) $number;

	if ( $number < 1 ) {
		return '';
	}

	return str_pad( (string) $number, 2, '0', STR_PAD_LEFT );
}

/**
 * The ordered, numbered list of drawer sections — the single source of truth.
 *
 * Published top-level pages that carry a number, minus the front page, sorted by number
 * then title. Each entry is annotated with its label, permalink and current-page flag.
 *
 * @return array<int, array<string, mixed>> Section descriptors.
 */
function ucf_brand_get_ordered_sections() {
	$pages = get_posts(
		array(
			'post_type'        => 'page',
			'post_parent'      => 0,
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'meta_key'         => 'ucf_brand_number',
			'meta_query'       => array(
				array(
					'key'     => 'ucf_brand_number',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
			'orderby'          => array(
				'meta_value_num' => 'ASC',
				'title'          => 'ASC',
			),
			'suppress_filters' => false,
		)
	);

	$front_id   = (int) get_option( 'page_on_front' );
	$current_id = (int) get_queried_object_id();
	$sections   = array();

	foreach ( $pages as $page ) {
		if ( $page->ID === $front_id ) {
			continue;
		}

		$number = (int) get_post_meta( $page->ID, 'ucf_brand_number', true );

		if ( $number < 1 ) {
			continue;
		}

		$sections[] = array(
			'id'         => $page->ID,
			'number'     => $number,
			'label'      => ucf_brand_format_number( $number ),
			'title'      => get_the_title( $page ),
			'url'        => get_permalink( $page ),
			'is_current' => $page->ID === $current_id,
		);
	}

	usort(
		$sections,
		static function ( $a, $b ) {
			return $a['number'] <=> $b['number'] ?: strcasecmp( $a['title'], $b['title'] );
		}
	);

	return $sections;
}

/**
 * Register the block binding that prints the current page's decimal label.
 *
 * @return void
 */
function ucf_brand_register_bindings() {
	register_block_bindings_source(
		'ucf-brand/section-number',
		array(
			'label'              => __( 'Brand section number', 'ucf-brand-block-theme' ),
			'get_value_callback' => 'ucf_brand_binding_section_number',
			'uses_context'       => array( 'postId' ),
		)
	);
}
add_action( 'init', 'ucf_brand_register_bindings' );

/**
 * Resolve the bound value: the queried page's zero-padded number, or '' when unset.
 *
 * @param array         $source_args    Binding arguments (unused).
 * @param WP_Block|null $block_instance The block being rendered.
 * @return string Decimal label, or ''.
 */
function ucf_brand_binding_section_number( $source_args, $block_instance = null ) {
	$post_id = 0;

	if ( $block_instance instanceof WP_Block && ! empty( $block_instance->context['postId'] ) ) {
		$post_id = (int) $block_instance->context['postId'];
	}

	if ( ! $post_id ) {
		$post_id = (int) get_queried_object_id();
	}

	return ucf_brand_format_number( get_post_meta( $post_id, 'ucf_brand_number', true ) );
}

/**
 * Register the drawer's dynamic navigation block.
 *
 * This is theme glue, not a distributable design block — it lives in functions.php rather
 * than blocks/ precisely because it is server-rendered from live page data. It emits the
 * `.brand-nav` markup that _drawer.scss styles and brand-nav.js augments (H2 sub-nav,
 * current-item highlight).
 *
 * @return void
 */
function ucf_brand_register_section_nav() {
	register_block_type(
		'ucf-brand/section-nav',
		array(
			'api_version'     => 3,
			'render_callback' => 'ucf_brand_render_section_nav',
		)
	);
}
add_action( 'init', 'ucf_brand_register_section_nav' );

/**
 * Render the drawer navigation from the ordered section list.
 *
 * @return string Navigation markup, or '' when there are no numbered sections.
 */
function ucf_brand_render_section_nav() {
	$sections = ucf_brand_get_ordered_sections();

	if ( empty( $sections ) ) {
		return '';
	}

	$items = '';

	foreach ( $sections as $section ) {
		$items .= sprintf(
			'<li class="brand-nav__item%1$s"><a class="brand-nav__link" href="%2$s"%3$s><span class="brand-nav__num">%4$s</span><span class="brand-nav__text">%5$s</span></a></li>',
			$section['is_current'] ? ' is-current' : '',
			esc_url( $section['url'] ),
			$section['is_current'] ? ' aria-current="page"' : '',
			esc_html( $section['label'] ),
			esc_html( $section['title'] )
		);
	}

	return sprintf(
		'<nav class="brand-nav" aria-label="%1$s"><ul class="brand-nav__list">%2$s</ul></nav>',
		esc_attr__( 'Brand sections', 'ucf-brand-block-theme' ),
		$items
	);
}

/**
 * Enqueue the block-editor script for the Brand order panel.
 *
 * @return void
 */
function ucf_brand_enqueue_editor_assets() {
	$asset_path = get_theme_file_path( 'build/index.asset.php' );

	if ( ! file_exists( $asset_path ) ) {
		return;
	}

	$asset = require $asset_path;

	wp_enqueue_script(
		'ucf-brand-editor',
		get_theme_file_uri( 'build/index.js' ),
		$asset['dependencies'],
		$asset['version'],
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'ucf_brand_enqueue_editor_assets' );

/**
 * Register the "Badge" rich-text inline formats in the block editor.
 *
 * A no-build script (uses the global wp.* packages declared as dependencies)
 * that adds a single Badge button to the RichText formatting toolbar; it opens
 * a swatch popover (built from the same ColorPalette as core's Highlight dialog)
 * to pick a tone, wrapping the selected text in <span class="badge…">. The look
 * comes from the compiled stylesheet (src/scss/_badge.scss), so it matches the
 * front end.
 *
 * @return void
 */
function ucf_brand_enqueue_badge_format() {
	$relative_path = 'assets/js/badge-format.js';
	$file_path     = get_theme_file_path( $relative_path );
	$version       = file_exists( $file_path ) ? filemtime( $file_path ) : false;

	wp_enqueue_script(
		'ucf-brand-badge-format',
		get_theme_file_uri( $relative_path ),
		array( 'wp-rich-text', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-element', 'wp-i18n' ),
		$version,
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'ucf_brand_enqueue_badge_format' );
