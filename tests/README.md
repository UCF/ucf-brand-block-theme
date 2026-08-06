# Tests

Two suites today, both run by `npm test`:

| Suite | Command | Needs | Runtime |
| --- | --- | --- | --- |
| PHP units | `npm run test:php` | PHP + Composer | ~0.1s |
| Block round-trips | `npm run test:js` | Node | ~2s |

Both are fast and dependency-light on purpose. A suite that takes a minute and needs Docker
is a suite nobody runs before committing, so anything requiring a live WordPress is kept out
of these and pushed to the integration tier instead.

## PHP units — `tests/php/`

PHPUnit with [Brain Monkey](https://brain-wp.github.io/BrainMonkey/), **no WordPress, no
database, no Docker**. What is covered is the theme's pure logic: heading slugs, section
numbering, search term parsing, highlighting, snippet windowing, ranking, and the two markup
builders (`site-mark`, `section-nav`).

That boundary is deliberate. WordPress functions the covered code calls are stubbed in
`TestCase::stubWordPress()`, and the stubs reproduce core's real behavior where it matters —
`esc_html()` does not double-encode, because highlighting escapes each run between matches
separately and a double-encoding stub would make those assertions pass against behavior
WordPress does not have.

**What does not belong here:** anything needing a real meta query, `WP_HTML_Tag_Processor`,
or the `render_block` filter — `ucf_brand_add_heading_anchor()` and the search query scoping,
mainly. Mocking those in would only test the mock. They want the integration tier.

Two data-only stand-ins (`WP_Post`, `WP_Block`) live in `tests/php/stubs/`. They hold no
logic, which is why the functions taking them stay in the fast suite.

## Block round-trips — `tests/js/`

Every custom block is static: `save()` emits the real markup and nothing renders on the
server. So the dominant failure mode is that `save()` changes, markup already stored in a
page stops matching what `save()` now produces, and the editor flags every existing instance
as invalid — **which is invisible on the front end.** `docs/architecture.md` is explicit that
a page render is not a sufficient check.

Two complementary assertions per block:

- **Round-trip** — `serialize()` → `parse()` → `isValid`. The same comparison the editor
  makes. Catches markup that cannot be read back.
- **Snapshot** — catches any markup change *at all*, including a self-consistent one that
  round-trips cleanly while silently invalidating every page already saved. Renaming a class
  in `save()` is exactly this: the round-trip still passes, the snapshot does not. Snapshots
  are committed, and reviewing a change to one is the point.

### The registration wrinkle

Blocks call `registerBlockType( metadata.name, { edit, save } )` — the *name*, not the
metadata object. On a real site that works because PHP's `register_block_type()` has already
read `block.json` and shipped its contents to the client. Jest has no PHP, so without the
same step registration silently warns and bails, `serialize()` returns `""`, and every
round-trip test passes against nothing.

`tests/js/helpers/register-blocks.js` therefore calls
`unstable__bootstrapServerSideBlockDefinitions()` — the exact API core uses to hand the
client its server-side definitions — before importing each block. The theme's source is
imported unmodified.

The "registers every block that ships in `src/blocks/`" test exists to catch that empty
registry, and to catch a new block being added without coverage. It has already earned its
place once.

### Jest configuration

`jest.config.js` carries four non-obvious settings, each commented in place:

- Babel is passed **inline to Jest** rather than added as a root `babel.config.js`, which
  webpack would also pick up. `npm run build` output is byte-identical with the test tooling
  installed — verify that before changing it.
- `customExportConditions` selects the CJS builds; jsdom's default `browser` condition
  points most `@wordpress/*` packages at ESM that Jest cannot load.
- A short `transformIgnorePatterns` allowlist covers the few dependencies with no CJS build
  at all. **If that list starts growing on every dependency bump, stop resolving the editor
  through npm** and validate against the WordPress bundle the site actually runs instead —
  that is higher fidelity anyway, since npm package versions drift from the deployed WP.
- `setupFiles` must re-list the preset's own globals file, because naming it replaces the
  preset's copy rather than adding to it.

## Not yet built

- Integration tier for the WordPress-dependent PHP (see above).
- Markup validity sweep over `parts/*.html` and `patterns/**/*.php`. Patterns interpolate PHP
  *inside* their block markup, so this needs a PHP render step before the markup can be
  parsed — `section-index.php` has shipped this class of bug before.
- Accessibility suite (Phase 2): Playwright + axe-core, per route, per pattern and per block
  style variant.
