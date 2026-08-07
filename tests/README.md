# Tests

Three suites. `npm test` runs the two fast ones:

| Suite | Command | Needs | Runtime |
| --- | --- | --- | --- |
| PHP units | `npm run test:php` | PHP + Composer | ~0.1s |
| Block round-trips + markup sweep | `npm run test:js` | Node, and PHP for the sweep | ~3s |
| PHP integration | `npm run test:integration` | Docker (wp-env) | ~2min cold, ~1s warm |

`npm test` runs the first two and **never touches Docker** — that is deliberate, and worth
protecting. A suite that needs a container is a suite nobody runs before committing, which is
the same as not having it. `npm run test:all` adds the integration tier.

The split is not by speed but by honesty: if a function needs real WordPress, it goes in the
integration tier rather than being mocked into the fast one, because mocking the thing under
test tests the mock.

## PHP units — `tests/php/`

PHPUnit with [Brain Monkey](https://brain-wp.github.io/BrainMonkey/), **no WordPress, no
database, no Docker**. What is covered is the theme's pure logic: heading slugs, section
numbering, search term parsing, highlighting, snippet windowing, ranking, and the two markup
builders (`site-mark`, `section-nav`).

That boundary is deliberate. WordPress functions the covered code calls are defined in
`tests/php/stubs/wp-escaping.php` — shared with the pattern renderer so the two cannot drift —
and they reproduce core's real behavior where it matters:
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

## Markup validity sweep — `tests/js/markup-validity.test.js`

Every block in every template part and pattern must be markup the current `save()` would
produce. `docs/architecture.md` records that `section-index.php` shipped a violation of this
once — a paragraph carrying a `textColor` attribute with no matching class, which looked fine
on white and stayed grey-on-black inside a Dark group.

Template parts are read straight off disk. Patterns cannot be: they interpolate PHP *inside*
their block markup, so `tests/php/render-patterns.php` renders each one the way core does and
hands back the result. **That is why this suite needs a PHP binary**, unlike `blocks.test.js`.

### `isValid` is not the assertion — and that matters

`parse()` does not merely validate. When markup fails to match, it walks the block type's
`deprecated` array for an older `save()` that *does* match, and on a hit it migrates the block
and reports `isValid: true`. Core blocks carry many deprecations — `core/heading` has six.

This was measured, not assumed. **Reproducing the exact `section-index.php` bug leaves every
block `isValid: true`**; core silently recovers it and logs "Updated Block: core/paragraph".
A sweep asserting on `isValid` would have shipped that bug a second time.

So the assertion is `isValidBlockContent( name, attributes, originalContent )`, which compares
against today's `save()` with no deprecation fallback. `core/html` is exempt — it carries
verbatim raw markup and has no normalization contract to hold it to; real WordPress 7.0.2
parses both of the theme's `core/html` chrome blocks as valid.

### Console silencing

Gutenberg logs a full validation diff for every invalid or migrated block, and
`@wordpress/jest-console` turns that into its own failure — hundreds of lines that bury which
file actually broke. The suite replaces the console methods with **plain functions, not
`jest.spyOn`**: jest-console asserts against `.mock.calls`, so a spy still records and still
fails. An ordinary function leaves nothing to find.

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

## PHP integration — `tests/integration/`

Real WordPress, real database, real hooks, via [`@wordpress/env`](https://www.npmjs.com/package/@wordpress/env).

```bash
npm run test:integration   # boots the containers, runs the tests, stops them again
```

**It always leaves the environment stopped**, including when the tests fail and when the run
is interrupted. Containers left running hold ports 8888 and 8889, which is how the next
project's `wp-env start` fails with nothing but "address already in use" to explain itself.

That is `tools/integration-tests.js` rather than a chained npm script, because three
properties have to hold together and a `&&` chain gives none of them reliably: PHPUnit's exit
code is what the process exits with (not the teardown's), the teardown runs even when the
tests fail — which is exactly when `&&` would skip it — and Ctrl-C tears the containers down
instead of orphaning four of them. All three are verified, the failure path included.

The cold start is around two minutes. While iterating, skip it:

```bash
npm run env:start              # once
npm run test:integration:only  # ~1s per run, against the running environment
npm run env:stop               # when you are done
```

Covers what the fast suite deliberately cannot:

- **`ucf_brand_add_heading_anchor()`** through the real `render_block` pass — including the
  `static` slug registry resetting between posts. Without that reset, the second page rendered
  in a request gets every anchor suffixed and every inbound link to it breaks.
- **`ucf_brand_get_ordered_sections()`** against real posts: that the `meta_key`/`meta_query`
  pair actually excludes drafts, child pages, posts, and unnumbered pages, and that ordering
  is numeric rather than lexical (10 must not sort before 2).
- **Search**: that `pre_get_posts` narrows the *main* query and leaves secondary queries
  alone, and that the subsections block renders real deep links inside a real search loop.

One test is there specifically to catch the failure `docs/architecture.md` warns about: it
renders a page, asks search to derive that page's anchors, and asserts every derived anchor is
present in the rendered HTML. Two implementations of one slug disagreeing is what broke shared
deep links before.

### PHPUnit is pinned to 9.6, and that is not an oversight

WordPress's test suite calls `PHPUnit\Util\Test::parseTestMethodAnnotations()`, which PHPUnit
removed in 10. So WP 7.0.3's suite caps at 9.x, and **the whole project is pinned to `^9.6`**
so there is one toolchain rather than two. That is what WP core itself uses.

The practical consequence: use annotations (`@dataProvider`, `@covers`), not PHP attributes
(`#[DataProvider]`). 9.6 runs cleanly on PHP 8.5, so the pin costs nothing locally.

### If wp-env will not start

Two things account for nearly every case, and the runner prints both:

- **Docker is not running.**
- **Ports 8888/8889 are taken**, almost always by another wp-env project that was left up.
  Stop that one, or override the ports for your machine only:

  ```json
  // .wp-env.override.json — gitignored, never commit it
  { "port": 8899, "testsPort": 8898 }
  ```

`.wp-env.json` itself stays on the defaults so the committed config is the standard one.

## Not yet built

- Accessibility suite (Phase 2): Playwright + axe-core, per route, per pattern and per block
  style variant.

See [`docs/testing-plan.md`](../docs/testing-plan.md) for the order of work and the decisions
behind it.
