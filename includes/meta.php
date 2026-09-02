<?php
/**
 * Per-page meta fields.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

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
 * `ucf_brand_hero_treatment` is the hero's light/dark toggle for one page. Empty means the
 * page follows whatever templates/page.html ships, which is the site-wide default; a value
 * overrides it for this page only. See ucf_brand_hero_treatment_class() in includes/hero.php.
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

	register_post_meta(
		'page',
		'ucf_brand_hero_treatment',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			// SYNC: the same three values the sidebar control offers
			// (src/js/editor/hero-treatment.js). Anything else falls back to the template's.
			'sanitize_callback' => static function ( $value ) {
				return in_array( $value, array( 'dark', 'light' ), true ) ? $value : '';
			},
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
