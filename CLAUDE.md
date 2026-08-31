# UCF Brand Block Theme — working notes

**Read [`docs/architecture.md`](docs/architecture.md) before changing anything.** It is the
single source for this theme's conventions and for the reasoning behind them. This file
does not restate those rules — keeping two copies is exactly the drift this theme has been
bitten by before. Read `README.md` first for what the theme is and where each file lives.

## What is in the architecture doc

Skim this index; go read the section before you touch the area it covers.

| Section                                                 | Covers                                                           |
| ------------------------------------------------------- | ---------------------------------------------------------------- |
| The escalation ladder                                   | Tokens → existing classes → core controls → something new        |
| Roles, not tokens                                       | `--brand-*` roles, and which file may set one                    |
| Patterns declare structure and composition, never color | Why a `textColor` attribute in a pattern is a bug                |
| Blocks                                                  | Static-only rule, palette slugs, where dynamic blocks go instead |
| PHP lives in `includes/`                                | One topic per file; `functions.php` is a loader only             |
| JavaScript is one pipeline                              | `src/` → `build/`, generated `.asset.php` dependency lists       |
| Build and content                                       | What `npm run build` does; block CSS belongs in `src/scss/`      |
| Formatting and linting                                  | Which formatter owns which file type, and why stylelint defers   |
| H2s are structural                                      | `includes/headings.php` owns every anchor id                     |
| Gotchas that have already bitten                        | The list not to "clean up"                                       |

Search has its own document: [`docs/search.md`](docs/search.md).

## Tests

`npm test` runs the two fast suites in about three seconds and needs no Docker.
`npm run test:integration` adds the WordPress tier, `npm run test:a11y` the accessibility one;
`npm run test:all` is everything. Read [`tests/README.md`](tests/README.md) before adding to
them — it covers what each suite is for and the non-obvious parts of the setup.

**New code ships with its test.** What you have to write depends on what you added, and most
of it is already automatic:

| You added                   | You write                                                                                                                                               |
| --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| A function in `includes/`   | A case in `tests/php/`, or `tests/integration/` if it needs WordPress — nothing enforces this for you                                                   |
| A block in `src/blocks/`    | An entry in `tests/js/helpers/register-blocks.js`, and — if no pattern or template renders it — a page in `tests/a11y/seed.php`. Both fail until you do |
| A pattern in `patterns/`    | Nothing. The markup sweep and the a11y suite both read the directory — just run them                                                                    |
| A template part in `parts/` | Nothing. Same sweep                                                                                                                                     |
| A `register_block_style()`  | Nothing, unless it is on a block type `tests/a11y/seed.php` has no sample for — then the seed fails until you add one                                   |

-   **A new function in `includes/` needs a unit test.** Put it with the tests for the file
    that owns the topic. If the function genuinely needs WordPress — a meta query,
    `WP_HTML_Tag_Processor`, the `render_block` filter — it goes in `tests/integration/`
    instead, which runs against real WordPress under wp-env (`npm run test:integration`). Do
    **not** mock those into the fast suite. A test that mocks the thing it is testing tests
    the mock.
-   **`npm test` must never require Docker.** It runs the two fast suites only; the
    integration tier is `npm run test:integration`, and `npm run test:all` is both. Keep that
    boundary — a suite that needs a container is one nobody runs before committing.
-   **PHPUnit is pinned to `^9.6`, deliberately.** WordPress's test suite calls a PHPUnit API
    removed in 10, so the project uses one toolchain at the version WP core supports. Write
    `@dataProvider` / `@covers` annotations, not `#[DataProvider]` attributes.
-   **A new block in `src/blocks/` needs registering in the test helper.** Add it to
    `BLOCK_DIRS` and give it a `BLOCK_FIXTURES` entry. You will not forget: the "registers
    every block that ships in `src/blocks/`" test compares the list against the directory and
    fails until they match. Nest the fixture the way the editor does — a block with a `parent`
    in its `block.json` cannot be validated standalone.
-   **New patterns and template parts need no new test.** The markup sweep is data-driven off
    disk, so it picks them up on the next run. Run `npm run test:js` and read the result — a
    failure there is the pattern being wrong, not the suite needing an update.
-   **The accessibility suite reads WordPress's registries, not a checked-in list.** Patterns
    come from `WP_Block_Patterns_Registry` and block styles from `WP_Block_Styles_Registry`, so
    new ones are audited on the next `npm run test:a11y` with nothing to add. It gates merges.
-   **Before filing what the a11y suite reports, read the style's definition in `src/scss/`.**
    Not every violation on a variant page is a theme defect. `is-style-on-dark` supplies the
    treatment only — no background, no base color, both from the block's own controls — so
    auditing it on a bare page produces five failures that belong to the fixture.
    `ucf_brand_a11y_wrap_in_context()` in `tests/a11y/seed.php` is where that gets corrected.
-   **An axe assertion on the wrong page passes.** The suite therefore checks the HTTP status,
    the expected `is-style-*` class and (for tabs) that the runtime enhancement actually ran,
    all _before_ trusting the audit. Keep that habit for anything added: the green version of
    each of those failures is indistinguishable from a clean site.
-   **Prove a new test can fail.** Break the code it covers, watch it go red, then restore.
    This is not ceremony: two tests written in this repo passed against nothing at all — one
    because blocks silently failed to register and `serialize()` returned `""`, and one
    because an XSS payload sat outside the range being escaped. Both looked green and correct.
-   **Never assert on a block's `isValid` to check markup.** `parse()` recovers mismatched
    markup by migrating it through the block type's `deprecated` array and then reports
    `isValid: true`. The exact bug this theme already shipped in the Index pattern passes an
    `isValid` check. Use `isValidBlockContent()`, which compares against the current `save()`
    with no deprecation fallback.
-   **Keep the fast suites fast and dependency-light.** No database and no Docker in
    `tests/php/` or `tests/js/`. A suite that needs a container is one nobody runs before
    committing, which is the same as not having it.

## Working practices

These are about how to make a change here, not about what the theme is.

-   **Never append to `functions.php`.** It is a loader. New behavior goes in the
    `includes/` file that owns its topic, or in a new file added to the array there.
-   **Rebuild and commit `build/` with any change under `src/`.** Both halves:
    `npm run build`. Never hand-edit anything in `build/`.
-   **Never verify markup through the front end.** Invalid blocks still render there, so a
    page that looks right proves nothing. Patterns and template parts are covered
    automatically by the markup sweep — `npm run test:js`. For markup the sweep cannot see,
    such as content authored into a page, ask the editor's store directly:
    ```js
    wp.data.select( 'core/block-editor' ).getBlocks(); // walk innerBlocks
    ```
    Read that result the way the tests do: a block reporting `isValid: true` may still have
    been migrated through a deprecation rather than matched outright. Watch the console for
    "Updated Block" alongside it.
-   **Watch for opcache when testing PHP changes on a running site.** The usual local stack
    caches with `revalidate_freq=2`; a before/after page capture taken faster than that
    compares stale code against stale code and proves nothing.
-   **This codebase documents _why_, not _what_.** Comments here explain reasoning and record
    bugs that already shipped. Match that when adding code, and when moving code keep its
    comment with it — including the file paths it references. Every comment is tagged and
    budgeted — see [Comments](#comments) below.

## Comments

Documenting _why_ is right, and unbudgeted it produced twenty-line essays above four-line
rules — `_drawer.scss` was 24% comments, most of it restating the CSS underneath. The fix is
not to write less reasoning. It is to tag each comment with the kind of reason it gives, and
cap what that kind is worth.

**Every prose comment opens with a tag.** The tag says what kind of reason follows, so a reader skimming for "what will break if I touch this" can tell in one word. (Structural dividers / file-title banners like `// ── … ──` are allowed untagged.)

| Tag         | Use when                                                         |
| ----------- | ---------------------------------------------------------------- |
| `WHY:`      | Someone would reasonably delete or "simplify" this               |
| `FIX:`      | This shipped broken once and the rule is what stops it recurring |
| `UPSTREAM:` | The cause is in code we do not own — WP core, a browser, a lib   |
| `SYNC:`     | Something elsewhere depends on this exact value                  |
| `SPEC:`     | The comp dictates the value; it is not ours to normalize         |
| `A11Y:`     | Removing it breaks assistive tech or fails the axe suite         |
| `SAFETY:`   | The escaping method or its **order** is load-bearing             |
| `PERF:`     | The obvious rewrite is correct but slower                        |
| `CONTEXT:`  | Something is missing that the code cannot show — keep it short   |
| `TODO:`     | Work not yet done. A different axis: meant to be deleted         |

Four rules:

-   **Two lines per comment, five for a file header.** A short `FIX:` rides on the
    declaration itself. `CONTEXT:` is the one exception — use it wherever real information is
    missing, and keep it brief; the block in `_drawer.scss` spends its length on a diagram,
    not on more sentences.
-   **Untagged prose means restatement — delete it.** If a comment does not earn a tag, the code
    below it already says the same thing.
-   **Compress the important context; delete the edge case.** Do not relocate it to a doc.
    Two tiers means two things to keep in step, which is the drift this theme already knows
    about. `git log -p` holds whatever gets cut, and that is the right place for it.
-   **Most specific tag wins, and history goes in the text.** The drawer's contrast bug is
    both a `FIX:` and an `A11Y:`; it is tagged `A11Y:` and says it shipped that way.

The domain tags earn their slot by being rare, so keep their tests narrow. `A11Y:` is not for
any code that touches ARIA — `role="tab"` in a tablist is just the implementation. `SAFETY:`
is not for a routine `esc_html()` on output; it is for `ucf_brand_highlight_terms()` in
`includes/search.php`, where escaping each run _before_ joining is what makes `<mark>`
survive, and the obvious order is silently wrong.

`SYNC:` is the one worth grepping. `grep -rn "SYNC:" src/ includes/` returns every
hand-maintained coupling in the theme — `$breakpoint-tabs` against `TABS_QUERY`, a `save()`
against the markup already in the database — which is the list to read before a refactor.

**`src/scss/_drawer.scss` is the reference.** It is converted; the rest of `src/scss/`,
`includes/`, and `src/js/` are not yet. Convert a file when you are already working in it,
and check the result the way that one was checked: strip the comments from both versions and
diff. Nothing but comments may move.
