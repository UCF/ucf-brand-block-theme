<?php
/**
 * Section numbering.
 *
 * Each brand page carries a `ucf_brand_number` — set by the editor in the Brand panel
 * (see src/js/editor/brand-order-panel.js). That single value both orders the page in the
 * drawer and prints as its decimal label. One PHP source of truth,
 * `ucf_brand_get_ordered_sections()`, feeds two consumers: the drawer menu (the
 * `ucf-brand/section-nav` dynamic block, in includes/section-nav.php) and each page's
 * on-page label (the `ucf-brand/section-number` block binding, below), so the two can
 * never disagree.
 *
 * `ucf_brand_format_number()` has a third caller worth knowing about: includes/enqueue.php
 * prints the same number into a CSS variable for the H2 subsection badges, once for the
 * front end and once for the editor canvas.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Format a section number as its decimal label.
 *
 * SPEC: unpadded. The guide numbers sections "1", "2", "3" and subsections "1.1", "1.2" —
 * the zero-padded form this used to return printed "01" and "01.01" instead.
 *
 * @param int $number Raw section number.
 * @return string Decimal label, or '' when unset.
 */
function ucf_brand_format_number( $number ) {
	$number = (int) $number;

	if ( $number < 1 ) {
		return '';
	}

	return (string) $number;
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
	/*
	 * The meta_key/meta_query pair is what makes this list the ordered one, so the
	 * slow-query sniffs below are acknowledged rather than avoided: the set is the site's
	 * top-level published pages — a few dozen at most, not a content archive — and it is
	 * read once per request to build the drawer.
	 */
	$pages = get_posts(
		array(
			'post_type'        => 'page',
			'post_parent'      => 0,
			'post_status'      => 'publish',
			'numberposts'      => -1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Bounded set; see above.
			'meta_key'         => 'ucf_brand_number',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded set; see above.
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
			if ( $a['number'] !== $b['number'] ) {
				return $a['number'] <=> $b['number'];
			}

			return strcasecmp( $a['title'], $b['title'] );
		}
	);

	return $sections;
}

/**
 * Register the block binding that prints the current page's decimal label.
 *
 * This is the front-end half only. A source registered in PHP reaches the editor as a
 * label with no `getValues`, so src/js/editor/section-number-binding.js registers a
 * matching client half — read
 * the note there before changing the format below.
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
		/* translators: 1: guide name, e.g. "Brand Guidelines". 2: section number, e.g. "5". */
		__( '%1$s · Section %2$s', 'ucf-brand-block-theme' ),
		$source_args['label'],
		$number
	);
}
