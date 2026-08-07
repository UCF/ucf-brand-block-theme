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
| H2s are structural                                      | `includes/headings.php` owns every anchor id                     |
| Gotchas that have already bitten                        | The list not to "clean up"                                       |

Search has its own document: [`docs/search.md`](docs/search.md).

## Tests

`npm test` runs the two fast suites in about three seconds and needs no Docker.
`npm run test:integration` adds the WordPress tier; `npm run test:all` is everything. Read
[`tests/README.md`](tests/README.md) before adding to them — it covers what each suite is for
and the non-obvious parts of the setup.

**New code ships with its test.** What you have to write depends on what you added, and two
of the four cases are already automatic:

| You added                   | You write                                                                                             |
| --------------------------- | ----------------------------------------------------------------------------------------------------- |
| A function in `includes/`   | A case in `tests/php/`, or `tests/integration/` if it needs WordPress — nothing enforces this for you |
| A block in `src/blocks/`    | An entry in `tests/js/helpers/register-blocks.js`; a test fails until you do                          |
| A pattern in `patterns/`    | Nothing. The sweep reads the directory — just run it                                                  |
| A template part in `parts/` | Nothing. Same sweep                                                                                   |

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
-   **Prove a new test can fail.** Break the code it covers, watch it go red, then restore.
    This is not ceremony: two tests written in this repo passed against nothing at all — one
    because blocks silently failed to register and `serialize()` returned `""`, and one
    because an XSS payload sat outside the range being escaped. Both looked green and correct.
-   **Never assert on a block's `isValid` to check markup.** `parse()` recovers mismatched
    markup by migrating it through the block type's `deprecated` array and then reports
    `isValid: true`. The exact bug this theme already shipped in `section-index.php` passes an
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
    comment with it — including the file paths it references.
