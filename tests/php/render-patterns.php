<?php
/**
 * Render every pattern file to its block markup, as JSON on stdout.
 *
 * Patterns cannot be read off disk and parsed directly: they interpolate PHP *inside* their
 * block markup — an `esc_html_e()` call sits between the block delimiters — so the file on
 * disk is not the markup WordPress registers. This renders them the same way core does in
 * `_register_theme_block_patterns()`: buffer the output, include the file, keep what it
 * echoed.
 *
 * The escaping stubs come from stubs/wp-escaping.php, shared with the unit suite, so the
 * markup this emits is escaped exactly the way those tests assert it is. Two copies would
 * mean the validity sweep validates markup the site never emits.
 *
 * Consumed by tests/js/markup-validity.test.js. Run directly to inspect:
 *
 *     php tests/php/render-patterns.php | python3 -m json.tool | head -40
 *
 * Exits non-zero with a message on stderr if a pattern renders nothing, which is almost
 * always a PHP error swallowed by the buffer rather than a genuinely empty pattern.
 *
 * @package ucf-brand-block-theme
 */

// Shared with the unit suite, so a pattern is escaped here exactly the way those tests
// assert it is. (This comment is also load-bearing for phpcs: Squiz.Commenting.FileComment
// mistakes a docblock followed immediately by `require_once` for documentation of the
// require, and then reports the file as having no doc comment at all.)
require_once __DIR__ . '/stubs/wp-escaping.php';

/**
 * Collect pattern files at any depth under patterns/.
 *
 * @param string $dir Directory to walk.
 * @return string[] Absolute paths, sorted for deterministic output.
 */
function ucf_brand_collect_pattern_files( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return array();
	}

	$found    = array();
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );

	foreach ( $iterator as $file ) {
		if ( $file->isFile() && 'php' === strtolower( $file->getExtension() ) ) {
			$found[] = $file->getPathname();
		}
	}

	sort( $found );

	return $found;
}

/**
 * Render one pattern file.
 *
 * The include is wrapped so a pattern that emits nothing is reported rather than silently
 * contributing an empty entry the sweep would then "validate" without noticing.
 *
 * @param string $path Absolute path to the pattern file.
 * @return string Rendered markup.
 */
function ucf_brand_render_pattern_file( $path ) {
	ob_start();
	require $path;

	return (string) ob_get_clean();
}

/**
 * Render every pattern and print the JSON payload.
 *
 * Wrapped in a function rather than left at file scope so the script introduces no globals —
 * a pattern file is `require`d into whatever scope calls it, and a stray `$path` or `$post`
 * at top level would be visible to the pattern being rendered.
 *
 * @return int Process exit code.
 */
function ucf_brand_render_patterns_main() {
	$theme_dir = dirname( __DIR__, 2 );
	$files     = ucf_brand_collect_pattern_files( $theme_dir . '/patterns' );
	$rendered  = array();
	$empty     = array();

	foreach ( $files as $path ) {
		$markup = trim( ucf_brand_render_pattern_file( $path ) );
		$slug   = ltrim( str_replace( $theme_dir, '', $path ), DIRECTORY_SEPARATOR );

		if ( '' === $markup ) {
			$empty[] = $slug;
			continue;
		}

		$rendered[] = array(
			'file'    => $slug,
			'content' => $markup,
		);
	}

	if ( ! empty( $empty ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI script; WP_Filesystem has no notion of stderr.
		fwrite( STDERR, "Pattern files rendered empty:\n  " . implode( "\n  ", $empty ) . "\n" );
		return 1;
	}

	if ( empty( $rendered ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI script; WP_Filesystem has no notion of stderr.
		fwrite( STDERR, "No pattern files found under {$theme_dir}/patterns.\n" );
		return 1;
	}

	echo wp_json_encode( $rendered ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON payload for the test harness, not markup.

	return 0;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- An exit status, not output.
exit( ucf_brand_render_patterns_main() );
