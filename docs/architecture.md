# Architecture and conventions

The rules to follow when changing this theme, and the reasoning behind them. `README.md`
covers what the theme _is_ and where each file lives; this file covers how to change it
without breaking something subtle.

Most of what follows was learned the hard way. Where a rule exists because a bug shipped,
that is said outright — those are the ones not to "clean up".

**Contents**

-   [The escalation ladder](#the-escalation-ladder)
-   [Roles, not tokens](#roles-not-tokens)
-   [Patterns declare structure and composition, never color](#patterns-declare-structure-and-composition-never-color)
-   [Blocks](#blocks)
-   [PHP lives in `includes/`, one topic per file](#php-lives-in-includes-one-topic-per-file)
-   [JavaScript is one pipeline](#javascript-is-one-pipeline)
-   [Build and content](#build-and-content)
-   [H2s are structural](#h2s-are-structural)
-   [Gotchas that have already bitten](#gotchas-that-have-already-bitten)

## The escalation ladder

Same rule as `ucf-wordpress-block-theme`. Reuse tokens; add as little as possible.

1. **`theme.json` tokens first.** Never hard-code a hex, a font stack, a spacing value or
   a size that a token already expresses.
2. **Existing classes and block styles next** — `lead`, `eyebrow`, `meta`, the accent
   color pairs (`is-style-paper`, `is-style-dark`, … and their `-accent` variants), and
   the `is-style-on-dark` / `is-style-halftone` group styles. Reuse these rather than
   writing pattern-local CSS.
3. **Core block controls next.** Express color through the block's color controls
   (`backgroundColor` / `textColor` / `style.color`) and layout through block attributes —
   not bespoke classes.
4. **Only then add something new**, and make it a reusable, token-driven primitive: a
   `register_block_style()` in `includes/block-styles.php` plus a partial in `src/scss/`.

Corollary: don't ship near-duplicate patterns for color variants.

## Roles, not tokens

`_compositions.scss` is the layer between the palette and the CSS that paints it. A
composition (`is-style-light`, `-dark`, `-paper`, `-bold-gold`) declares a set of
`--brand-*` roles — `accent`, `on-accent`, `line`, `body`, `body-muted`, `lead`, `eyebrow`,
`meta`, `link` — and everything downstream reads those instead of naming a color.

-   **Anything that _sets_ a `--brand-*` property belongs in `_compositions.scss`.**
    Anything that _reads_ one for a single component belongs with that component.
-   **The test for which to use: could an editor drop this inside a group carrying a
    composition?** If yes it reads a role. If no — fixed chrome like the drawer, or status
    colors like `success` / `danger` that must _not_ invert — a direct token is correct and
    clearer. Don't convert `_tabs.scss` / `_accordion.scss` speculatively.
-   Consumers use `compositions.role-default("<role>")` as the `var()` fallback, never a
    restated token. That is what keeps the default in one place.

## Patterns declare structure and composition, never color

Color reaches a pattern two ways only: the composition class on a container, and the
prefilled role utilities in `_utilities.scss` (`accent-fill`, `accent-text`, `hairline`).
A `backgroundColor` / `textColor` / `borderColor` attribute, or a `var:preset|color|…` in a
`style` object, freezes that block to one field and opts it out of the system.

Because preset classes carry `!important`, opting out silently wins — `is-style-meta`
combined with `textColor: "text-secondary"` looks fine on white and stays grey-on-black
inside a Dark group. That exact pairing shipped in `section-index.php`. Set the width, the
side and the size through core's controls; let the class supply the color.

The one legitimate exception is a pattern whose subject _is_ a color — the swatch patterns
name palette slugs on purpose.

The role utilities are plain classes, not `register_block_style()`, for two reasons: an
accent bar is part of what a pattern is rather than a look an editor picks, and block
styles are single-select, so registering them would consume the slot an editor needs for
a composition.

**A pattern is core blocks and nothing else.** Structure, spacing, borders and type come
from each block's own controls. The only classes a pattern may carry are the composition
on its container and the role utilities that bind its accent (`accent-fill`, `accent-text`,
`hairline`) — the parts that make it _that_ pattern rather than a look an editor picks.
A registered block style prefilled as a sensible default is fine; a bespoke class carrying
padding or a border is not. That is a sign the primitive is missing — add it to
`_base.scss` or express it through the block's controls, not through a class only the
pattern knows about. `brand-section` and `is-style-specimen` predate this rule and are the
two standing exceptions, not precedent.

## Blocks

**Never use a Custom HTML (`core/html`) block in page content.** If content needs
structure that core blocks don't express, the answer is a custom block, a pattern, or a
registered block style — not raw markup pasted into a page. There is currently zero
`wp:html` in authored pages; keep it that way.

The one `core/html` in the theme is `parts/mobile-bar.html`, and it is chrome rather than
content: a `<button>` carrying `aria-expanded` / `aria-controls` and a fixed-position scrim
have no core-block expression, and both are wired by `src/js/brand-nav.js`. Chrome in a
template part is not a precedent for raw markup in a page.

Custom blocks live in `src/blocks/<name>/`, built by `wp-scripts` to `build/<name>/`, and
registered by the loop in `ucf_brand_register_blocks()` (`includes/blocks.php`), which
discovers them by globbing for `block.json` rather than from a hand-maintained list.

-   **Static only.** `save()` must emit real markup. No `render.php`, no `render_callback`.
-   **`edit` is a named, capitalized component**, declared above the registration and passed
    as `edit: Edit`. An inline `edit()` method calls hooks in a function neither React nor
    `react-hooks/rules-of-hooks` recognizes as a component. `save()` stays a method — it is
    not a component and must not grow into one.
-   Take colors by palette **slug** and apply core's `has-{slug}-background-color` /
    `has-{slug}-color` classes — never write an inline hex into `save()`.
-   Don't reference theme functions or paths from block sources; they must lift into a
    plugin unchanged.
-   `ucf-news-block-theme` uses `render.php` server rendering. That is **not** a precedent
    for this theme.

The theme _does_ have two server-rendered blocks — `ucf-brand/section-nav` and
`ucf-brand/search-subsections` — but they are theme glue, not distributable design blocks,
and they live in `includes/` with the data they render rather than in `src/blocks/`. See the
next section.

### Hand-writing block markup in patterns

Pattern PHP must serialize **exactly** what `save()` would produce or the editor flags
the block invalid. Class and inline-style _order_ doesn't matter (Gutenberg compares
class tokens as a set and parses style declarations), but presence and values do.

Verify by opening a page in the block editor and asking the store directly:

```js
wp.data.select( 'core/block-editor' ).getBlocks(); // walk innerBlocks, check isValid
```

A page render is not a sufficient check — invalid blocks still render on the front end.

## PHP lives in `includes/`, one topic per file

`functions.php` is a loader and nothing else: an ordered array of include names plus a
`require_once` loop. Anything new belongs in the file that owns its topic, or in a new
file added to that array — never appended to `functions.php`.

| File                     | Owns                                                          |
| ------------------------ | ------------------------------------------------------------- |
| `setup.php`              | Theme supports, editor rendering modes                        |
| `enqueue.php`            | Every way CSS and JS reach the front end or the editor canvas |
| `blocks.php`             | Static custom block registration                              |
| `block-styles.php`       | Every `register_block_style()`                                |
| `pattern-categories.php` | The pattern category ladder                                   |
| `university-header.php`  | The UCF University Header: its script tag and its placeholder |
| `meta.php`               | Per-page fields                                               |
| `sections.php`           | Section numbering, ordering, the number binding               |
| `section-nav.php`        | The drawer's server-rendered block                            |
| `headings.php`           | H2 anchor ids and section extraction                          |
| `search.php`             | Search scoping and subsection deep links                      |

Two conventions hold this together:

-   **A dynamic block lives in the file that owns the data it renders.** `section-nav` sits
    with `ucf_brand_get_ordered_sections()`; `search-subsections` sits inside `search.php`.
    `blocks.php` is therefore about static blocks only.
-   **Every `register_block_style()` is in `block-styles.php`,** and each carries a comment
    naming the `src/scss/` partial that defines it. A style registered but not defined is an
    editor offering that paints nothing.

Load order in `functions.php` is coarse to fine and matters in one place: `enqueue.php`
calls `ucf_brand_format_number()` from `sections.php`. Every file only defines functions and
adds hooks at include time, so nothing runs before WordPress calls it.

## JavaScript is one pipeline

Everything hand-written is under `src/`; everything generated lands in `build/`. There is
one build tool for JS and one for CSS, and no third path.

-   `src/blocks/<name>/` — block sources, auto-discovered by their `block.json`.
-   `src/js/editor/` — editor glue, one job per module, assembled by `index.js` into the
    `editor` entry. Modules register against a hook or a store, so import order is not
    significant. The shared `section-number.js` helper exists so the three modules that need
    the page's number don't each re-derive it.
-   `src/js/brand-nav.js`, `src/js/badge-format.js` — the drawer script and the Badge
    rich-text format.

Entries without a `block.json` are named explicitly in `webpack.config.js`. Two things
there are deliberate:

-   **Every entry emits a `<name>.asset.php`,** and `ucf_brand_enqueue_build_script()` reads
    it. That is why no dependency array is written by hand in PHP — adding an
    `@wordpress/*` import to a source file is the whole change. `badge-format.js` used to be
    a no-build script reading globals off `wp.*` with its dependencies restated in PHP;
    converting it to imports produced exactly the same list, derived instead of copied.
-   **`output.clean` keeps `css/`.** wp-scripts wipes its output directory on every build,
    preserving only `fonts/` and `images/`. Without the override, `npm run build:blocks` on
    its own would delete a stylesheet `npm run build:css` had just produced.

## Build and content

-   `npm run build` runs both halves: `wp-scripts` for `src/blocks/` and `src/js/` →
    `build/`, and `sass` for `src/scss/main.scss` → `build/css/main.css`. `build/` is
    committed; never edit anything in it directly. New SCSS partials must be `@use`d in
    `src/scss/main.scss`. Declarations alphabetical.
-   **Block CSS goes in `src/scss/`, not in the block folder.** One stylesheet pipeline —
    `wp-scripts` builds JS only. This keeps block styles in the editor automatically via
    `add_editor_style()`.
-   Webfonts come from `theme.json` `fontFace`, never from SCSS or `wp_enqueue_style`.
-   `add_editor_style()` loads the same `main.css` in the editor, so front-end and editor
    stay in parity. Keep it that way.
-   Pattern categories form a compositional ladder, small to large: `ucf-brand-units`
    (single primitives) → `ucf-brand-groups` (clusters of units) → `ucf-brand-sections`
    (full-width content bands) → `ucf-brand-pages` (whole-page layouts). Registered in
    `includes/pattern-categories.php`. **Avoid the bare `ucf-sections` slug** — it is
    reserved by the UCF Section plugin.

### Verifying a change to PHP on a running site

PHP opcache is on in the usual local stack with `revalidate_freq=2`. Swapping files and
immediately requesting a page serves the _old_ compiled code, which makes a
before/after comparison silently meaningless. Wait past the revalidation window before
capturing anything you intend to trust.

## Formatting and linting

One formatter per file type, and they do not overlap:

| Files                               | Formatted by          | Checked by                                     |
| ----------------------------------- | --------------------- | ---------------------------------------------- |
| `*.php`                             | `phpcbf`              | `composer lint`                                |
| `src/blocks/`, `src/js/`, `tests/`  | Prettier              | `npm run lint:js` (via ESLint), `format:check` |
| `src/scss/`                         | Prettier              | `npm run format:check`                         |
| `*.md`, `*.json`, `*.yml`           | Prettier              | `npm run format:check`                         |
| `patterns/`, `parts/`, `templates/` | nothing, deliberately | the markup sweep in `npm run test:js`          |

`npm run format` fixes everything Prettier owns; `composer lint:fix` is the PHP half. Three
things follow from the split, and all three are easy to undo by accident:

-   **Prettier is part of wp-scripts, not an addition to it.** `@wordpress/scripts` depends
    on `wp-prettier`, and `@wordpress/eslint-plugin/recommended` switches on its
    `prettier/prettier` rule whenever it finds Prettier installed — which is why
    `npm run lint:js` _is_ Prettier for JS, and why there is no ESLint-versus-Prettier
    conflict to resolve. It is pinned in `devDependencies` at the version wp-scripts
    resolves, so a wp-scripts bump cannot silently reformat the codebase.
-   **stylelint checks meaning, not whitespace.** `.stylelintrc.js` extends
    `@wordpress/stylelint-config/scss` rather than the `scss-stylistic` preset wp-scripts
    defaults to. The stylistic rules contradict Prettier on any declaration long enough to
    wrap, and their `--fix` corrupts the Sass maps in `_compositions.scss`. That file's
    header records both, and is the thing to read before changing the preset.
-   **Prettier never sees PHP or block markup.** `*.php` is in `.prettierignore` because
    phpcs owns it, and `patterns/`, `parts/` and `templates/` are there because their markup
    must match what `save()` emits — see "Hand-writing block markup in patterns" above.

## H2s are structural

Sub-navigation is generated from each page's `<h2>` elements. An H2 is a drawer entry.
Use H3 for anything that should not appear in the drawer.

**`includes/headings.php` owns the anchor id.** Ids are assigned during `render_block`, so
they are in the HTML the server sends. Don't derive them anywhere else. `brand-nav.js` used
to slugify in the browser, and the moment search needed to emit a `/page/#heading` link
server-side there were two implementations that had to agree byte for byte — they didn't,
and the deep links landed at the top of the page.

Two consequences worth knowing:

-   `ucf_brand_heading_slug()` is a port of the old JS `slugify()`, **not** a call to
    `sanitize_title()`, which transliterates accents and handles entities differently.
    Anchors already shared or bookmarked keep resolving. Don't "simplify" it.
-   Anything server-side that needs a page's sections should call
    `ucf_brand_get_post_sections()` rather than re-parsing content. It reuses the same two
    slug helpers in the same order, which is the only reason its anchors match the page's.

## Gotchas that have already bitten

-   **Preset slugs get kebab-cased.** A slug of `h1` produces
    `--wp--preset--font-size--h-1` and `has-h-1-font-size`. Keep slugs kebab-stable
    (`heading-1`, `display-1`, `ui`, `meta`) so the slug and the generated name match.
-   **Don't reuse core's default preset slugs** (`small`, `medium`, `large`, `x-large`).
    `defaultFontSizes` is `false`, but same-slug collisions are still confusing.
-   **Anything that sets a background must declare a text treatment with it.** Body copy,
    links and the `lead` / `eyebrow` / `meta` helpers read `--brand-*` custom properties;
    a background that sets none of them inherits the enclosing one, so a light card
    nested in a dark section would keep the dark section's grey copy. Add a row to
    `$treatments` in `_compositions.scss` and `@include treatment(...)`. Never recolor
    these with a descendant selector like `.is-style-on-dark p` — inheritance never beats
    a matching rule, so that version leaks into every nested card that has its own
    background. That bug shipped once already.
-   **`ch` is font-relative.** Never use `ch` for `layout.contentSize` — the measure would
    scale with each element's own font size, so an H1 would get a wildly wider column than
    a paragraph. Content sizes are in px.
-   **`overflow-x: clip`, never `hidden`.** An `overflow: hidden` ancestor silently kills
    `position: sticky` on every descendant, which would break the drawer.
-   **`align-items: start` on `.brand-shell` is load-bearing.** The grid default of
    `stretch` makes the sticky sidebar full-height and sticky a no-op.
-   **A template part always gets a wrapper `<div>`, and that can kill `position: sticky`.**
    `render_block_core_template_part()` has no no-wrapper option. Moving the mobile bar into
    `parts/mobile-bar.html` put a div around it whose height is exactly the bar's height,
    leaving a sticky element nowhere to travel. `.brand-mobile-bar-part { display: contents }`
    in `_drawer.scss` removes the wrapper from the box tree and restores the original flow.
    Any future part holding sticky or fixed chrome needs the same treatment.
-   **Core's constrained layout uses `margin-left: auto !important`.** Anything setting a
    narrower `max-width` gets centered as a side effect. The single override lives in
    `src/scss/_base.scss` — put new cases there rather than scattering `!important`.
-   **The footer must stay outside `<main>`.** That is the only thing making the drawer
    stop at the footer.
-   **Pages open `template-locked`, so anything editable must be in the template itself.**
    `ucf_brand_page_rendering_mode()` turns "Show template" on by default for pages, which
    is what makes the hero visible while writing. In that mode core disables every block in
    the template except `core/post-title`, `core/post-featured-image` and
    `core/post-content` — and it disables template parts _and their children_ outright. Move
    the hero into `parts/` and it renders but goes inert. It is also a default, not a lock:
    the per-user preference beats it, so don't rely on it for anything but visibility.
-   **To keep something in a template editable, add it to `editor.postContentBlockTypes`.**
    That filter is the allowlist above, and it is the only supported seam for this — don't
    invent a `setBlockEditingMode` effect that races core's. It matches by **block type**, so
    the thing you name has to be a block of your own: allowlisting `core/paragraph` would
    unlock every paragraph in every template. `ucf-brand/page-hero` exists for exactly this
    reason and holds no content of its own.
    Inside such a wrapper, `templateLock: 'contentOnly'` sorts the children — core keeps the
    ones whose block type declares a `role: "content"` attribute and disables the rest. A
    bound block is a further case: core refuses to let it be typed into unless its binding
    source defines `setValues`, which is why `core/post-meta` fields are editable in place
    and `ucf-brand/section-number` stays derived.
-   **Per-page values cannot live in a template block's content.** A template is one object
    shared by every page, so a paragraph typed into `templates/page.html` is the same
    paragraph everywhere. Per-page hero copy is post meta reached through a binding. One
    binding resolves to one string — that is why the deck is a single paragraph and the
    closing note is a second field rather than one multi-paragraph blob.
-   **A binding needs `show_in_rest` on the meta or it dies silently on the front end.**
    `_block_bindings_post_meta_get_value()` refuses any key not exposed to REST and returns
    null, which leaves the block's saved (empty) content in place. It looks like a CSS bug.
-   **`register_block_bindings_source()` is the front end only — the editor needs a second,
    JS registration.** A PHP-only source reaches the client as a label with no `getValues`,
    so the canvas prints the source's _name_ where the value belongs. Register the client
    half with `registerBlockBindingsSource()` (`src/js/editor/section-number-binding.js`),
    passing neither `label` nor `usesContext` — the server already supplied both and a second
    label warns. Omit `setValues` to keep the field read-only. Accept that this restates the
    PHP formatter in JS; the copies must be kept in step, and the reason it is tolerable here
    and not for heading slugs is that nothing on the front end runs the JS copy.
-   **Nothing in the hero may depend on the photo.** Copy sits on a featured image, so the
    `scrim` gradient carries the contrast and the type stays at full strength — the closing
    note is ordinary body copy, not `muted`, and the same goes for anything added later.
    Grey on an arbitrary photograph fails whatever the gradient does. Note that with no
    featured image `core/post-featured-image` renders nothing _including its overlay_, so
    the fallback field is whatever the composition class paints — currently `is-style-dark`.
-   **The hero's copy needs `width: 100%` and `position: relative`, and both are load-bearing.**
    Core's constrained layout centers children with `margin-inline: auto !important`; inside
    a flex container those auto margins beat `align-items: stretch`, so a short title shrinks
    to its text and floats to the middle of the band. And painting order puts every
    positioned box above every non-positioned one regardless of source order, so the
    absolutely positioned featured image covers copy that merely comes later in the document.
    Both are in `_hero.scss` with the reasoning; don't "clean them up".
