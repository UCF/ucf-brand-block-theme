<?php
/**
 * Seed the UCF Brand site.
 *
 * Run with:
 *   wp --path=/Users/jabarnes/Projects/lamp-pod/www/wordpress \
 *      --url=http://localhost/wordpress/brand/ \
 *      eval-file /path/to/seed-brand.php
 *
 * Idempotent: pages are matched by slug and updated in place, so re-running after
 * editing the markup in pages/ refreshes content without creating duplicates.
 */

$pages_dir = __DIR__ . '/pages';

// slug => [ title, source file ]
$pages = array(
	'foundation'     => array( 'Foundation', 'foundation.html' ),
	'building-blocks' => array( 'Building Blocks', 'blocks.html' ),
	'identity'       => array( 'Identity', 'identity.html' ),
	'voice'          => array( 'Voice', 'voice.html' ),
	'photo-video'    => array( 'Photo & Video', 'photo.html' ),
	'digital'        => array( 'Digital', 'digital.html' ),
	'campaigns'      => array( 'Campaigns', 'campaigns.html' ),
	'measurement'    => array( 'Measurement', 'measurement.html' ),
	'governance'     => array( 'Governance', 'governance.html' ),
	'resources'      => array( 'Resources', 'resources.html' ),
	'meet-stu'       => array( 'Meet Stu', 'stu.html' ),
);

/**
 * Expand `<!--PATTERN:slug-->` placeholders into the registered pattern's block markup.
 *
 * Seeded pages hold real, editable block content — not a `core/pattern` reference, which
 * would resolve on the server and leave nothing for an editor to change. Pulling the
 * markup from the registry at seed time keeps `patterns/*.php` the single source of
 * truth instead of duplicating swatch and specimen markup into the page files.
 *
 * @param string $content Page content, possibly containing placeholders.
 * @return string Content with placeholders expanded.
 */
function ucf_seed_expand_patterns( $content ) {
	return preg_replace_callback(
		'/<!--PATTERN:([a-z0-9\/-]+)-->/i',
		static function ( $m ) {
			$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( $m[1] );

			if ( ! $pattern ) {
				WP_CLI::warning( "unregistered pattern '{$m[1]}' — placeholder left in place" );
				return $m[0];
			}

			return $pattern['content'];
		},
		$content
	);
}

/**
 * Create or update a page by slug.
 *
 * @param string $slug  Page slug.
 * @param string $title Page title.
 * @param string $file  Absolute path to a block-markup file.
 * @return int|null Page ID, or null when the source file is missing.
 */
function ucf_seed_page( $slug, $title, $file ) {
	if ( ! file_exists( $file ) ) {
		WP_CLI::warning( "missing source for '$slug' — skipped" );
		return null;
	}

	$content  = ucf_seed_expand_patterns( file_get_contents( $file ) );
	$existing = get_page_by_path( $slug, OBJECT, 'page' );

	$data = array(
		'post_type'    => 'page',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_status'  => 'publish',
	);

	if ( $existing ) {
		$data['ID'] = $existing->ID;
		$id         = wp_update_post( $data, true );
		$verb       = 'updated';
	} else {
		$id   = wp_insert_post( $data, true );
		$verb = 'created';
	}

	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( "$slug: " . $id->get_error_message() );
		return null;
	}

	WP_CLI::log( sprintf( '    %-8s %-18s (ID %d, %d bytes)', $verb, $slug, $id, strlen( $content ) ) );

	return $id;
}

WP_CLI::log( '==> Site settings' );
update_option( 'permalink_structure', '/%postname%/' );
update_option( 'blogname', 'Brand Central' );
update_option( 'blogdescription', 'UCF Brand Guidelines' );
WP_CLI::log( '    permalinks -> /%postname%/' );

WP_CLI::log( '==> Pages' );
$ids = array();
foreach ( $pages as $slug => $meta ) {
	$id = ucf_seed_page( $slug, $meta[0], "$pages_dir/{$meta[1]}" );
	if ( $id ) {
		$ids[ $slug ] = $id;
	}
}

WP_CLI::log( '==> Front page' );
$home_id = ucf_seed_page( 'home', 'Brand Central', "$pages_dir/home.html" );
if ( $home_id ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home_id );
	WP_CLI::log( "    front page -> ID $home_id" );
}

// ── Navigation ───────────────────────────────────────────────────────────────
//
// One level deep, by design. Sub-navigation is derived from each page's H2s at
// runtime by assets/js/brand-nav.js — it is never authored here.

WP_CLI::log( '==> Navigation' );
$nav_blocks = array();
foreach ( $pages as $slug => $meta ) {
	if ( ! isset( $ids[ $slug ] ) ) {
		continue;
	}

	$nav_blocks[] = sprintf(
		'<!-- wp:navigation-link {"label":"%s","type":"page","id":%d,"kind":"post-type","url":"%s"} /-->',
		esc_attr( $meta[0] ),
		$ids[ $slug ],
		esc_url( get_permalink( $ids[ $slug ] ) )
	);
}
$nav_content = implode( "\n", $nav_blocks );

$existing_nav = get_page_by_path( 'brand-navigation', OBJECT, 'wp_navigation' );
$nav_data     = array(
	'post_type'    => 'wp_navigation',
	'post_title'   => 'Brand Navigation',
	'post_name'    => 'brand-navigation',
	'post_content' => $nav_content,
	'post_status'  => 'publish',
);

if ( $existing_nav ) {
	$nav_data['ID'] = $existing_nav->ID;
	$nav_id         = wp_update_post( $nav_data, true );
	WP_CLI::log( "    updated  brand-navigation (ID $nav_id, " . count( $nav_blocks ) . ' links)' );
} else {
	$nav_id = wp_insert_post( $nav_data, true );
	WP_CLI::log( "    created  brand-navigation (ID $nav_id, " . count( $nav_blocks ) . ' links)' );
}

// Core's Navigation block falls back to the most recent wp_navigation post. Remove
// the stock one WordPress ships so the fallback can only resolve to ours.
$stock_nav = get_page_by_path( 'navigation', OBJECT, 'wp_navigation' );
if ( $stock_nav && $stock_nav->ID !== $nav_id ) {
	wp_delete_post( $stock_nav->ID, true );
	WP_CLI::log( "    removed stock navigation (ID {$stock_nav->ID})" );
}

// ── Cleanup ──────────────────────────────────────────────────────────────────

WP_CLI::log( '==> Cleanup' );
foreach ( array( 'sample-page' => 'page', 'hello-world' => 'post' ) as $slug => $type ) {
	$stock = get_page_by_path( $slug, OBJECT, $type );
	if ( $stock ) {
		wp_delete_post( $stock->ID, true );
		WP_CLI::log( "    deleted  $slug (ID {$stock->ID})" );
	}
}

flush_rewrite_rules( false );
WP_CLI::success( 'Seeded. http://localhost/wordpress/brand/' );
