<?php
/**
 * Report — and on request rewrite — the paste artifacts already in the database.
 *
 * The theme cleans these at both boundaries it controls (see includes/paste-artifacts.php),
 * but a display filter does not change what is stored: the row still holds the characters,
 * an export still carries them, and anything reading `post_content` directly still sees
 * them. This is the sweep for what is already there.
 *
 * Run it from the site root:
 *
 *     wp eval-file tools/paste-artifacts.php           # report only, changes nothing
 *     wp eval-file tools/paste-artifacts.php fix       # rewrite the affected rows
 *
 * Reporting is the default deliberately. `fix` writes to every affected post, and the
 * report is what tells you whether that is one stray byte-order mark or two hundred spaces
 * across a section you are mid-way through editing.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

if ( ! function_exists( 'ucf_brand_paste_artifacts' ) ) {
	WP_CLI::error( 'The theme is not active — includes/paste-artifacts.php has not loaded.' );
}

$ucf_brand_fix = in_array( 'fix', (array) $args, true ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- `$args` is WP-CLI's, set by eval-file.

/*
 * Names for the report. The table itself lives with the filter in includes/, so this cannot
 * drift from what the theme actually replaces — the keys come from there.
 */
$ucf_brand_names = array(
	"\u{202F}" => 'U+202F NARROW NO-BREAK SPACE',
	"\u{2007}" => 'U+2007 FIGURE SPACE',
	"\u{200B}" => 'U+200B ZERO WIDTH SPACE',
	"\u{2060}" => 'U+2060 WORD JOINER',
	"\u{FEFF}" => 'U+FEFF ZERO WIDTH NO-BREAK SPACE',
);

$ucf_brand_posts = get_posts(
	array(
		'post_type'        => 'any',
		'post_status'      => 'any',
		'numberposts'      => -1,
		'suppress_filters' => false,
	)
);

$ucf_brand_totals   = array();
$ucf_brand_affected = 0;

foreach ( $ucf_brand_posts as $ucf_brand_post ) {
	$ucf_brand_found = array();

	foreach ( array_keys( ucf_brand_paste_artifacts() ) as $ucf_brand_char ) {
		$ucf_brand_count = substr_count( $ucf_brand_post->post_content, $ucf_brand_char )
			+ substr_count( $ucf_brand_post->post_title, $ucf_brand_char )
			+ substr_count( $ucf_brand_post->post_excerpt, $ucf_brand_char );

		if ( $ucf_brand_count ) {
			$ucf_brand_found[ $ucf_brand_char ] = $ucf_brand_count;

			$ucf_brand_totals[ $ucf_brand_char ] = ( $ucf_brand_totals[ $ucf_brand_char ] ?? 0 ) + $ucf_brand_count;
		}
	}

	if ( ! $ucf_brand_found ) {
		continue;
	}

	++$ucf_brand_affected;

	$ucf_brand_detail = array();

	foreach ( $ucf_brand_found as $ucf_brand_char => $ucf_brand_count ) {
		$ucf_brand_detail[] = $ucf_brand_names[ $ucf_brand_char ] . ' x' . $ucf_brand_count;
	}

	WP_CLI::log(
		sprintf(
			'#%d %s — %s',
			$ucf_brand_post->ID,
			$ucf_brand_post->post_title,
			implode( ', ', $ucf_brand_detail )
		)
	);

	if ( ! $ucf_brand_fix ) {
		continue;
	}

	/*
	 * SAFETY: wp_slash() on the way in, because wp_update_post() unslashes what it is given —
	 * the same hazard tests/a11y/seed.php records. Unslashed, the backslashes escaping quotes
	 * inside a block comment's attribute JSON would be eaten and every attribute on the page
	 * would parse as null.
	 *
	 * The normalization itself is `wp_insert_post_data`'s, which this update runs through, so
	 * the sweep and the save path cannot disagree about what a clean row looks like.
	 */
	$ucf_brand_result = wp_update_post(
		wp_slash(
			array(
				'ID'           => $ucf_brand_post->ID,
				'post_content' => $ucf_brand_post->post_content,
				'post_title'   => $ucf_brand_post->post_title,
				'post_excerpt' => $ucf_brand_post->post_excerpt,
			)
		),
		true
	);

	if ( is_wp_error( $ucf_brand_result ) ) {
		WP_CLI::warning( sprintf( '  could not rewrite #%d: %s', $ucf_brand_post->ID, $ucf_brand_result->get_error_message() ) );
	}
}

if ( ! $ucf_brand_affected ) {
	WP_CLI::success( sprintf( 'Scanned %d posts. No paste artifacts stored.', count( $ucf_brand_posts ) ) );
	return;
}

$ucf_brand_summary = array();

foreach ( $ucf_brand_totals as $ucf_brand_char => $ucf_brand_count ) {
	$ucf_brand_summary[] = $ucf_brand_names[ $ucf_brand_char ] . ': ' . $ucf_brand_count;
}

WP_CLI::log( '' );
WP_CLI::log( implode( "\n", $ucf_brand_summary ) );

if ( $ucf_brand_fix ) {
	WP_CLI::success( sprintf( 'Rewrote %d of %d posts.', $ucf_brand_affected, count( $ucf_brand_posts ) ) );
} else {
	WP_CLI::success(
		sprintf(
			'%d of %d posts hold paste artifacts. Re-run with `fix` to rewrite them.',
			$ucf_brand_affected,
			count( $ucf_brand_posts )
		)
	);
}
