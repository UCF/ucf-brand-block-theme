# Testing implementation plan

Working document for building out the theme's test coverage. Two phases: **unit tests**
(phase 1, spine built) then **accessibility** (phase 2, not started).

`tests/README.md` documents the suites that already exist and how to run them — read that
first. This file is the plan and the handoff: what is done, what is next, and the decisions
and dead ends already worked through so none of it gets re-litigated.

Fold the finished parts into `tests/README.md` and delete this file once phase 2 lands.

**Contents**

-   [Where things stand](#where-things-stand)
-   [Decisions already made](#decisions-already-made)
-   [Gotchas already paid for](#gotchas-already-paid-for)
-   [Remaining work, in order](#remaining-work-in-order)
-   [Housekeeping, unrelated to testing](#housekeeping-unrelated-to-testing)
-   [Open questions](#open-questions)

## Where things stand

Branch `test`, **uncommitted**, on top of `1f87cf7` (the merged header-bar PR).

```bash
npm test          # both suites
npm run test:php  # PHPUnit         → OK (95 tests, 155 assertions)   ~0.1s
npm run test:js   # Jest            → 20 passed, 20 total             ~3s
```

Both are green, `phpcs` is clean across all 31 PHP files, and `npm run lint:js tests/js`
is clean. `npm run build` output is **byte-identical** with the test tooling installed —
re-verify that if you touch the Babel or Jest config.

New files:

```
phpunit.xml.dist
jest.config.js
tests/README.md
tests/php/{bootstrap,TestCase}.php
tests/php/stubs/class-wp-{post,block}.php
tests/php/{HeadingSlug,PostSections,SectionNumber,SearchTerms,SearchHighlight,SearchRanking,SiteMark,SectionNav}Test.php
tests/js/{blocks.test.js,setup-globals.js}
tests/js/helpers/register-blocks.js
tests/js/__snapshots__/blocks.test.js.snap
```

Modified: `.gitignore`, `composer.json`, `package.json`, `phpcs.xml.dist` (+ both lockfiles).

**What is covered.** Heading slugs and section extraction, section numbering and the hero
binding, search term parsing / matching / highlighting / snippets / ranking, the `site-mark`
and `section-nav` renderers, and round-trip validity plus committed snapshots for all seven
custom blocks.

**What is not.** Anything needing a real WordPress: `ucf_brand_add_heading_anchor()` (wants
`WP_HTML_Tag_Processor` and `render_block`), `ucf_brand_limit_main_search_to_pages()` (wants
`WP_Query`), and `ucf_brand_render_search_subsections()` end to end. Also nothing in
`src/js/` outside the blocks — `brand-nav.js`, `badge-format.js` and `src/js/editor/*` have
no coverage yet.

## Decisions already made

Recorded so they are not re-argued. Each was chosen for a reason that still holds.

**PHP units run without WordPress.** PHPUnit + Brain Monkey, no database, no Docker, ~0.1s.
A suite that needs a container is a suite nobody runs before committing. The WordPress
functions the covered code calls are stubbed in `TestCase::stubWordPress()`, and the stubs
reproduce core's real behavior where it matters — notably `esc_html()` does **not**
double-encode, matching `_wp_specialchars()`'s `$double_encode = false` default.

**The boundary is "does it need real WordPress".** If a function needs a meta query, the
HTML API, or the hook pipeline, it goes to the integration tier rather than getting mocked
into the fast suite. Mocking those would test the mock.

**Blocks are tested through `serialize()` → `parse()` → `isValid`.** That is the comparison
the editor itself makes, and `docs/architecture.md` is explicit that a front-end render is
not a sufficient check because invalid blocks still render.

**Snapshots sit alongside the round-trips, and both are needed.** They catch different
things. Renaming a class inside `save()` is self-consistent, so the round-trip still passes
— but it silently invalidates every page already saved with the old markup. The snapshot
catches that; the round-trip does not. This was verified by mutation, not assumed.

**Babel is scoped to Jest, not added as a root `babel.config.js`.** A root config would also
be read by webpack, changing shipped output for the sake of the tests. It is passed inline
in `jest.config.js` instead.

**`@wordpress/*` are devDependencies only.** The build externalizes those imports to the
`wp.*` globals WordPress already ships, so none of it reaches `build/`.

**phpcs exclusions are scoped, not blanket.** Three narrow ones in `phpcs.xml.dist`, each
with its reason inline: PSR-4 file naming in `tests/php/` (incompatible with WordPress's
`class-*.php` convention and required by the autoloader), the `WP_Post`/`WP_Block` stub class
names (they must match core's exactly — that is the point), and `strip_tags()` in
`TestCase.php` (the stub reproduces core's implementation, which calls it).

## Gotchas already paid for

The expensive knowledge. Do not rediscover these.

**Block registration silently no-ops under Jest.** Blocks call
`registerBlockType( metadata.name, { edit, save } )` — the *name*, not the metadata object.
On a real site the title and attributes are already present because PHP's
`register_block_type()` read `block.json` and shipped it to the client. Jest has no PHP, so
registration warns, returns undefined, `serialize()` returns `""`, and **every round-trip
test passes against an empty string.** The first version of this suite was green and
meaningless.

The fix is `unstable__bootstrapServerSideBlockDefinitions()` in
`tests/js/helpers/register-blocks.js` — the same API core uses to hand the client its
server-side definitions. The "registers every block that ships in `src/blocks/`" test exists
to catch a regression here; it is not decoration.

**Mutation-test anything security-shaped before trusting it.** The first XSS test for
`ucf_brand_highlight_terms()` put its payload *after* the match, so it exercised the
trailing-text escape and kept passing with `esc_html()` deleted from inside the `<mark>`.
The payload has to sit inside the matched span. Both suites have been mutation-checked once;
do the same for new assertions that claim to prove escaping.

**Jest's jsdom environment resolves ESM by default.** `customExportConditions:
['node','require','default']` is what points `@wordpress/*` at their `.cjs` builds. Without
it nearly every package fails on its first `import`.

**A few packages have no CJS build at all** — `uuid` 14, `marked`, `@wordpress/theme`,
`@wordpress/interactivity` — and go through Babel via `transformIgnorePatterns`. **If that
list grows on every dependency bump, stop resolving the editor through npm.** The
alternative is proven: loading WordPress's own `wp-includes/js/dist/*` bundles in a browser
against a live instance validates blocks against the exact WP the site runs, which is higher
fidelity than npm packages that drift from it. That is the escape hatch if this gets
annoying.

**Naming `setupFiles` replaces the preset's copy** rather than adding to it, so
`@wordpress/jest-preset-default/scripts/setup-globals.js` has to be listed explicitly
alongside ours.

**Patterns interpolate PHP inside their block markup** — `<?php esc_html_e( 'Do', … ); ?>`
sits between the block delimiters in `patterns/units/detail-card.php`. Any tooling that
wants to parse pattern markup has to render the PHP first. This is the main obstacle to
item 1 below.

## Remaining work, in order

### ~~1. Markup validity sweep over `parts/` and `patterns/`~~ — done

`tests/php/render-patterns.php` renders the seven patterns; `tests/js/markup-validity.test.js`
sweeps those plus the four template parts. Nine tests. Mutation-verified against both a
pattern and a template part.

One finding from building it is worth carrying forward, because it invalidates the plan as
originally written above:

**`isValid` is a near-useless assertion, and the plan called for exactly that.** `parse()`
does not merely validate — on a mismatch it searches the block type's `deprecated` array for
an older `save()` that matches, migrates the block, and reports `isValid: true`. Reproducing
the exact `section-index.php` bug (a `textColor` attribute with no matching class) leaves
every block valid; core recovers it and logs "Updated Block: core/paragraph". **A sweep built
to the original plan would have shipped that bug a second time.**

The suite uses `isValidBlockContent( name, attributes, originalContent )` instead, which
compares against today's `save()` with no deprecation fallback. `core/html` is exempt —
verbatim raw markup, no normalization contract, and real WordPress parses the theme's two
`core/html` chrome blocks as valid.

Also worth knowing: `@wordpress/jest-console` must be defeated with **plain functions, not
`jest.spyOn`**, or Gutenberg's validation logging buries the real failure. See
`tests/README.md`.

### 2. PHP integration tier

For the functions deliberately excluded from the fast suite. Use `@wordpress/env` (already
the news theme's approach) plus `wp-phpunit`.

Targets: `ucf_brand_add_heading_anchor()` through the real `render_block` filter, including
the author-set-id-wins path and the per-post registry reset; `ucf_brand_get_ordered_sections()`
against real posts and meta; `ucf_brand_limit_main_search_to_pages()` against a real
`WP_Query`; `ucf_brand_render_search_subsections()` end to end on a search request.

Keep it a **separate** PHPUnit suite (`--testsuite integration`) so `npm run test:php` stays
sub-second and Docker stays optional for the fast loop.

### 3. CI workflow

`.github/workflows/ci.yml` — the repo currently has only `trigger-packagist-update.yml`.
Model on the news theme's. Jobs: `composer install` → `phpcs` → `phpunit`; `npm ci` →
`npm run build` → `npm run test:js`. Then phase 2's a11y job.

**Pin PHP to 8.2** to match the container. Local development is on 8.5.8, and a suite that
only ever runs on 8.5 proves less than it looks like it does.

Optional and worth considering: fail CI if `build/` is dirty after `npm run build`, since
`build/` is committed and drifting from `src/` is a known hazard.

### 4. Phase 2 — accessibility

Port the news theme's suite
(`github.com/UCF/ucf-news-block-theme/tree/rc-v1.0.0-alpha.3/tests`) — Playwright +
`@axe-core/playwright`, `.wp-env.json`, `tests/seed.sh`, `playwright.config.js` with three
viewport projects, failing on violations and annotating `incomplete` without failing. That
policy is deliberate there and should carry over: pa11y conflates incomplete with errors and
produces a false positive on every hero image.

Then extend past per-route coverage, which is the part this theme actually needs:

-   **Routes** — home, a numbered section page, search results, 404.
-   **Per pattern.** Seed one page per entry in `WP_Block_Patterns_Registry` (the news
    theme's `seed.sh` already pulls one pattern's content this way — generalize the loop) so
    every pattern is axed in isolation and a newly added pattern is covered automatically
    rather than by remembering to add it.
-   **Per variant.** A generated page exercising every `register_block_style()` in
    `includes/block-styles.php` and every custom block. This is the one that matters most
    here: the compositions (`is-style-light` / `-dark` / `-paper` / `-bold-gold`) recolor
    their contents, so a variant can fail contrast where the default passes. Testing only the
    default composition would miss exactly the failure the system makes possible.

Known live-instance requirements from the header work: the theme runs on a **multisite
subsite**, and wp-env will need `tests/seed.sh` adapted — the news theme's script assumes
single-site with `postname` permalinks.

## Housekeeping, unrelated to testing

Surfaced by `composer audit` while installing PHPUnit. Both are **pre-existing dev
dependencies**, not introduced by this work, and neither ships to production — but both are
real:

| Package | Installed | Advisory | Fixed in |
| --- | --- | --- | --- |
| `squizlabs/php_codesniffer` | 3.13.5 | CVE-2026-67434, OS command injection | `>=3.13.6` |
| `wp-coding-standards/wpcs` | `^3.1` | CVE-2026-45293, arbitrary code execution (high) | `>=3.4.1` |

Bumping the `wpcs` constraint to `^3.4.1` should pull a fixed phpcs with it. Worth a separate
commit so it is reviewable on its own.

`npm audit` also reports 39 advisories across the JS dev tree; most predate this work
(`@wordpress/scripts` carries a deep dependency graph). Worth a look, not a blocker.

## Open questions

1. **Does the test work belong on its own branch?** It is currently uncommitted on `test`,
   which sits on the merged header-bar PR. A dedicated branch would make it reviewable
   separately from anything else that lands on `test`.
2. **Should `CLAUDE.md` gain a pointer to `tests/README.md`?** Nothing in the contributor
   docs currently mentions that tests exist or that `npm test` runs them, so neither a new
   contributor nor a future agent session would find them.
3. **How strict should CI be initially?** Failing the build on a11y violations from day one
   is the right end state, but the first full run across every pattern and variant will
   almost certainly surface existing issues. Landing it non-blocking first, fixing the
   backlog, then flipping it to required may be the smoother path — your call.
