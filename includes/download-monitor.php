<?php
/**
 * Download Monitor fallbacks.
 *
 * The theme renders protected downloads through Download Monitor's shortcodes, and
 * WordPress prints an unregistered shortcode as literal text. Deactivating the plugin
 * therefore puts `[download id="136"]` in front of visitors on every page that uses one.
 * These stand-ins swallow that.
 *
 * The theme's template overrides in download-monitor/ need no such guard: the plugin
 * loads them through locate_template(), so they never run without it.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Register stand-ins for Download Monitor's shortcodes when the plugin is absent.
 *
 * WHY: hooked late so the real plugin always wins — shortcode_exists() is only meaningful
 * after every plugin has registered on the default init priority.
 */
function ucf_brand_register_download_fallbacks() {
	foreach ( array( 'download', 'dlm_no_access' ) as $tag ) {
		if ( ! shortcode_exists( $tag ) ) {
			add_shortcode( $tag, 'ucf_brand_download_fallback' );
		}
	}
}
add_action( 'init', 'ucf_brand_register_download_fallbacks', 99 );

/**
 * Render nothing for visitors; tell an editor what is wrong.
 *
 * @return string
 */
function ucf_brand_download_fallback() {
	// WHY: silence for the public is the whole point, but a silent failure an editor
	// cannot see is how a missing download survives a content review.
	if ( ! current_user_can( 'edit_posts' ) ) {
		return '';
	}

	return sprintf(
		'<p class="ucf-download-missing"><strong>%s</strong> %s</p>',
		esc_html__( 'Download unavailable.', 'ucf-brand-block-theme' ),
		esc_html__( 'The Download Manager plugin is not active, so this file cannot be shown.', 'ucf-brand-block-theme' )
	);
}

/**
 * Drop Download Monitor's own "You must accept the terms…" line from the gate page.
 *
 * WHY: the No Access page states this in its own content, in the site's voice. The plugin
 * string is a second, blunter copy of the same instruction directly beneath it.
 *
 * UPSTREAM: this text replaces `dlm_no_access_error` whenever a download is terms-locked
 * (DLM_Integrated_Terms_And_Conditions::maybe_hide_no_access_message), so editing that
 * setting has no effect here — the filter is the only route.
 */
add_filter( 'dlm_terms_and_conditions_access_text', '__return_empty_string' );
