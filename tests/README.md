# Tests

Four suites. `npm test` runs the two fast ones:

| Suite                            | Command                    | Needs                        | Runtime               |
| -------------------------------- | -------------------------- | ---------------------------- | --------------------- |
| PHP units                        | `npm run test:php`         | PHP + Composer               | ~0.1s                 |
| Block round-trips + markup sweep | `npm run test:js`          | Node, and PHP for the sweep  | ~3s                   |
| PHP integration                  | `npm run test:integration` | Docker (wp-env)              | ~2min cold, ~1s warm  |
| Accessibility                    | `npm run test:a11y`        | Docker (wp-env) + Playwright | ~2min cold, ~15s warm |

`npm test` runs the first two and **never touches Docker** — that is deliberate, and worth
protecting. A suite that needs a container is a suite nobody runs before committing, which is
the same as not having it. `npm run test:all` adds the integration tier.

The split is not by speed but by honesty: if a function needs real WordPress, it goes in the
integration tier rather than being mocked into the fast one, because mocking the thing under
test tests the mock.

## PHP units — `tests/php/`

PHPUnit with [Brain Monkey](https://brain-wp.github.io/BrainMonkey/), **no WordPress, no
database, no Docker**. What is covered is the theme's pure logic: heading slugs, section
numbering, search term parsing, highlighting, snippet windowing, ranking, the `section-nav`
markup builder, and the University Header's script tag and placeholder.

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

-   **Round-trip** — `serialize()` → `parse()` → `isValid`. The same comparison the editor
    makes. Catches markup that cannot be read back.
-   **Snapshot** — catches any markup change _at all_, including a self-consistent one that
    round-trips cleanly while silently invalidating every page already saved. Renaming a class
    in `save()` is exactly this: the round-trip still passes, the snapshot does not. Snapshots
    are committed, and reviewing a change to one is the point.

### The registration wrinkle

Blocks call `registerBlockType( metadata.name, { edit, save } )` — the _name_, not the
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

Template parts are read straight off disk. Patterns cannot be: they interpolate PHP _inside_
their block markup, so `tests/php/render-patterns.php` renders each one the way core does and
hands back the result. **That is why this suite needs a PHP binary**, unlike `blocks.test.js`.

### `isValid` is not the assertion — and that matters

`parse()` does not merely validate. When markup fails to match, it walks the block type's
`deprecated` array for an older `save()` that _does_ match, and on a hit it migrates the block
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

-   Babel is passed **inline to Jest** rather than added as a root `babel.config.js`, which
    webpack would also pick up. `npm run build` output is byte-identical with the test tooling
    installed — verify that before changing it.
-   `customExportConditions` selects the CJS builds; jsdom's default `browser` condition
    points most `@wordpress/*` packages at ESM that Jest cannot load.
-   A short `transformIgnorePatterns` allowlist covers the few dependencies with no CJS build
    at all. **If that list starts growing on every dependency bump, stop resolving the editor
    through npm** and validate against the WordPress bundle the site actually runs instead —
    that is higher fidelity anyway, since npm package versions drift from the deployed WP.
-   `setupFiles` must re-list the preset's own globals file, because naming it replaces the
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

-   **`ucf_brand_add_heading_anchor()`** through the real `render_block` pass — including the
    `static` slug registry resetting between posts. Without that reset, the second page rendered
    in a request gets every anchor suffixed and every inbound link to it breaks.
-   **`ucf_brand_get_ordered_sections()`** against real posts: that the `meta_key`/`meta_query`
    pair actually excludes drafts, child pages, posts, and unnumbered pages, and that ordering
    is numeric rather than lexical (10 must not sort before 2).
-   **Search**: that `pre_get_posts` narrows the _main_ query and leaves secondary queries
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

-   **Docker is not running.**
-   **Ports 8888/8889 are taken**, almost always by another wp-env project that was left up.
    Stop that one, or override the ports for your machine only:

    ```json
    // .wp-env.override.json — gitignored, never commit it
    { "port": 8899, "testsPort": 8898 }
    ```

`.wp-env.json` itself stays on the defaults so the committed config is the standard one.

## Accessibility — `tests/a11y/`

[axe-core](https://github.com/dequelabs/axe-core) through Playwright, against a real
WordPress, at three viewports. WCAG 2.0/2.1 A + AA.

```bash
npm run test:a11y   # boots wp-env, seeds the content, audits, stops the environment again
```

Like the integration tier it always leaves the environment stopped, and for the same reasons —
see `tools/a11y-tests.js`. The iteration loop is the same shape too:

```bash
npm run env:start && npm run env:seed   # once
npm run test:a11y:only                  # ~15s per run
npm run env:stop                        # when you are done
```

### Four tiers, and only the first is a list anyone maintains

| Tier         | What it audits                                       | Where the list comes from    |
| ------------ | ---------------------------------------------------- | ---------------------------- |
| **Routes**   | One page per template, plus search-with-no-results   | Written out in `seed.php`    |
| **Blocks**   | Custom blocks no pattern or template already renders | Written out in `seed.php`    |
| **Patterns** | One page per pattern, in isolation                   | `WP_Block_Patterns_Registry` |
| **Variants** | One page per registered block style                  | `WP_Block_Styles_Registry`   |

The bottom two read WordPress's own registries at seed time, so **a new pattern or block style
is audited on the next run with nothing to add here** — the same property the markup sweep has.
A new _block_ is the one case needing a decision, and `seed.php` fails the seed rather than
letting it slide: it checks every top-level block in `src/blocks/` renders on some page the
suite visits, counting templates and patterns as coverage.

### Why per-variant is the tier this theme needed

Per-route coverage would have missed the failure this theme's design makes possible. The
compositions — `is-style-dark`, `-paper`, `-light`, `-bold-gold` and their `-accent` twins —
set the `--brand-*` roles their contents read, so **the same `is-style-muted` paragraph
resolves to a different color in each one**. Grey on paper passes; the same grey on the dark
field need not. Auditing the default composition proves nothing about the other eleven.

So every `core/group` variant page carries a probe: a heading, body copy, a link, all four
text styles, a rule, a list and a button. Twelve compositions times that probe is the actual
cross-product, and it is what a mutation test confirmed — recoloring `.is-style-lead` turned
ten pages red, including every composition.

### The guards, and why each exists

An axe assertion on the wrong page passes. Every guard below is there because the green
version of its failure is indistinguishable from a clean site:

-   **HTTP status is asserted before axe runs.** A seeded page that 404s renders the 404
    template, which is perfectly accessible. This caught a real setup bug immediately — see
    the `.htaccess` note below.
-   **A variant page must carry its `is-style-*` class.** The sample markup in `seed.php` is
    hand-written, so a future WordPress could change what a block's `save()` emits and leave
    it stale. A stale page still renders — in its _default_ colors — and passes.
-   **The tabs page must have built a tablist** above 768px and must not below it.
    `src/blocks/tabs/view.js` adds the tab roles at runtime; if it fails to load, the plain
    stack it leaves behind is valid markup that passes axe at every viewport.
-   **A missing or partial manifest is a hard error.** Playwright reports "0 tests" as a pass,
    so a forgotten seed would look exactly like a site with nothing wrong with it.

### `incomplete` is reported, never failed on

axe splits "this is wrong" from "a human has to look at this". The second covers cases it
cannot compute — text over a background image, mainly — and those are recorded as annotations.
This policy is carried over verbatim from the news theme, and for the same reason: pa11y
conflates the two and produces a false positive on every hero.

### The theme must be activated in its own command

`switch_theme()` cannot do this from inside `wp eval-file`. WordPress has already booted by
then — `functions.php` loaded for whatever theme was active, `init` fired, patterns and block
styles registered — so the switch writes an option and changes nothing else in that process.

The failure mode is the interesting part, because it is invisible locally. The switch _does_
take effect for the **next** process, so the seed fails on a clean environment and passes on
every run after it: green on any machine that has run it once, red on every CI runner. It
shipped exactly that way, and no amount of local re-running would have shown it.

`tools/a11y-tests.js` runs `wp theme activate` as a separate invocation, and `seed.php` now
asserts rather than switches — failing loudly beats a fix that hides itself on the second
attempt. `tests/integration/bootstrap.php` solves the same problem from the other side, at
`muplugins_loaded`, which is why that tier never had it.

### wp-env will not write `.htaccess`, and the symptom is misleading

`flush_rules( true )` guards its write behind `got_mod_rewrite()`, which asks the _current_
server whether mod_rewrite is loaded. The current server is PHP-CLI in the `cli` container,
which has no Apache and says no; the container actually serving port 8888 does have it and is
never asked. `wp rewrite flush --hard` fails identically.

The symptom does not look like a permalink problem. Every seeded path returns Apache's own
bare "Not Found" page, so the suite audits a document with no `<html lang>` and reports
`html-has-lang` against a theme that sets it correctly. `seed.php` writes the file itself.

### Judging a finding

Not every violation on a variant page is a defect in the theme, and getting this wrong wastes
a designer's afternoon. `is-style-on-dark` is the worked example: it supplies the _treatment_
only — the `--brand-*` roles the helper classes read — and deliberately sets no background and
no base color, both of which come from the block's own controls. Audited on a bare page it
produces five guaranteed failures that are the fixture's fault, not the theme's. So
`ucf_brand_a11y_wrap_in_context()` reproduces the reference usage from
`templates/front-page.html` instead. **Read the style's definition in `src/scss/` before
filing what the suite reports.**

## CI

Two workflows, split by cost so the every-commit loop stays quick.

**`.github/workflows/ci.yml` — every commit, every branch** (~2 min)

| Job          | Runs                                                           | Gating   |
| ------------ | -------------------------------------------------------------- | -------- |
| `php`        | unit suite on PHP 8.1 and 8.2                                  | blocking |
| `javascript` | version check, build, build/ drift check, block + markup tests | blocking |
| `quality`    | phpcs, eslint, stylelint, prettier                             | advisory |

**`.github/workflows/ci-full.yml` — pull requests and main only**

| Job             | Runs                             | Gating   |
| --------------- | -------------------------------- | -------- |
| `integration`   | wp-env + the WordPress suite     | blocking |
| `accessibility` | wp-env + Playwright and axe-core | blocking |

Both need a booted WordPress, which is what puts them here rather than in `ci.yml`.

The accessibility job **blocks**, unlike the advisory `quality` job. That is deliberate and it
is not the same judgement call: a contrast failure is a defect with a measured value, not a
style preference, and the findings are meant to be acted on rather than noted. It also posts
them, which is the other half of the point — see below.

### The pull request comment

The job renders its findings into a single comment, edited in place on every push rather than
appended. `tools/a11y-report.js` builds it from structured axe data the specs attach.

It groups **by finding, not by test**, because the raw run misleads in a specific way: the
header, drawer and footer are on every page, so one bad grey in a template part fails
thirty-nine checks. Read per-test that is thirty-nine problems; it is one. Each row carries
the element, both hex values, the measured ratio and the threshold — enough for a designer to
answer "what should this be instead?" without cloning the repository.

The full `playwright-report` artifact holds everything the comment truncates. Comments are
skipped for pull requests from forks, which get a read-only token; the job summary and the
artifact still carry the findings there.

### When each runs

| Event                            | `ci.yml` | `ci-full.yml` |
| -------------------------------- | -------- | ------------- |
| Push to a branch with no PR      | ✓        | –             |
| Open a pull request              | –        | ✓             |
| Push to a branch with an open PR | ✓        | ✓             |
| Merge a PR, or commit to main    | ✓        | ✓             |

`ci-full.yml` deliberately does **not** re-run the fast suites. A `pull_request` event and the
`push` for its branch fire together, so `ci.yml` has already run them on the same commit —
duplicating them would double every PR's CI time for no extra signal.

**Style never fails the build.** phpcs, eslint, stylelint and prettier all run and are all
reported, but the `quality` job carries `continue-on-error` and never gates a merge. A red X
meaning "an unrelated file has a long comment line" is a red X people learn to ignore, which
costs more than the findings are worth.

Two checks sound like lint and are deliberately blocking anyway, because neither is about
style: `lint:version` catches `style.css` and `package.json` disagreeing, which ships a theme
reporting the wrong version; and the build/ drift check rebuilds and fails if the committed
output changed, because stale `build/` means the deployed theme does not match the source it
was built from.

## Not covered by anything yet

`src/js/brand-nav.js`, `src/js/badge-format.js` and `src/js/editor/*` have no unit tests. The
accessibility suite exercises `brand-nav.js` and `tabs/view.js` indirectly — it audits the DOM
those scripts produce, and asserts the tab roles exist — but that is coverage of the result,
not of the logic.
