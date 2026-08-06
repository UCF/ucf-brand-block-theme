# UCF Brand Block Theme — working notes

**Read [`docs/architecture.md`](docs/architecture.md) before changing anything.** It is the
single source for this theme's conventions and for the reasoning behind them. This file
does not restate those rules — keeping two copies is exactly the drift this theme has been
bitten by before. Read `README.md` first for what the theme is and where each file lives.

## What is in the architecture doc

Skim this index; go read the section before you touch the area it covers.

| Section | Covers |
| --- | --- |
| The escalation ladder | Tokens → existing classes → core controls → something new |
| Roles, not tokens | `--brand-*` roles, and which file may set one |
| Patterns declare structure and composition, never color | Why a `textColor` attribute in a pattern is a bug |
| Blocks | Static-only rule, palette slugs, where dynamic blocks go instead |
| PHP lives in `includes/` | One topic per file; `functions.php` is a loader only |
| JavaScript is one pipeline | `src/` → `build/`, generated `.asset.php` dependency lists |
| Build and content | What `npm run build` does; block CSS belongs in `src/scss/` |
| H2s are structural | `includes/headings.php` owns every anchor id |
| Gotchas that have already bitten | The list not to "clean up" |

Search has its own document: [`docs/search.md`](docs/search.md).

## Working practices

These are about how to make a change here, not about what the theme is.

-   **Never append to `functions.php`.** It is a loader. New behavior goes in the
    `includes/` file that owns its topic, or in a new file added to the array there.
-   **Rebuild and commit `build/` with any change under `src/`.** Both halves:
    `npm run build`. Never hand-edit anything in `build/`.
-   **Verify pattern markup through the editor's store, not the front end.** Invalid blocks
    still render:
    ```js
    wp.data.select( 'core/block-editor' ).getBlocks(); // walk innerBlocks, check isValid
    ```
-   **Watch for opcache when testing PHP changes on a running site.** The usual local stack
    caches with `revalidate_freq=2`; a before/after page capture taken faster than that
    compares stale code against stale code and proves nothing.
-   **This codebase documents *why*, not *what*.** Comments here explain reasoning and record
    bugs that already shipped. Match that when adding code, and when moving code keep its
    comment with it — including the file paths it references.
