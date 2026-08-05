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
require_once get_theme_file_path( 'includes/headings.php' );
require_once get_theme_file_path( 'includes/search.php' );

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
		'page-hero',
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
 * These are the prototype's `.on-dark` / `.ht` section modifiers, expressed so an editor
 * can apply them from the block sidebar instead of hand-writing a class. Definitions live
 * in src/scss/_sections.scss and _compositions.scss.
 *
 * @return void
 */
function ucf_brand_register_block_styles() {
	$group_styles = array(
		'on-dark'  => __( 'On Dark', 'ucf-brand-block-theme' ),
		'halftone' => __( 'Halftone', 'ucf-brand-block-theme' ),
		'specimen' => __( 'Type Specimen', 'ucf-brand-block-theme' ),
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

	ucf_brand_register_callout_styles();

	// Accent Rule: the short, heavy rule that sits under a hero or section title. Reads
	// `--brand-accent`, so it follows whatever composition encloses it rather than naming
	// gold. Styling lives in src/scss/_hero.scss.
	register_block_style(
		'core/separator',
		array(
			'name'  => 'accent-rule',
			'label' => __( 'Accent Rule', 'ucf-brand-block-theme' ),
		)
	);

	// `muted` is de-emphasized body copy — the same family and size as body text, one step
	// down in emphasis. It exists so a pattern can ask for grey copy without naming a color
	// token, which would freeze it to a light field. See src/scss/_compositions.scss.
	$text_styles = array(
		'lead'    => __( 'Lead', 'ucf-brand-block-theme' ),
		'eyebrow' => __( 'Eyebrow', 'ucf-brand-block-theme' ),
		'meta'    => __( 'Meta', 'ucf-brand-block-theme' ),
		'muted'   => __( 'Muted', 'ucf-brand-block-theme' ),
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

	// Reading Width: content is wide-by-default (850, the `contentSize` token). This opt-in
	// style pulls a block in to the 756px reading measure (`--wp--custom--reading-width`) for
	// long-form copy. Styling in src/scss/_base.scss; left-anchored like the rest of the guide.
	foreach ( array( 'core/group', 'core/columns', 'core/paragraph', 'core/heading', 'core/list' ) as $block ) {
		register_block_style(
			$block,
			array(
				'name'  => 'reading-width',
				'label' => __( 'Reading Width', 'ucf-brand-block-theme' ),
			)
		);
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

	// Brand treatment for core's native Accordion block. The disclosure behavior is
	// core's (Interactivity API); this style only supplies the UCF look. Styling lives
	// in src/scss/_accordion.scss. Keep accordion headings at H3 so they stay out of
	// the H2-driven drawer sub-nav and subsection badge — see CLAUDE.md.
	register_block_style(
		'core/accordion',
		array(
			'name'  => 'brand',
			'label' => __( 'Brand', 'ucf-brand-block-theme' ),
		)
	);
}
add_action( 'init', 'ucf_brand_register_block_styles' );

/**
 * Register the callout color pairs as core/group block styles.
 *
 * One primitive, four color pairs, two flavors each — a plain background/text pair and
 * the same pair with a 3px gold rule on the leading edge:
 *
 *     is-style-paper           background + text only
 *     is-style-paper-accent    the same pair plus the edge rule
 *
 * Definitions live in src/scss/_compositions.scss, which also declares the `--brand-*`
 * roles each pair supplies — including `--brand-accent`, set by both flavors so a
 * component can bind it whether or not the edge rule is showing. Adding a pair means a
 * row here plus a row in the `$compositions` map there.
 *
 * Replaces the old single-purpose `gold-edge` style.
 *
 * @return void
 */
function ucf_brand_register_callout_styles() {
	$callouts = array(
		'bold-gold' => __( 'Bold Gold', 'ucf-brand-block-theme' ),
		'paper'     => __( 'Paper', 'ucf-brand-block-theme' ),
		'light'     => __( 'Light', 'ucf-brand-block-theme' ),
		'dark'      => __( 'Dark', 'ucf-brand-block-theme' ),
	);

	foreach ( $callouts as $name => $label ) {
		register_block_style(
			'core/group',
			array(
				'name'  => $name,
				'label' => $label,
			)
		);

		register_block_style(
			'core/group',
			array(
				'name'  => $name . '-accent',
				/* translators: %s: callout color pair name, e.g. "Paper". */
				'label' => sprintf( __( '%s + Accent', 'ucf-brand-block-theme' ), $label ),
			)
		);
	}
}

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
 * Register the per-page fields the hero and drawer read, exposed to the editor and REST.
 *
 * `ucf_brand_number` orders the page in the drawer and prints as its label.
 * `ucf_brand_deck` and `ucf_brand_hero_note` are the hero's two lines of copy under the
 * title. Both are written straight into the canvas: templates/page.html binds a paragraph
 * to each through core's `core/post-meta` source, which is editable in place because that
 * source ships a setter. Each holds one paragraph's worth of rich text — a binding resolves
 * to a single string, so the deck is one paragraph and the note is the second.
 *
 * `show_in_rest` is not optional here. `_block_bindings_post_meta_get_value()` refuses to
 * read a key that isn't exposed to REST, so dropping it would empty the hero on the front
 * end, not just in the editor.
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

	foreach ( array( 'ucf_brand_deck', 'ucf_brand_hero_note' ) as $key ) {
		register_post_meta(
			'page',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'wp_kses_post',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_pages' );
				},
			)
		);
	}
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
 * With a `label` argument the number is expanded into the hero's eyebrow line —
 * "Brand Guidelines · Section 05" — rather than returned bare. Both forms come off the
 * same meta value on purpose: the drawer prints one number per page and the hero prints
 * the same one, so the two cannot drift the way a hand-typed eyebrow does.
 *
 * @param array         $source_args    Binding arguments. `label` prefixes the number.
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

	$number = ucf_brand_format_number( get_post_meta( $post_id, 'ucf_brand_number', true ) );

	// An unnumbered page returns '' either way, which empties the paragraph — and
	// `.brand-page-number:empty` then hides it. See src/scss/_typography.scss.
	if ( '' === $number || empty( $source_args['label'] ) ) {
		return $number;
	}

	return sprintf(
		/* translators: 1: guide name, e.g. "Brand Guidelines". 2: zero-padded section number, e.g. "05". */
		__( '%1$s · Section %2$s', 'ucf-brand-block-theme' ),
		$source_args['label'],
		$number
	);
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
			'<li class="brand-nav__item%1$s"><a class="brand-nav__link" href="%2$s"%3$s><span class="brand-nav__num">%4$s</span><span class="brand-nav__text">%5$s</span><span class="brand-nav__icon" aria-hidden="true"></span></a></li>',
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
 * The value is fixed at page load. blocks/index.js overrides it inline when an author
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

	$settings['styles'][] = array(
		'css' => sprintf( '.is-root-container{--brand-section:"%s.";}', $section ),
	);

	return $settings;
}
add_filter( 'block_editor_settings_all', 'ucf_brand_editor_section_style', 10, 2 );

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
