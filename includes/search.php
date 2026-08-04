<?php
/**
 * Search behavior and subsection deep links.
 *
 * Relevanssi indexes and ranks whole posts — that is true of the free and premium
 * editions alike, and no setting turns a page into a bag of sections. So the subsection
 * result is built here instead, at render time: Relevanssi decides *which pages* match,
 * and this file works out *which H2 within each page* the reader actually wanted, then
 * emits `/page/#heading` links under the result.
 *
 * The alternative — a shadow post per H2, indexed as its own document — would rank better
 * but duplicates every page into the database and needs a sync path that can drift. This
 * keeps one copy of the content, holds no state, and needs no reindex when a page is
 * edited. It works identically with Relevanssi active or not.
 *
 * Anchors come from includes/headings.php, which is also what puts them on the page, so
 * the two cannot disagree.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Most subsection links to show beneath a single result.
 */
const UCF_BRAND_MAX_SUBSECTIONS = 3;

/**
 * Keep guide search results focused on section pages.
 *
 * The sidebar Search block already posts `post_type=page`, but a hand-typed or shared
 * `?s=...` link should resolve the same way. Constrain only the front-end main query,
 * leaving admin and secondary queries untouched — Relevanssi also filters the main query,
 * and it reads the post type set here.
 *
 * @param WP_Query $query The query instance (passed by reference).
 * @return void
 */
function ucf_brand_limit_main_search_to_pages( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	$query->set( 'post_type', 'page' );
}
add_action( 'pre_get_posts', 'ucf_brand_limit_main_search_to_pages' );

/**
 * Split the active search query into terms.
 *
 * A double-quoted run is kept whole so "clear space" is matched as a phrase rather than
 * as two common words. Single characters are dropped as too noisy to rank on.
 *
 * @param string $query Raw search query. Defaults to the current one.
 * @return string[] Search terms.
 */
function ucf_brand_search_terms( $query = null ) {
	if ( null === $query ) {
		$query = get_search_query( false );
	}

	$query = trim( (string) $query );

	if ( '' === $query ) {
		return array();
	}

	$terms = array();

	// Pull quoted phrases out first, then treat whatever is left as single words.
	if ( preg_match_all( '/"([^"]+)"/', $query, $matches ) ) {
		foreach ( $matches[1] as $phrase ) {
			$phrase = trim( $phrase );

			if ( '' !== $phrase ) {
				$terms[] = $phrase;
			}
		}

		$query = preg_replace( '/"[^"]+"/', ' ', $query );
	}

	// mb_strlen(), not a `wp_`-prefixed wrapper — no such wrapper exists. Core polyfills
	// this one in wp-includes/compat.php when the mbstring extension is missing, so it is
	// always safe to call here.
	foreach ( preg_split( '/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY ) as $word ) {
		if ( mb_strlen( $word ) > 1 ) {
			$terms[] = $word;
		}
	}

	return array_values( array_unique( $terms ) );
}

/**
 * Build the match pattern for a set of terms.
 *
 * One place owns the matching rule, so scoring, snippet windowing and highlighting can
 * never disagree about what counts as a hit. Word-bounded on purpose: an unbounded match
 * would score "art" against "start" and rank an unrelated section first.
 *
 * @param string[] $terms Terms or phrases.
 * @return string Regex, or '' when there is nothing to match.
 */
function ucf_brand_term_pattern( $terms ) {
	$parts = array();

	foreach ( (array) $terms as $term ) {
		if ( '' !== trim( (string) $term ) ) {
			$parts[] = preg_quote( $term, '/' );
		}
	}

	if ( empty( $parts ) ) {
		return '';
	}

	// Longest first: alternation is first-match-wins, so this lets a quoted phrase claim
	// the run before the individual words inside it do. Without it, "clear space" would
	// highlight as two adjacent marks rather than one.
	usort(
		$parts,
		function ( $a, $b ) {
			return strlen( $b ) <=> strlen( $a );
		}
	);

	return '/\b(' . implode( '|', $parts ) . ')/iu';
}

/**
 * Count whole-word occurrences of a term in a string.
 *
 * @param string $term     Term or phrase.
 * @param string $haystack Text to search.
 * @return int Occurrences.
 */
function ucf_brand_count_term( $term, $haystack ) {
	$pattern = ucf_brand_term_pattern( array( $term ) );

	if ( '' === $pattern || '' === $haystack ) {
		return 0;
	}

	$found = preg_match_all( $pattern, $haystack, $ignored );

	// preg_match_all() returns false on a pattern the Unicode engine rejects — treat
	// that as "no match" rather than letting false coerce to 0 silently elsewhere.
	return $found ? (int) $found : 0;
}

/**
 * Escape text and wrap the matched terms in `<mark>`.
 *
 * Every run between matches is escaped on its own and only then re-joined, so `<mark>` is
 * the single piece of markup in the result and nothing in the page content can introduce
 * another. Escaping the whole string first and highlighting afterwards would be wrong in
 * both directions: the entities it produces shift every offset, and a search for "amp"
 * would start matching inside `&amp;`.
 *
 * @param string   $text  Plain text.
 * @param string[] $terms Terms to mark.
 * @return string Escaped HTML.
 */
function ucf_brand_highlight_terms( $text, $terms ) {
	$pattern = ucf_brand_term_pattern( $terms );

	if ( '' === $pattern || ! preg_match_all( $pattern, $text, $matches, PREG_OFFSET_CAPTURE ) ) {
		return esc_html( $text );
	}

	// PREG_OFFSET_CAPTURE reports byte offsets and substr() counts bytes, so the two agree
	// even under /u.
	//
	// Runs separated by nothing but whitespace are merged into one. An unquoted "clear
	// space" matches as two terms, and marking them separately renders as two chips with a
	// seam down the middle; the reader typed one phrase and should see one highlight.
	$ranges = array();

	foreach ( $matches[0] as $match ) {
		$start = $match[1];
		$end   = $start + strlen( $match[0] );
		$last  = count( $ranges ) - 1;

		if ( $last >= 0 && '' === trim( substr( $text, $ranges[ $last ][1], $start - $ranges[ $last ][1] ) ) ) {
			$ranges[ $last ][1] = $end;
			continue;
		}

		$ranges[] = array( $start, $end );
	}

	$out    = '';
	$cursor = 0;

	foreach ( $ranges as $range ) {
		list( $start, $end ) = $range;

		$out   .= esc_html( substr( $text, $cursor, $start - $cursor ) );
		$out   .= '<mark>' . esc_html( substr( $text, $start, $end - $start ) ) . '</mark>';
		$cursor = $end;
	}

	return $out . esc_html( substr( $text, $cursor ) );
}

/**
 * Pull a highlighted excerpt from a section, centered on the first match.
 *
 * When the query matched only the heading there is nothing to center on, so the window
 * opens at the start of the section — which still tells the reader what they are jumping
 * into.
 *
 * @param string   $text   Section body text, tags already stripped.
 * @param string[] $terms  Search terms.
 * @param int      $length Approximate snippet length in bytes.
 * @return string Escaped HTML with `<mark>` around matches, or '' when there is no text.
 */
function ucf_brand_section_snippet( $text, $terms, $length = 180 ) {
	$text = trim( preg_replace( '/\s+/', ' ', (string) $text ) );

	if ( '' === $text ) {
		return '';
	}

	$pattern = ucf_brand_term_pattern( $terms );
	$start   = 0;

	// Open a third of a window ahead of the hit so the match reads in context rather than
	// sitting flush against the left edge.
	if ( '' !== $pattern && preg_match( $pattern, $text, $match, PREG_OFFSET_CAPTURE ) ) {
		$start = max( 0, $match[0][1] - (int) floor( $length / 3 ) );
	}

	// Snap both edges to a space so no word is cut in half. Spaces are ASCII, so cutting
	// on one can never split a multi-byte character even though these are byte offsets.
	if ( $start > 0 ) {
		$space = strpos( $text, ' ', $start );
		$start = ( false === $space ) ? $start : $space + 1;
	}

	$snippet = substr( $text, $start );
	$clipped = false;

	if ( strlen( $snippet ) > $length ) {
		$cut     = strrpos( substr( $snippet, 0, $length ), ' ' );
		$snippet = substr( $snippet, 0, ( false === $cut ) ? $length : $cut );
		$clipped = true;
	}

	$snippet = ucf_brand_highlight_terms( trim( $snippet ), $terms );

	// Decoration around already-escaped markup.
	if ( $start > 0 ) {
		$snippet = '…' . $snippet;
	}

	if ( $clipped ) {
		$snippet .= '…';
	}

	return $snippet;
}

/**
 * Rank a page's H2 sections against the search terms.
 *
 * Sections are ordered by how many distinct terms they mention first, and only then by
 * raw frequency — a section touching every word the reader typed beats one that repeats a
 * single word. A term in the heading counts for more than the same term in body copy,
 * since the heading is what the reader will see in the result.
 *
 * @param WP_Post  $post  Page to inspect.
 * @param string[] $terms Search terms.
 * @param int      $limit Maximum sections to return.
 * @return array[] Matching sections, best first, each with `id` and `title`.
 */
function ucf_brand_find_matching_sections( $post, $terms, $limit = UCF_BRAND_MAX_SUBSECTIONS ) {
	if ( empty( $terms ) ) {
		return array();
	}

	$scored = array();

	foreach ( ucf_brand_get_post_sections( $post ) as $section ) {
		$score   = 0;
		$matched = 0;

		foreach ( $terms as $term ) {
			$in_title = ucf_brand_count_term( $term, $section['title'] );
			$in_text  = ucf_brand_count_term( $term, $section['text'] );

			if ( $in_title || $in_text ) {
				++$matched;
			}

			$score += ( $in_title * 5 ) + $in_text;
		}

		if ( $score > 0 ) {
			$section['matched'] = $matched;
			$section['score']   = $score;
			$scored[]           = $section;
		}
	}

	usort(
		$scored,
		function ( $a, $b ) {
			if ( $a['matched'] !== $b['matched'] ) {
				return $b['matched'] <=> $a['matched'];
			}

			return $b['score'] <=> $a['score'];
		}
	);

	return array_slice( $scored, 0, max( 1, (int) $limit ) );
}

/**
 * Register the search result's subsection block.
 *
 * Theme glue rendered from live page data, so it follows ucf-brand/section-nav and lives
 * here rather than in blocks/ — see CLAUDE.md on why blocks/ stays static-only.
 *
 * @return void
 */
function ucf_brand_register_search_subsections() {
	register_block_type(
		'ucf-brand/search-subsections',
		array(
			'api_version'     => 3,
			'render_callback' => 'ucf_brand_render_search_subsections',
		)
	);
}
add_action( 'init', 'ucf_brand_register_search_subsections' );

/**
 * Render the matching subsections for the current result.
 *
 * @return string Markup, or '' when nothing in the page matched below the title.
 */
function ucf_brand_render_search_subsections() {
	if ( ! is_search() ) {
		return '';
	}

	$post = get_post();

	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$terms    = ucf_brand_search_terms();
	$sections = ucf_brand_find_matching_sections( $post, $terms );

	if ( empty( $sections ) ) {
		return '';
	}

	$permalink = get_permalink( $post );
	$items     = '';

	foreach ( $sections as $section ) {
		$snippet = ucf_brand_section_snippet( $section['text'], $terms );

		// The snippet sits outside the anchor deliberately. Inside, it would join the
		// link's accessible name and every result would announce as a paragraph of prose.
		// Both values arrive escaped from ucf_brand_highlight_terms().
		$items .= sprintf(
			'<li class="brand-search__item"><a class="brand-search__link" href="%1$s"><span class="brand-search__marker" aria-hidden="true">#</span><span class="brand-search__text">%2$s</span></a>%3$s</li>',
			esc_url( $permalink . '#' . $section['id'] ),
			ucf_brand_highlight_terms( $section['title'], $terms ),
			'' === $snippet ? '' : '<p class="brand-search__snippet">' . $snippet . '</p>'
		);
	}

	return sprintf(
		'<nav class="brand-search__sections" aria-label="%1$s"><ul class="brand-search__list">%2$s</ul></nav>',
		/* translators: %s: page title the matching sections belong to. */
		esc_attr( sprintf( __( 'Matching sections in %s', 'ucf-brand-block-theme' ), get_the_title( $post ) ) ),
		$items
	);
}
