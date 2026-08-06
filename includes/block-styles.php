<?php
/**
 * Block style registration.
 *
 * Every `register_block_style()` in the theme is here; each one's CSS lives in a partial
 * under src/scss/ named in the comment above it. A style registered here and not defined
 * there is an editor offering that paints nothing, so the two move together.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

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
	// is a toggle attribute added to core/button in src/js/editor/stretch-link.js so it composes
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
