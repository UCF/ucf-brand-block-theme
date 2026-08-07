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
npm test                   # PHPUnit 95 + Jest 20, ~3s, no Docker
npm run env:start          # Docker, once
npm run test:integration   # PHPUnit + real WordPress, 26 tests
npm run test:all           # everything
```

All three suites are green, `phpcs` is clean across the whole theme, `tests/js` adds no
eslint debt, and `npm run build` output is byte-identical with the test tooling installed.

**[`tests/README.md`](../tests/README.md) is the reference for what each suite covers and how
it is wired.** It is deliberately not restated here — two copies of that is exactly the drift
this theme keeps getting bitten by. This file holds only what a reference doc should not: the
plan, the reasoning behind choices already made, and the traps already paid for.

Still uncovered: everything in `src/js/` outside the blocks — `brand-nav.js`,
`badge-format.js` and `src/js/editor/*`.

## Decisions already made

Recorded so they are not re-argued. Each was chosen for a reason that still holds.

**Three tiers, split by honesty rather than speed.** If a function needs real WordPress it
goes to the integration tier instead of being mocked into the fast one, because mocking the
thing under test tests the mock. The fast tiers stay Docker-free so they actually get run.

**PHPUnit is pinned to `^9.6` project-wide.** Not a preference — WordPress's test suite calls
an API PHPUnit removed in 10. See the note under item 2 below for how that surfaced.

**Blocks get both a round-trip and a snapshot, and both are needed.** They catch different
things: renaming a class inside `save()` is self-consistent, so the round-trip still passes
while every already-saved page is silently invalidated. The snapshot catches that. Verified by
mutation, not assumed.

**Markup is checked with `isValidBlockContent()`, never `isValid`.** See item 1 below.

**Babel is scoped to Jest, not added as a root `babel.config.js`.** A root config would also
be read by webpack, changing shipped output for the sake of the tests.

**`@wordpress/*` are devDependencies only.** The build externalizes those imports to the
`wp.*` globals WordPress already ships, so none of it reaches `build/`.

**WordPress stubs live in one file, shared.** `tests/php/stubs/wp-escaping.php` is used by both
the unit suite and the pattern renderer. Two copies would mean the markup sweep validates
markup escaped differently from what the site emits.

**phpcs exclusions are scoped and each carries its reason inline** — PSR-4 file naming in the
test directories, the `WP_Post`/`WP_Block` stub class names, `strip_tags()` in the stub that
reproduces core's implementation. None of them are blanket.

## Gotchas already paid for

The expensive knowledge. Do not rediscover these.

**Block registration silently no-ops under Jest.** Blocks call
`registerBlockType( metadata.name, { edit, save } )` — the _name_, not the metadata object.
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
`ucf_brand_highlight_terms()` put its payload _after_ the match, so it exercised the
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

### ~~2. PHP integration tier~~ — done

`tests/integration/` on wp-env + wp-phpunit: 26 tests across heading anchors, ordered
sections, and search. Mutation-verified against three separate defects. `npm test` still
never touches Docker; `npm run test:integration` is the tier and `npm run test:all` is both.

Two things came out of building it that were not in the plan:

**PHPUnit had to be downgraded from 11 to 9.6, project-wide.** WordPress's test suite calls
`PHPUnit\Util\Test::parseTestMethodAnnotations()`, which PHPUnit removed in 10 — so WP 7.0.3
caps at 9.x. Composer resolved `phpunit ^11` with `wp-phpunit` happily; the incompatibility
only surfaced at runtime, as 18 identical errors. Picking 11 for the unit suite was a default
chosen before the integration tier's constraint was known. One toolchain at the version WP
core supports is the right answer; 9.6 runs cleanly on PHP 8.5, so it costs nothing. The
attributes (`#[DataProvider]`, `#[CoversFunction]`) were converted to annotations.

**The environment must not be left running.** `npm run test:integration` goes through
`tools/integration-tests.js`, which boots, runs, and always stops — on failure and on Ctrl-C
too, with PHPUnit's exit code preserved rather than the teardown's. A chained npm script gives
none of those reliably. Containers left up hold 8888/8889 and the next project's `wp-env
start` then fails with only "address already in use" to go on, which is exactly how an hour
disappears. Both the success and failure paths are verified.

`.wp-env.json` stays on the default ports; per-machine clashes go in `.wp-env.override.json`,
which is gitignored.

### ~~3. CI workflow~~ — done

Two workflows, split by cost:

-   **`ci.yml` — every commit, every branch.** The two Docker-free suites, plus the version
    check and the build/ drift check. Around two minutes, so the inner loop stays cheap.
-   **`ci-full.yml` — pull requests and main only.** The WordPress integration tier, and where
    the accessibility suite goes in phase 2.

`ci-full.yml` is deliberately not a superset: a `pull_request` event and the `push` for its
branch fire together, so re-running the fast suites there would double every PR's CI time for
no extra signal.

**Linting and formatting never fail a build.** phpcs, eslint, stylelint and prettier run in
the `quality` job, which carries `continue-on-error`. The findings are worth seeing; they are
not the same claim as "this is broken", and a build that goes red for a long comment line in
an unrelated file is a build whose signal gets ignored.

PHP is a matrix (8.1, 8.2) because `style.css` promises 8.1 while production runs 8.2 — the
floor is a promise and the ceiling is what ships.

Two additions not in the original plan, both blocking because neither is style: the **build/
drift check** (rebuild, fail if the committed output changed — catches the "edited src/,
forgot to rebuild" commit, and verified to actually catch it) and `lint:version`, which
despite its name catches a version mismatch that would ship a theme reporting the wrong
version.

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

| Package                     | Installed | Advisory                                        | Fixed in   |
| --------------------------- | --------- | ----------------------------------------------- | ---------- |
| `squizlabs/php_codesniffer` | 3.13.5    | CVE-2026-67434, OS command injection            | `>=3.13.6` |
| `wp-coding-standards/wpcs`  | `^3.1`    | CVE-2026-45293, arbitrary code execution (high) | `>=3.4.1`  |

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
3. **How strict should the a11y job be when it lands?** The same question the lint steps
   already answer with `continue-on-error`: the first full run across every pattern and
   variant will almost certainly surface existing issues. Landing it non-blocking, fixing the
   backlog, then flipping it to required is the pattern now established in `ci.yml`.
