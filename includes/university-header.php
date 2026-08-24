<?php
/**
 * The UCF University Header.
 *
 * The black bar across the top of every page is not this theme's markup. It is built at
 * runtime by a script from universityheader.ucf.edu, which owns its links, its search and
 * its appearance — UCF's terms are that none of the three may be changed locally, so this
 * file is deliberately the whole integration: put the script on the page with the id it
 * looks itself up by, and leave it an empty div to fill.
 *
 * Reference: https://universityheader.ucf.edu/
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Handle the header script is registered under.
 *
 * SYNC: `ucf_brand_university_header_script_tag()` rewrites the `{handle}-js` id WordPress
 * generates from this, so the two have to be reading the same string.
 */
const UCF_BRAND_UNIVERSITY_HEADER_HANDLE = 'ucf-brand-university-header';

/**
 * The URL of the header script, query string and all.
 *
 * WHY: `?use-full-width=1` is load-bearing twice over and cannot be moved out into a
 * setting. The host serves a *different build* of the script for that query string — the
 * default build has no full-width branch compiled into it at all — and that build then reads
 * the option back out of its own `src` at runtime. Lose the query string either side of that
 * and the bar silently renders boxed at 940px instead.
 *
 * WHY: `https://`, not the protocol-relative `//` the published snippet still prints. That
 * form exists to serve http pages, which is not a case worth carrying.
 *
 * @return string Absolute URL of the header script.
 */
function ucf_brand_university_header_src() {
	/**
	 * Filters the University Header script URL.
	 *
	 * The seam for the other documented options — `use-1200-breakpoint`,
	 * `use-bootstrap-overrides` — without editing the theme.
	 *
	 * @param string $src Absolute URL, including its option query string.
	 */
	return apply_filters(
		'ucf_brand_university_header_src',
		'https://universityheader.ucf.edu/bar/js/university-header.js?use-full-width=1'
	);
}

/**
 * Put the header script on the page.
 *
 * WHY: the head, and not deferred. The bar has to be the first visible thing on the page —
 * UCF's rule, not a preference — and the script fetches its own stylesheet only once it has
 * run, so anything that postpones it buys a paint with no header followed by a 50px jump.
 *
 * WHY: no version. The URL already carries the only query string this host reads; a
 * WordPress `?ver=` would be *this site's* version stamped onto someone else's asset, and
 * the script is versioned by its own server either way (`UCFHB_VERSION`, baked into the
 * response body).
 *
 * @return void
 */
function ucf_brand_enqueue_university_header() {
	wp_enqueue_script(
		UCF_BRAND_UNIVERSITY_HEADER_HANDLE,
		ucf_brand_university_header_src(),
		array(),
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Deliberate; see above.
		null,
		false
	);
}
add_action( 'wp_enqueue_scripts', 'ucf_brand_enqueue_university_header' );

/**
 * Give the script tag the id the header looks itself up by.
 *
 * UPSTREAM: the script reads its options back out of `document.getElementById(
 * 'ucfhb-script' ).getAttribute( 'src' )`. Without the id it finds nothing, falls through to
 * its defaults, and `use-full-width` is ignored — with no error anywhere, on a bar that
 * still renders.
 *
 * FIX: matched on the handle, never on `$src`. UCF's published snippet tests the src for
 * `universityheader.ucf.edu`, which is wrong in both directions: it also stamps this id onto
 * any *other* script from that host — the UCF Header Bar plugin's — and two elements sharing
 * one id makes `getElementById` a coin toss; and it stops matching the moment an optimizer
 * filters our own src to a proxy. The handle is a constant here and names one enqueue.
 *
 * @param string $tag    The `<script>` tag for the enqueued script.
 * @param string $handle The script's registered handle.
 * @return string Filtered tag.
 */
function ucf_brand_university_header_script_tag( $tag, $handle ) {
	if ( UCF_BRAND_UNIVERSITY_HEADER_HANDLE !== $handle ) {
		return $tag;
	}

	return str_replace( "{$handle}-js", 'ucfhb-script', $tag );
}
add_filter( 'script_loader_tag', 'ucf_brand_university_header_script_tag', 10, 2 );

/**
 * Print the element the header renders itself into.
 *
 * WHY: an explicit placeholder rather than letting the script pick a spot. Left alone it
 * does `document.body.insertBefore( div, document.body.firstChild )`, which makes where the
 * bar lands a race with anything else writing to the top of the body.
 *
 * A11Y: `wp_body_open` fires before the template, and core inserts its "Skip to content"
 * link immediately before `div.wp-site-blocks` — so the bar's own controls come first in tab
 * order. WCAG 2.4.1 is still met, by the `<main>` landmark every template here carries.
 *
 * @return void
 */
function ucf_brand_university_header_placeholder() {
	echo '<div id="ucfhb"></div>';
}
add_action( 'wp_body_open', 'ucf_brand_university_header_placeholder' );
