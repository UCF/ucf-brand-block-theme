<?php
/**
 * Seed a wp-env instance with the content the accessibility suite walks.
 *
 * Run through WP-CLI, not the web:
 *
 *     wp-env run cli wp eval-file wp-content/themes/ucf-brand-block-theme/tests/a11y/seed.php
 *
 * `tools/a11y-tests.js` does that for you; `npm run env:seed` is the standalone door.
 *
 * Three families of page come out of this, and only the first is hand-written:
 *
 *   Routes    One page per template — front page, a numbered section, a post, the blog
 *             index, search, 404. Fixed, because a template is a fixed thing.
 *   Patterns  One page per registered `ucf-brand/*` pattern, read out of
 *             `WP_Block_Patterns_Registry` rather than listed here. A new pattern is
 *             therefore covered the next time this runs, with nothing to remember.
 *   Variants  One page per registered block style, read out of `WP_Block_Styles_Registry`
 *             the same way. See the note above SAMPLES for the part that is not automatic.
 *
 * It ends by writing `seeded.json` next to this file — the manifest the Playwright specs
 * read to know what exists and what to call it. The suite refuses to run without it, so a
 * seed that failed halfway cannot be mistaken for a site with nothing wrong.
 *
 * Re-running is safe: everything it makes is tagged with UCF_BRAND_A11Y_SEED_META and deleted on the way
 * in, so this is a reset rather than an append.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	die( "tests/a11y/seed.php must run under WP-CLI.\n" );
}

/*
 * KSES strips HTML comments, and a block *is* an HTML comment. Left on, every
 * `wp_insert_post()` below would store content with the block delimiters gone — the pages
 * would still render (as raw HTML), so this fails by producing plausible-looking pages that
 * are not the blocks under test. WP-CLI runs with no logged-in user, so
 * `current_user_can( 'unfiltered_html' )` is false and `kses_init_filters()` has already
 * attached them on `init`.
 */
kses_remove_filters();

/** Meta key marking a post as this script's, so a re-run can clear the last one. */
const UCF_BRAND_A11Y_SEED_META = '_ucf_brand_a11y_seed';

/** Search route's query, and a word every seeded section page contains. */
const UCF_BRAND_A11Y_SEARCH_TERM = 'typography';

ucf_brand_a11y_seed();

/**
 * Build the whole fixture site and write the manifest.
 *
 * @return void
 */
function ucf_brand_a11y_seed() {
	ucf_brand_a11y_assert_theme_active();
	ucf_brand_a11y_reset();

	// Pretty permalinks: the manifest hands Playwright paths like `/colors/`, and with the
	// default `?p=` structure every one of them would 404 into a green-looking 404 test.
	global $wp_rewrite;
	$wp_rewrite->set_permalink_structure( '/%postname%/' );
	ucf_brand_a11y_write_htaccess();

	$manifest = array(
		'routes'   => ucf_brand_a11y_seed_routes(),
		'blocks'   => ucf_brand_a11y_seed_blocks(),
		'patterns' => ucf_brand_a11y_seed_patterns(),
		'variants' => ucf_brand_a11y_seed_variants(),
	);

	ucf_brand_a11y_assert_blocks_covered();

	$wp_rewrite->flush_rules( true );
	wp_get_theme()->delete_pattern_cache();

	$path  = __DIR__ . '/seeded.json';
	$bytes = file_put_contents( $path, wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- CLI-only fixture writer; WP_Filesystem is not bootstrapped here.

	if ( false === $bytes ) {
		WP_CLI::error( "Could not write the manifest to {$path}." );
	}

	WP_CLI::success(
		sprintf(
			'Seeded %d routes, %d blocks, %d patterns, %d variants.',
			count( $manifest['routes'] ),
			count( $manifest['blocks'] ),
			count( $manifest['patterns'] ),
			count( $manifest['variants'] )
		)
	);
}

/**
 * Refuse to run against any theme but this one.
 *
 * This checks rather than fixes, and that distinction is the whole point. An earlier version
 * called `switch_theme()` here, which does not do what it looks like it does: WordPress has
 * already booted by the time `eval-file` runs, so `functions.php` loaded for whatever theme
 * was active and `init` has been and gone. The switch writes the option and nothing else —
 * this process still sees empty pattern and block-style registries, while the *next* process
 * gets the theme.
 *
 * So the seed failed on a clean environment and passed on every run after it: green on any
 * machine that had run it once, red on every CI runner, which is how it shipped. Failing
 * loudly is strictly better than a fix that hides itself on the second attempt.
 *
 * `tools/a11y-tests.js` activates the theme in a separate WP-CLI invocation, which is the
 * arrangement that actually works. `tests/integration/bootstrap.php` solves the same problem
 * from the other side, switching at `muplugins_loaded` — before the theme loads.
 *
 * @return void
 */
function ucf_brand_a11y_assert_theme_active() {
	$active = get_stylesheet();

	if ( 'ucf-brand-block-theme' !== $active ) {
		WP_CLI::error(
			"The active theme is '{$active}', so this would seed and audit that theme instead.\n"
			. 'Activate it first, in its own command — switching from inside this script is too '
			. "late to matter:\n"
			. '  wp-env run cli wp theme activate ucf-brand-block-theme'
		);
	}
}

/**
 * Write the rewrite rules into `.htaccess` by hand.
 *
 * `flush_rules( true )` will not do it from here. It guards the write behind
 * `got_mod_rewrite()`, which asks the *current* server whether mod_rewrite is loaded — and
 * the current server is PHP-CLI in the `cli` container, which has no Apache and answers no.
 * The `wordpress` container serving port 8888 does have it; nothing ever asks that one.
 *
 * The symptom is worth recognizing, because it does not look like a permalink problem: every
 * seeded path returns Apache's own bare "Not Found" page, so the suite audits a page with no
 * `<html lang>` and reports a `html-has-lang` violation on a theme that sets it correctly.
 * `wp rewrite flush --hard` fails the same way for the same reason.
 *
 * `mod_rewrite_rules()` only builds the string, so calling it directly and writing the result
 * skips the check without faking it.
 *
 * @return void
 */
function ucf_brand_a11y_write_htaccess() {
	global $wp_rewrite;

	require_once ABSPATH . 'wp-admin/includes/misc.php';

	$written = insert_with_markers(
		ABSPATH . '.htaccess',
		'WordPress',
		explode( "\n", $wp_rewrite->mod_rewrite_rules() )
	);

	if ( ! $written ) {
		WP_CLI::error(
			'Could not write ' . ABSPATH . '.htaccess. Without it every pretty permalink '
			. '404s and the whole suite audits Apache error pages.'
		);
	}
}

/**
 * Give a page a featured image.
 *
 * The home hero's mobile half *is* the featured image — below the breakpoint it replaces the
 * video outright — so a fixture without one cannot show whether that swap works.
 *
 * The bytes are a 1×1 PNG inline rather than a checked-in fixture file: `object-fit: cover`
 * scales it to the band either way, and one less binary in the repo is one less thing to
 * explain.
 *
 * @param int $post_id Page to attach it to.
 * @return void
 */
function ucf_brand_a11y_attach_featured_image( $post_id ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';

	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Fixture image bytes, not obfuscation.
	$png     = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==' );
	$uploads = wp_upload_dir();
	$path    = trailingslashit( $uploads['path'] ) . 'ucf-brand-a11y-hero-' . $post_id . '.png';

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- CLI-only fixture writer; WP_Filesystem is not bootstrapped here.
	if ( false === file_put_contents( $path, $png ) ) {
		WP_CLI::error( "Could not write the fixture featured image to {$path}." );
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => 'Fixture featured image',
			'post_status'    => 'inherit',
		),
		$path,
		$post_id
	);

	if ( ! $attachment_id ) {
		WP_CLI::error( 'Could not create the fixture featured image attachment.' );
	}

	wp_update_attachment_metadata(
		$attachment_id,
		wp_generate_attachment_metadata( $attachment_id, $path )
	);

	// A11Y: decorative. The hero's own h1 names the page, so an alt repeating it would
	// announce the same words twice — the same trade as the wordmark in includes/branding.php.
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', '' );

	set_post_thumbnail( $post_id, $attachment_id );
}

/**
 * Delete everything a previous run created.
 *
 * By meta rather than by slug: a run that changed the naming scheme would otherwise leave
 * its predecessor's pages behind, and those stale pages are indistinguishable from real
 * ones in a report.
 *
 * @return void
 */
function ucf_brand_a11y_reset() {
	$stale = get_posts(
		array(
			'post_type'        => 'any',
			'post_status'      => 'any',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-off CLI teardown over a fixture site.
			'meta_key'         => UCF_BRAND_A11Y_SEED_META,
		)
	);

	foreach ( $stale as $id ) {
		wp_delete_post( $id, true );
	}
}

/**
 * Create a page or post and tag it as seeded.
 *
 * @param array<string, mixed> $args   Arguments for `wp_insert_post()`, minus the status.
 * @param array<string, mixed> $meta   Post meta to attach.
 * @return int The new post ID.
 */
function ucf_brand_a11y_insert( array $args, array $meta = array() ) {
	// UPSTREAM: wp_insert_post() unslashes what it is given, which eats the backslashes JSON
	// uses to escape a quote inside a block comment's attributes — the attrs then parse as
	// null and the block renders with none of them, looking merely empty. Anything seeded
	// here that carries markup inside an attribute (the index block's descriptions do) needs
	// the content slashed on the way in, exactly as the editor's REST save slashes it.
	if ( isset( $args['post_content'] ) ) {
		$args['post_content'] = wp_slash( $args['post_content'] );
	}

	$id = wp_insert_post(
		array_merge(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			),
			$args
		),
		true
	);

	if ( is_wp_error( $id ) ) {
		WP_CLI::error( 'Could not create "' . ( $args['post_title'] ?? '?' ) . '": ' . $id->get_error_message() );
	}

	update_post_meta( $id, UCF_BRAND_A11Y_SEED_META, 1 );

	foreach ( $meta as $key => $value ) {
		update_post_meta( $id, $key, $value );
	}

	return $id;
}

/**
 * One page per template, plus the two routes that are queries rather than posts.
 *
 * The section pages carry `ucf_brand_number`, which is what puts them in the drawer and
 * gives each one its hero label — so these three are also what makes the sidebar under
 * *every* other seeded page non-empty.
 *
 * @return array<int, array<string, string>> Manifest entries.
 */
function ucf_brand_a11y_seed_routes() {
	$home = ucf_brand_a11y_insert(
		array(
			'post_title'   => 'Brand Guidelines',
			'post_name'    => 'a11y-home',
			'post_content' => ucf_brand_a11y_front_page_content(),
		)
	);

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );

	// templates/front-page.html shows this below the video breakpoint, in place of the
	// video. Without it the mobile half of the home hero is empty. See home-hero.spec.js.
	ucf_brand_a11y_attach_featured_image( $home );

	// Three, not one: `ucf_brand_get_ordered_sections()` sorts numerically, and a drawer with
	// a single entry would render the same whether or not that ordering works.
	$sections = array(
		1  => 'Logos',
		2  => 'Color',
		10 => 'Typography',
	);

	foreach ( $sections as $number => $title ) {
		ucf_brand_a11y_insert(
			array(
				'post_title'   => $title,
				'post_name'    => 'a11y-section-' . sanitize_title( $title ),
				'post_content' => ucf_brand_a11y_section_content( $title ),
			),
			array(
				'ucf_brand_number'    => $number,
				'ucf_brand_deck'      => 'How ' . strtolower( $title ) . ' works across the university brand system.',
				'ucf_brand_hero_note' => 'Questions about ' . strtolower( $title ) . '? Reach University Brand and Marketing.',
			)
		);
	}

	ucf_brand_a11y_insert(
		array(
			'post_type'    => 'post',
			'post_title'   => 'A post, for the single and index templates',
			'post_name'    => 'a11y-post',
			'post_content' => '<!-- wp:paragraph -->' . "\n"
				. '<p>Body copy for the single post template, which the brand guide itself barely uses '
				. 'but still ships. Includes <a href="/a11y-section-color/">a link</a> so link contrast '
				. 'is judged in running text rather than in isolation.</p>' . "\n"
				. '<!-- /wp:paragraph -->',
		)
	);

	return array(
		array(
			'name' => 'front-page',
			'path' => '/',
		),
		array(
			'name' => 'page-section',
			'path' => '/a11y-section-typography/',
		),
		array(
			'name' => 'single-post',
			'path' => '/a11y-post/',
		),
		array(
			'name' => 'index-blog',
			'path' => '/?post_type=post',
		),
		array(
			'name' => 'search-results',
			'path' => '/?s=' . UCF_BRAND_A11Y_SEARCH_TERM,
		),
		array(
			'name' => 'search-no-results',
			'path' => '/?s=' . rawurlencode( 'zzzz no results zzzz' ),
		),
		// The only route expected not to return 200. Declared, because the suite treats an
		// unexpected 404 as a failure — a mistyped path would otherwise audit the 404 template
		// and pass.
		array(
			'name'   => 'notfound-404',
			'path'   => '/a11y-this-page-does-not-exist/',
			'status' => 404,
		),
	);
}

/**
 * A page per custom block that no pattern or template already renders.
 *
 * `tabs` and `section-index` are in that position — `page-hero` is in `templates/page.html`
 * and `color-swatches` is in a pattern, so both are already on pages the suite visits.
 * `ucf_brand_a11y_assert_blocks_covered()` is what tells you when that stops being true.
 *
 * `section-index` is inserted into page content by hand, so no pattern or template carries
 * it and this page is its only coverage. It needs real H2s to index and a Brand number to
 * number them with — on a page with neither the block correctly renders nothing, and
 * auditing an empty element is the green-for-the-wrong-reason case this suite exists to
 * avoid. The manifest's `class` check is what refuses to audit if it rendered nothing.
 *
 * Tabs is also the block with the most to check. Its saved markup is a role-free stack of
 * label/panel pairs; `src/blocks/tabs/view.js` adds the `tablist`/`tab`/`tabpanel` roles and
 * the keyboard handling at runtime, and *only* above 768px. So the desktop and tablet
 * viewports axe a real tab widget while the mobile one axes the plain stack the block falls
 * back to — two different accessibility trees from one page, which is the reason the viewport
 * matrix is not decoration here.
 *
 * @return array<int, array<string, string>> Manifest entries.
 */
function ucf_brand_a11y_seed_blocks() {
	// Two tabs, not one: a tablist with a single tab exercises neither the roving tabindex
	// nor the arrow-key handling, and `collect()` in view.js bails on an incomplete pair.
	$tabs = '<!-- wp:ucf-brand/tabs -->' . "\n"
		. '<div class="wp-block-ucf-brand-tabs ucf-tabs">'
		. ucf_brand_a11y_tab(
			'Use the primary mark',
			'The full lockup, on a field with room to breathe.',
			'Clear space around the mark is measured from the pegasus.'
		)
		. ucf_brand_a11y_tab(
			'Recolor the mark',
			'Gold or black, and nothing else.',
			'The mark is gold or black. Nothing else is approved.'
		)
		. '</div>' . "\n"
		. '<!-- /wp:ucf-brand/tabs -->';

	ucf_brand_a11y_insert(
		array(
			'post_title'   => 'Block: Tabs',
			'post_name'    => 'a11y-block-tabs',
			'post_content' => $tabs,
		)
	);

	ucf_brand_a11y_insert(
		array(
			'post_title'   => 'Block: Section index',
			'post_name'    => 'a11y-block-section-index',
			// One description carries inline markup and one does not: the field is rich text,
			// and a link inside a description is its own contrast pair for axe to measure.
			'post_content' => '<!-- wp:ucf-brand/section-index {"heading":"This section covers:","descriptions":'
				. '{"Using the wordmark":"Where it goes, and <a href=\"/a11y-section-logos/\">how much room</a> it needs.",'
				. '"What to avoid with the wordmark":"The cases that come up most often."}} /-->'
				. "\n\n" . ucf_brand_a11y_section_content( 'the wordmark' ),
		),
		// The number is what gives each entry its "3.1" label; without it the block renders
		// the list with no number column and the mono treatment goes unaudited.
		array( 'ucf_brand_number' => 3 )
	);

	/*
	 * The hero's Light treatment, which is a page-level field rather than a block style
	 * (includes/hero.php) and so is invisible to the variants tier that reads the style
	 * registry. Dark needs no page of its own: the hero is in templates/page.html, so every
	 * other seeded page already audits it. The deck and the note are set because the light
	 * band is white — the copy on it is the pair axe has to measure.
	 */
	ucf_brand_a11y_insert(
		array(
			'post_title'   => 'Hero: Light treatment',
			'post_name'    => 'a11y-hero-light',
			'post_content' => ucf_brand_a11y_section_content( 'the light hero' ),
		),
		array(
			'ucf_brand_number'         => 4,
			'ucf_brand_hero_treatment' => 'light',
			'ucf_brand_deck'           => 'The deck under the title, on the light band.',
			'ucf_brand_hero_note'      => 'The closing note, which is body copy.',
		)
	);

	return array(
		array(
			'name' => 'ucf-brand/tabs',
			'path' => '/a11y-block-tabs/',
		),
		array(
			// The class is the assertion: it is only on the page if the render filter ran, and
			// a hero audited in the template's own Dark is a green result for the wrong band.
			'name'  => 'ucf-brand/page-hero',
			'path'  => '/a11y-hero-light/',
			'class' => 'is-style-light',
		),
		array(
			'name'  => 'ucf-brand/section-index',
			'path'  => '/a11y-block-section-index/',
			'class' => 'brand-index',
		),
	);
}

/**
 * One `ucf-brand/tab`, with its label and panel.
 *
 * The markup mirrors `tests/js/__snapshots__/blocks.test.js.snap`, which is the committed
 * record of what `save()` emits. `heading` is sourced out of the markup rather than
 * stored as JSON, which is why the label block carries no attributes.
 *
 * Both parts of the label are here on purpose. The description is the element the enhanced
 * strip paints white on black and grey once the tab is selected — two contrast pairs axe
 * cannot measure on a label that has only a heading, which is what this seeded for at first.
 *
 * @param string $heading     Tab heading.
 * @param string $description Label description, under the heading.
 * @param string $copy        Panel copy.
 * @return string Block markup.
 */
function ucf_brand_a11y_tab( $heading, $description, $copy ) {
	return '<!-- wp:ucf-brand/tab -->' . "\n"
		. '<div class="wp-block-ucf-brand-tab ucf-tabs__set"><!-- wp:ucf-brand/tab-label -->' . "\n"
		. '<div class="wp-block-ucf-brand-tab-label ucf-tabs__label">'
		. '<h3 class="ucf-tabs__heading">' . esc_html( $heading ) . '</h3>'
		. '<p class="ucf-tabs__description">' . esc_html( $description ) . '</p></div>' . "\n"
		. '<!-- /wp:ucf-brand/tab-label -->' . "\n\n"
		. '<!-- wp:ucf-brand/tab-panel -->' . "\n"
		. '<div class="wp-block-ucf-brand-tab-panel ucf-tabs__panel"><!-- wp:paragraph -->' . "\n"
		. '<p>' . esc_html( $copy ) . '</p>' . "\n"
		. '<!-- /wp:paragraph --></div>' . "\n"
		. '<!-- /wp:ucf-brand/tab-panel --></div>' . "\n"
		. '<!-- /wp:ucf-brand/tab -->';
}

/**
 * One page per `ucf-brand/*` pattern, each holding that pattern and nothing else.
 *
 * The registry is the list, so a pattern added to `patterns/` is covered by the next seed
 * without anyone remembering to add it here — the property the plan asked for. `content` is
 * already the rendered result: core includes the pattern's PHP file and buffers its output
 * before registering it, which is why the translated strings and interpolated PHP inside
 * these files come through as markup.
 *
 * @return array<int, array<string, string>> Manifest entries.
 */
function ucf_brand_a11y_seed_patterns() {
	$entries  = array();
	$patterns = WP_Block_Patterns_Registry::get_instance()->get_all_registered();

	foreach ( $patterns as $pattern ) {
		/*
		 * Two spellings in one registry: patterns discovered from a theme's `patterns/`
		 * directory carry `slug`, while the eleven core registers itself carry `name` and no
		 * `slug` at all. Reading only `slug` works — the theme's patterns all have one — but
		 * emits an "undefined array key" warning for every core pattern it walks past.
		 */
		$slug = (string) ( $pattern['slug'] ?? $pattern['name'] ?? '' );

		if ( 0 !== strpos( $slug, 'ucf-brand/' ) ) {
			continue;
		}

		$content = trim( (string) ( $pattern['content'] ?? '' ) );

		if ( '' === $content ) {
			WP_CLI::error( "Pattern {$slug} registered with empty content." );
		}

		$short = sanitize_title( substr( $slug, strlen( 'ucf-brand/' ) ) );

		ucf_brand_a11y_insert(
			array(
				'post_title'   => 'Pattern: ' . $pattern['title'],
				'post_name'    => 'a11y-pattern-' . $short,
				'post_content' => $content,
			)
		);

		$entries[] = array(
			'name' => $slug,
			'path' => '/a11y-pattern-' . $short . '/',
		);
	}

	if ( ! $entries ) {
		WP_CLI::error( 'No ucf-brand patterns registered. Is the theme active?' );
	}

	return $entries;
}

/**
 * One page per registered block style.
 *
 * `WP_Block_Styles_Registry` holds exactly this theme's styles and nothing else — core
 * registers its own (`is-style-outline`, `is-style-dots`) on the client, so they never reach
 * the server registry. That makes the registry a clean list of what to cover.
 *
 * What is *not* automatic is the markup: a style is a class, and a class needs a block to sit
 * on. SAMPLES supplies one representative block per block type, and a style registered
 * against a type with no sample is a hard error rather than a silent skip — so adding a style
 * to a new block type breaks the seed until someone decides what that block should look like.
 *
 * @return array<int, array<string, string>> Manifest entries.
 */
function ucf_brand_a11y_seed_variants() {
	$entries  = array();
	$samples  = ucf_brand_a11y_samples();
	$registry = WP_Block_Styles_Registry::get_instance()->get_all_registered();

	foreach ( $registry as $block_name => $styles ) {
		if ( ! isset( $samples[ $block_name ] ) ) {
			WP_CLI::error(
				"Block styles are registered for {$block_name} but tests/a11y/seed.php has no sample "
				. 'markup for it. Add one to SAMPLES so the styles get covered.'
			);
		}

		foreach ( array_keys( $styles ) as $style ) {
			$class   = 'is-style-' . $style;
			$slug    = 'a11y-variant-' . sanitize_title( str_replace( '/', '-', $block_name ) . '-' . $style );
			$content = str_replace( '%CLASS%', $class, $samples[ $block_name ] );

			$content = ucf_brand_a11y_wrap_in_context( $block_name . ':' . $style, $content );

			ucf_brand_a11y_insert(
				array(
					'post_title'   => 'Variant: ' . $block_name . ' ' . $class,
					'post_name'    => $slug,
					'post_content' => $content,
				)
			);

			$entries[] = array(
				'name'  => $block_name . ' ' . $class,
				'path'  => '/' . $slug . '/',
				// The spec asserts this class is in the DOM before it trusts the axe run. A
				// page whose variant failed to render is a page that passes every check.
				'class' => $class,
			);
		}
	}

	return $entries;
}

/**
 * Put a style on the kind of field it was written for, when a bare page is the wrong one.
 *
 * Almost every style is self-contained: a composition supplies its own background, so
 * dropping it on the default page template audits it exactly as an author would get it.
 * `is-style-on-dark` is the exception, and `src/scss/_compositions.scss` says so where it is
 * defined — it "supplies the treatment only". It sets the `--brand-*` roles the helper
 * classes read and deliberately sets no background and no base `color`; both come from the
 * block's own color controls. `templates/front-page.html` used to be the reference usage —
 * it supplied both, a black `.brand-shell` around a group carrying `textColor: "white"` —
 * and that template is gone; the pairing it demonstrated is reproduced below instead.
 *
 * Audited on a bare page it produces five guaranteed failures — a near-black heading on
 * black, plus a gold eyebrow, grey meta, grey muted and a tint-blue link on white. Not one is
 * a defect in the theme; every one is the fixture asking a text treatment to do a
 * composition's job. Since this suite gates merges and its output goes to designers, a false
 * finding is worse than a missing one: it spends someone's afternoon on a colour that was
 * never wrong.
 *
 * The wrapper therefore reproduces the reference usage rather than approximating it. Text
 * color rides on the wrapper and inherits, which keeps SAMPLES generic.
 *
 * Keyed by `block-name:style` so a style name reused across block types stays distinct.
 *
 * @param string $key     `block-name:style`.
 * @param string $content The variant markup.
 * @return string The markup, wrapped if that style needs a field.
 */
function ucf_brand_a11y_wrap_in_context( $key, $content ) {
	$contexts = array(
		'core/group:on-dark' => '<!-- wp:group {"align":"full","textColor":"white","style":{"color":{"background":"#000000"},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->'
			. '<div class="wp-block-group alignfull has-white-color has-text-color has-background" style="background-color:#000000;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">'
			. '%INNER%'
			. '</div>'
			. '<!-- /wp:group -->',
	);

	if ( ! isset( $contexts[ $key ] ) ) {
		return $content;
	}

	return str_replace( '%INNER%', $content, $contexts[ $key ] );
}

/**
 * Representative markup per block type, with `%CLASS%` where the style class goes.
 *
 * Each is what `save()` produces for that block, so WordPress renders it rather than
 * recovering it. They are hand-written because there is no server-side way to ask a static
 * block what its `save()` emits — `tests/js/markup-validity.test.js` is where markup
 * correctness is actually enforced, and the DOM assertion in `variants.spec.js` is what
 * catches one of these going stale.
 *
 * `core/group` gets the probe rather than a paragraph, and that is the point of the whole
 * tier: the compositions (`is-style-dark`, `is-style-paper`, …) set the `--brand-*` roles
 * their contents read, so the same muted paragraph is legible in one and not in another.
 * Only a group carrying the full range of text finds that.
 *
 * @return array<string, string> Block name to markup.
 */
function ucf_brand_a11y_samples() {
	$probe = ucf_brand_a11y_probe();

	return array(
		'core/group'     => '<!-- wp:group {"className":"%CLASS%","layout":{"type":"constrained"}} -->' . "\n"
			. '<div class="wp-block-group %CLASS%">' . $probe . '</div>' . "\n"
			. '<!-- /wp:group -->',

		'core/paragraph' => '<!-- wp:paragraph {"className":"%CLASS%"} -->' . "\n"
			. '<p class="%CLASS%">Body copy in this paragraph style, long enough for axe to '
			. 'sample its rendered color against the page behind it, and carrying '
			. '<a href="/a11y-section-color/">a link</a> so the link color is judged too.</p>' . "\n"
			. '<!-- /wp:paragraph -->',

		'core/heading'   => '<!-- wp:heading {"className":"%CLASS%"} -->' . "\n"
			. '<h2 class="wp-block-heading %CLASS%">A heading in this style</h2>' . "\n"
			. '<!-- /wp:heading -->',

		'core/list'      => '<!-- wp:list {"className":"%CLASS%"} -->' . "\n"
			. '<ul class="wp-block-list %CLASS%">'
			. '<!-- wp:list-item --><li>First list item</li><!-- /wp:list-item -->'
			. '<!-- wp:list-item --><li>Second list item</li><!-- /wp:list-item -->'
			. '</ul>' . "\n"
			. '<!-- /wp:list -->',

		'core/columns'   => '<!-- wp:columns {"className":"%CLASS%"} -->' . "\n"
			. '<div class="wp-block-columns %CLASS%">'
			. '<!-- wp:column --><div class="wp-block-column">'
			. '<!-- wp:paragraph --><p>Left column copy.</p><!-- /wp:paragraph -->'
			. '</div><!-- /wp:column -->'
			. '<!-- wp:column --><div class="wp-block-column">'
			. '<!-- wp:paragraph --><p>Right column copy.</p><!-- /wp:paragraph -->'
			. '</div><!-- /wp:column -->'
			. '</div>' . "\n"
			. '<!-- /wp:columns -->',

		'core/separator' => '<!-- wp:paragraph --><p>Copy above the rule.</p><!-- /wp:paragraph -->' . "\n"
			. '<!-- wp:separator {"className":"%CLASS%"} -->' . "\n"
			. '<hr class="wp-block-separator has-alpha-channel-opacity %CLASS%"/>' . "\n"
			. '<!-- /wp:separator -->' . "\n"
			. '<!-- wp:paragraph --><p>Copy below the rule.</p><!-- /wp:paragraph -->',

		/*
		 * `is-style-glyph` is a transparent, borderless button, so its label is the only thing
		 * carrying the control's accessible name and its contrast is judged against the page
		 * rather than a filled background. Both are exactly what this page is for.
		 */
		'core/button'    => '<!-- wp:buttons -->' . "\n"
			. '<div class="wp-block-buttons">'
			. '<!-- wp:button {"className":"%CLASS%"} -->'
			. '<div class="wp-block-button %CLASS%">'
			. '<a class="wp-block-button__link wp-element-button" href="/a11y-section-color/">Read the guidance</a>'
			. '</div>'
			. '<!-- /wp:button -->'
			. '</div>' . "\n"
			. '<!-- /wp:buttons -->',

		/*
		 * Two items, the first open: a closed panel is hidden from the accessibility tree, so a
		 * single-item accordion would leave the panel's own contrast untested. Headings stay at
		 * H3 — the drawer sub-nav and the subsection badge are both driven off H2s, and an
		 * accordion is not a subsection. See CLAUDE.md.
		 */
		'core/accordion' => '<!-- wp:accordion {"className":"%CLASS%"} -->' . "\n"
			. '<div class="wp-block-accordion %CLASS%">'
			. ucf_brand_a11y_accordion_item( 'The first thing to know', 'Panel copy for the first item.', true )
			. ucf_brand_a11y_accordion_item( 'The second thing to know', 'Panel copy for the second item.', false )
			. '</div>' . "\n"
			. '<!-- /wp:accordion -->',
	);
}

/**
 * One `core/accordion-item`, with its heading and panel.
 *
 * @param string $title Heading text.
 * @param string $copy  Panel copy.
 * @param bool   $open  Whether the item starts expanded.
 * @return string Block markup.
 */
function ucf_brand_a11y_accordion_item( $title, $copy, $open ) {
	$attrs = $open ? ' {"openByDefault":true}' : '';

	return '<!-- wp:accordion-item' . $attrs . ' -->'
		. '<!-- wp:accordion-heading {"title":"' . esc_attr( $title ) . '","level":3} -->' . "\n"
		. '<h3 class="wp-block-accordion-heading">'
		. '<button class="wp-block-accordion-heading__toggle">'
		. '<span class="wp-block-accordion-heading__toggle-title">' . esc_html( $title ) . '</span>'
		. '<span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span>'
		. '</button></h3>' . "\n"
		. '<!-- /wp:accordion-heading -->'
		. '<!-- wp:accordion-panel -->' . "\n"
		. '<div class="wp-block-accordion-panel">'
		. '<!-- wp:paragraph --><p>' . esc_html( $copy ) . '</p><!-- /wp:paragraph -->'
		. '</div>' . "\n"
		. '<!-- /wp:accordion-panel -->'
		. '<!-- /wp:accordion-item -->';
}

/**
 * The contrast probe dropped inside every `core/group` variant.
 *
 * Every text style the theme registers, plus a link, a rule and a button — one of each, so a
 * composition that recolors its contents is measured against all of them at once. A group
 * variant page holding a single paragraph would pass while `is-style-muted` sat unreadable
 * inside it.
 *
 * @return string Block markup.
 */
function ucf_brand_a11y_probe() {
	$markup = '<!-- wp:heading --><h2 class="wp-block-heading">Heading inside the composition</h2><!-- /wp:heading -->'
		. '<!-- wp:paragraph --><p>Default body copy, with <a href="/a11y-section-color/">a link</a> in it.</p><!-- /wp:paragraph -->';

	foreach ( array( 'lead', 'eyebrow', 'meta', 'muted' ) as $style ) {
		$markup .= '<!-- wp:paragraph {"className":"is-style-' . $style . '"} -->'
			. '<p class="is-style-' . $style . '">Body copy in the ' . $style . ' style.</p>'
			. '<!-- /wp:paragraph -->';
	}

	$markup .= '<!-- wp:separator {"className":"is-style-accent-rule"} -->'
		. '<hr class="wp-block-separator has-alpha-channel-opacity is-style-accent-rule"/>'
		. '<!-- /wp:separator -->'
		. '<!-- wp:list --><ul class="wp-block-list">'
		. '<!-- wp:list-item --><li>A list item inside the composition</li><!-- /wp:list-item -->'
		. '</ul><!-- /wp:list -->'
		. '<!-- wp:buttons --><div class="wp-block-buttons">'
		. '<!-- wp:button --><div class="wp-block-button">'
		. '<a class="wp-block-button__link wp-element-button" href="/a11y-section-color/">A button inside it</a>'
		. '</div><!-- /wp:button -->'
		. '</div><!-- /wp:buttons -->';

	return $markup;
}

/**
 * Front page content — the front-page template renders `post-content` on a black field.
 *
 * @return string Block markup.
 */
function ucf_brand_a11y_front_page_content() {
	// A11Y: an h2, not an h1. templates/front-page.html gives the home hero a
	// `core/post-title` h1, so content that opens with its own would put two on the page —
	// which axe does not flag and a screen-reader user navigating by heading level does hit.
	return '<!-- wp:heading -->' . "\n"
		. '<h2 class="wp-block-heading">The UCF brand</h2>' . "\n"
		. '<!-- /wp:heading -->' . "\n"
		. '<!-- wp:paragraph {"className":"is-style-lead"} -->' . "\n"
		. '<p class="is-style-lead">Everything the university looks and sounds like, in one place.</p>' . "\n"
		. '<!-- /wp:paragraph -->' . "\n"
		. '<!-- wp:paragraph -->' . "\n"
		. '<p>Start with <a href="/a11y-section-logos/">the logos</a>, or search the guide.</p>' . "\n"
		. '<!-- /wp:paragraph -->';
}

/**
 * A numbered section page: two H2s so the drawer's derived sub-nav and the search results'
 * deep links both have something real to point at.
 *
 * @param string $title Section title, echoed into the copy so search has a term to match.
 * @return string Block markup.
 */
function ucf_brand_a11y_section_content( $title ) {
	$lower = strtolower( $title );

	return '<!-- wp:heading -->' . "\n"
		. '<h2 class="wp-block-heading">Using ' . esc_html( $lower ) . '</h2>' . "\n"
		. '<!-- /wp:heading -->' . "\n"
		. '<!-- wp:paragraph -->' . "\n"
		. '<p>Guidance for ' . esc_html( $lower ) . ' across print and digital, including '
		. esc_html( UCF_BRAND_A11Y_SEARCH_TERM ) . ' pairings and the rules that go with them.</p>' . "\n"
		. '<!-- /wp:paragraph -->' . "\n"
		. '<!-- wp:heading -->' . "\n"
		. '<h2 class="wp-block-heading">What to avoid with ' . esc_html( $lower ) . '</h2>' . "\n"
		. '<!-- /wp:heading -->' . "\n"
		. '<!-- wp:paragraph -->' . "\n"
		. '<p>The cases that come up most often, and what to do instead.</p>' . "\n"
		. '<!-- /wp:paragraph -->';
}

/**
 * Fail if a custom block ships without appearing anywhere the suite will look.
 *
 * Two of the three top-level blocks are covered incidentally — `page-hero` sits in
 * `templates/page.html` and `color-swatches` in a pattern — so nothing about that coverage is
 * declared, and a block could be added tomorrow with no page rendering it and the suite would
 * stay green while covering less. This is the check that says so out loud.
 *
 * Only top-level blocks are checked: `tab-label` has a `parent`, so it cannot appear except
 * inside `tabs`, and asserting on it separately would just be asserting twice.
 *
 * @return void
 */
function ucf_brand_a11y_assert_blocks_covered() {
	$haystack = '';

	$seeded = get_posts(
		array(
			'post_type'        => 'any',
			'post_status'      => 'any',
			'numberposts'      => -1,
			'suppress_filters' => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-off CLI check over a fixture site.
			'meta_key'         => UCF_BRAND_A11Y_SEED_META,
		)
	);

	foreach ( $seeded as $post ) {
		$haystack .= $post->post_content;
	}

	// Templates and parts count as coverage: a block in `templates/page.html` renders on every
	// page the suite visits, which is stronger coverage than a page of its own.
	foreach ( array( 'templates', 'parts' ) as $dir ) {
		foreach ( (array) glob( get_theme_file_path( $dir ) . '/*.html' ) as $file ) {
			$haystack .= (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading the theme's own files from CLI.
		}
	}

	$missing = array();

	foreach ( (array) glob( get_theme_file_path( 'src/blocks' ) . '/*/block.json' ) as $file ) {
		$meta = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading the theme's own files from CLI.

		if ( ! is_array( $meta ) || ! empty( $meta['parent'] ) ) {
			continue;
		}

		if ( false === strpos( $haystack, '<!-- wp:' . $meta['name'] ) ) {
			$missing[] = $meta['name'];
		}
	}

	if ( $missing ) {
		WP_CLI::error(
			'These blocks render on no page the accessibility suite visits: ' . implode( ', ', $missing )
			. '. Add them to a seeded page in tests/a11y/seed.php.'
		);
	}
}
