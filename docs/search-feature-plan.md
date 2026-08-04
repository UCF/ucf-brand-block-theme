# Search Feature Implementation Plan

> **Status: all three phases shipped.** Phase 3 was built from Option A below, but not the
> way this plan first described it. Two things changed once the code was written:
>
> 1. **No post meta and no `save_post` indexing.** Sections are resolved at render time from
>    `post_content` (`includes/search.php`). Nothing is stored, so nothing can go stale and
>    no reindex is needed when a page is edited. The "Risk: performance when parsing content
>    on every save" below stopped applying.
> 2. **Relevanssi Premium is not required, and would not have helped.** Relevanssi's index
>    unit is the post in both editions — neither returns a fragment of a page. Relevanssi
>    (free, already installed) ranks the pages; the theme picks the H2 within each one.
>    Premium is worth buying for PDF/attachment indexing, which is a separate feature.
>
> The anchor-drift risk this plan flagged was real and was fixed first: ids now come from
> `includes/headings.php` during `render_block` instead of from `brand-nav.js` in the
> browser, so there is one implementation rather than two that must agree. See CLAUDE.md.

## Goals

- Add a search input above the sidebar nav menu.
- Input includes a magnifying glass icon and placeholder text: Search the guide...
- Submitting search sends users to a dedicated search results page.
- Search results page keeps the same drawer/sidebar shell as section pages.
- Results list matching pages.
- Preferred enhancement: subsection matching so users can jump to the matching H2 anchor.

## Recommended Approach

### Recommendation 1 (MVP, no plugin)

Use native WordPress search for pages only, then style and template it to match the guide.

Why this is good:
- Lowest complexity.
- No external dependency.
- Fast to ship and easy to maintain in this theme.

Tradeoff:
- Native search matches page content/title, but does not reliably return direct subsection anchor links.

### Recommendation 2 (Enhanced, plugin-assisted)

Use Relevanssi (free or premium) for stronger relevance and indexing control, plus small theme glue to emit subsection links.

Why this is good:
- Better ranking and partial matching than native search.
- Can index heading data and support H2-level hits.
- Lower custom SQL/indexing work than building a full custom search engine.

Tradeoff:
- Adds plugin dependency and configuration.

## Architecture Notes For This Theme

- Sidebar/nav is rendered by a dynamic block in functions.php via ucf_brand_render_section_nav().
- Sidebar composition lives in parts/brand-sidebar.html.
- Content shell with sticky drawer is in templates/page.html.
- There is no templates/search.html yet, so search currently falls back to templates/index.html.

## Execution Plan

## Phase 1: Add Search Box Above Nav

1. Insert a Search block above the section-nav block in parts/brand-sidebar.html.
2. Configure it to use icon button style and placeholder text Search the guide...
3. Add a specific class for styling hooks, for example brand-sidebar__search.
4. Ensure form method/action use standard WordPress search query behavior.

Implementation detail:
- Core Search block supports icon button mode and placeholder text; this avoids custom form markup.

Files:
- parts/brand-sidebar.html
- src/scss/_drawer.scss
- assets/css/main.css (after build)

Acceptance criteria:
- Search input appears above the menu list on desktop and mobile drawer.
- Magnifying glass icon is visible.
- Placeholder reads exactly Search the guide...

## Phase 2: Create Dedicated Search Results Template In Brand Shell

1. Create templates/search.html.
2. Reuse the same shell structure from templates/page.html:
   - Header
   - mobile bar and scrim
   - main.brand-shell with sidebar and brand-content
   - footer
3. In brand-content, add:
   - Query title block for search term
   - Query block inheriting current search query
   - Restrict post type to page
   - Post template rows with linked title and excerpt
   - Pagination and no-results state

Files:
- templates/search.html

Acceptance criteria:
- Searching from sidebar lands on a search results page using the same left drawer.
- Results show matching pages only.
- Sidebar nav behavior remains intact.

## Phase 3: Subsection (H2) Matching Strategy

### Option A: Relevanssi-based (recommended)

1. Install and activate Relevanssi.
2. Add lightweight indexing glue in theme:
   - On save_post_page, parse post content and collect H2 headings and anchor IDs.
   - Persist as structured post meta, for example _ucf_brand_h2_index.
3. Hook Relevanssi indexing filters so heading text is indexed with page content.
4. In search result rendering, when query matches a heading, append subsection jump links:
   - /page-slug/#heading-id

Files likely touched:
- functions.php (or a small include in includes/)
- templates/search.html (result row markup)

Acceptance criteria:
- Searching a subsection phrase returns the parent page.
- Result row includes at least one matching subsection deep link.
- Deep link opens page and scrolls to target H2.

### Option B: Custom no-plugin heading index (fallback)

1. Store normalized H2 text + anchor pairs in post meta during save.
2. Extend main search query with custom SQL filter to include heading meta matches.
3. Render heading deep links similarly to Option A.

Tradeoff:
- More custom query and indexing logic to maintain.

## Recommended Sequence

1. Ship Phase 1 and Phase 2 first (usable, branded search UX).
2. Validate usage and search quality.
3. Add Phase 3 with Relevanssi if subsection jumps are required by users.

## Testing Plan

- UI placement:
  - Sidebar search renders above nav in desktop and mobile drawer.
- Functional:
  - Query submission uses ?s=term.
  - Search template displays results and pagination.
  - Results are limited to pages.
- Regression:
  - Existing section nav and H2 injected subnav still work on normal pages.
  - Mobile drawer open/close still works.
- Enhanced path:
  - H2 phrase query returns deep link(s).
  - Duplicate/similar headings produce stable unique IDs.

## Risks And Mitigations

- Risk: mismatch between runtime H2 IDs and indexed anchor IDs.
  - Mitigation: share one slug/id generation utility between indexing and front-end JS behavior.
- Risk: performance when parsing content on every save.
  - Mitigation: parse only page post type and only on publish/update.
- Risk: plugin lock-in.
  - Mitigation: keep heading extraction and result-link rendering in theme code with thin plugin-specific adapters.

## Effort Estimate

- Phase 1: 0.5 day
- Phase 2: 0.5 to 1 day
- Phase 3 (Relevanssi path): 1 to 2 days

Total:
- MVP (Phase 1 + Phase 2): about 1 day
- Enhanced subsection search: about 2 to 3 days total
