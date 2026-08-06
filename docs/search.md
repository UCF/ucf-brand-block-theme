# Search

Search in this theme answers a question a normal WordPress search cannot: _which section of
which page_ matches. A brand guide is a small number of long pages, so "Foundation" as a
result is nearly useless — the reader wants "Foundation → Clear Space".

Implemented in `includes/search.php`. Anchors come from `includes/headings.php`, which is
also what puts them on the page, so the two cannot disagree.

## How a result is built

1. **Relevanssi decides which pages match**, if it is installed. It ranks whole posts —
   that is true of the free and premium editions alike, and no setting turns a page into a
   bag of sections.
2. **The theme decides which H2 within each page the reader wanted.** The
   `ucf-brand/search-subsections` block renders once per result row, scores that page's
   sections against the query, and emits up to `UCF_BRAND_MAX_SUBSECTIONS` (3)
   `/page/#heading` links with highlighted snippets beneath the result.

Everything is resolved at render time from `post_content`. Nothing is stored, so nothing
can go stale and no reindex is needed when a page is edited.

**Relevanssi is optional.** With it, pages rank better. Without it, core's search picks the
pages and the subsection logic behaves identically.

### Why not a shadow post per H2

Indexing each section as its own document would rank better, but it duplicates every page
into the database and needs a sync path that can drift. The current design keeps one copy
of the content and holds no state.

## Scoring

`ucf_brand_find_matching_sections()` orders a page's sections by:

1. **How many distinct query terms the section mentions.** A section touching every word
   the reader typed beats one that repeats a single word many times.
2. **Then raw frequency,** with a heading hit worth 5× a body hit — the heading is what the
   reader sees in the result.

Terms come from `ucf_brand_search_terms()`: a double-quoted run is kept whole so
`"clear space"` matches as a phrase, and single characters are dropped as too noisy.

## Details worth knowing before changing it

-   **One place owns what counts as a match.** `ucf_brand_term_pattern()` builds the regex
    used by scoring, snippet windowing and highlighting alike, so the three can never
    disagree. It is word-bounded on purpose: unbounded, "art" would match "start" and rank
    an unrelated section first.
-   **Longest term first.** Alternation is first-match-wins, so a quoted phrase must be able
    to claim the run before the individual words inside it do. Otherwise `"clear space"`
    highlights as two adjacent marks rather than one.
-   **Highlighting escapes each run separately and only then joins.** Escaping the whole
    string first would shift every byte offset _and_ let a search for "amp" start matching
    inside `&amp;`.
-   **The snippet sits outside the anchor.** Inside, it would join the link's accessible
    name and every result would announce as a paragraph of prose.
-   **`pre_get_posts` constrains the main front-end query to pages,** so a hand-typed or
    shared `?s=…` link resolves the same way the sidebar Search block does.

## Where the pieces are

| Piece                             | Location                          |
| --------------------------------- | --------------------------------- |
| All search logic                  | `includes/search.php`             |
| Anchor ids and section extraction | `includes/headings.php`           |
| Results template                  | `templates/search.html`           |
| Editor stand-in for the block     | `src/js/editor/dynamic-blocks.js` |
| Styling                           | `src/scss/_search.scss`           |
