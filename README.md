# UCF Brand Block Theme

Block theme for UCF brand documentation, modeled on [brand.utah.edu](https://brand.utah.edu/)
and the UCF Brand Hub prototype. This pass establishes the foundations — color, typography
and the drawer navigation. Components come later.

This file covers what the theme is and where things live.
[`docs/architecture.md`](docs/architecture.md) covers the rules for changing it, and is
required reading before you do.

## Where everything lives

One rule governs the layout: **everything hand-written is in `src/`, everything generated
is in `build/`, and `assets/` holds only static files that are neither.**

| Path                     | What's in it                                                                      |
| ------------------------ | --------------------------------------------------------------------------------- |
| `src/blocks/`            | Custom block sources, one folder per block. Discovered by their `block.json`.     |
| `src/js/editor/`         | Block-editor customizations, one job per module, bundled into `build/editor.js`.  |
| `src/js/brand-nav.js`    | The drawer's sub-navigation, scroll-spy and mobile toggle.                        |
| `src/js/badge-format.js` | The Badge rich-text format on the editor toolbar.                                 |
| `src/scss/`              | Every stylesheet, one partial per concern, compiled to `build/css/main.css`.      |
| `build/`                 | **Generated and committed.** Blocks, bundled scripts, the stylesheet. Never edit. |
| `assets/fonts/`          | Self-hosted webfonts, referenced from `theme.json`.                               |
| `includes/`              | All PHP behavior, one topic per file (table below).                               |
| `functions.php`          | A loader for `includes/` and nothing else.                                        |
| `templates/`             | Page templates: `404`, `front-page`, `index`, `page`, `search`, `single`.         |
| `parts/`                 | Template parts: `footer`, `brand-sidebar`, `mobile-bar`.                          |
| `patterns/`              | Block patterns, filed by rung of the composition ladder.                          |
| `theme.json`             | Every design token: palette, type scale, spacing, layout, webfonts.               |
| `style.css`              | Theme header only — name, version, requirements. No CSS.                          |
| `docs/`                  | Developer documentation.                                                          |

### What each `includes/` file owns

| File                     | Owns                                                          |
| ------------------------ | ------------------------------------------------------------- |
| `setup.php`              | Theme supports; the `template-locked` page editing mode       |
| `enqueue.php`            | Every script and stylesheet, front end and editor canvas      |
| `blocks.php`             | Static custom block registration                              |
| `block-styles.php`       | Every `register_block_style()`                                |
| `pattern-categories.php` | The four pattern categories                                   |
| `university-header.php`  | The UCF University Header: its script tag and its placeholder |
| `meta.php`               | Per-page fields: brand number, hero deck, hero note           |
| `sections.php`           | Section numbering, drawer ordering, the number binding        |
| `section-nav.php`        | The drawer's server-rendered navigation block                 |
| `headings.php`           | H2 anchor ids and section extraction                          |
| `section-index.php`      | The on-page index block, built from those H2s                 |
| `search.php`             | Search scoping and subsection deep links                      |

## Getting started

```bash
npm install
npm run build         # everything: src/blocks/ + src/js/ -> build/, src/scss/ -> build/css/
npm run build:blocks  # blocks and scripts only  (wp-scripts)
npm run build:css     # stylesheet only  (sass)
npm run start         # watch blocks and scripts
npm run watch         # watch stylesheet
```

Everything hand-written lives in `src/`; everything generated lands in `build/`, which is
**committed** so the theme can be deployed without running a build. Never edit anything in
`build/` by hand — edit the source and rebuild.

## Linting and formatting

```bash
npm run lint:js       # ESLint over src/blocks/ and src/js/  (wp-scripts)
npm run lint:css      # stylelint over src/scss/             (wp-scripts)
npm run lint:version  # style.css and package.json agree on the version
npm run format        # Prettier: JS, JSON, SCSS, Markdown, YAML
npm run format:check  # verify formatting, write nothing

composer install      # one-time: pull PHPCS + WordPress Coding Standards
composer run lint     # PHPCS over the theme's PHP
composer run lint:fix # auto-fix what phpcbf can
```

PHP follows the WordPress Coding Standards, configured in `phpcs.xml.dist` (the `WordPress`
ruleset, `ucf_brand` global prefix, theme text domain). JS/JSON/SCSS/Markdown follow the
shared `@wordpress/prettier-config`.

Block-markup files — `parts/`, `templates/`, and the `patterns/` PHP —
are deliberately excluded from Prettier in `.prettierignore`. Their canonical form is whatever
the block editor emits; reformatting the serialized markup diverges from each block's `save()`
and triggers invalid-block warnings. Leave it as the editor writes it.

## Design tokens

Everything lives in `theme.json`. Nothing in this theme hard-codes a hex, a font stack or
a spacing value; SCSS reads tokens through `--wp--preset--*` / `--wp--custom--*` variables
mapped in `src/scss/_variables.scss`.

-   **19 brand colors**, with `defaultPalette: false` and `custom: false`, so the editor
    offers brand tokens and nothing else — authors cannot pick an off-brand color.
-   **Three typefaces**, self-hosted in `assets/fonts/` and declared as `fontFace` entries.
    No request ever goes to a third-party font CDN.
-   **Fluid type scale** — every heading size is a `clamp()`, so there is no separate mobile
    scale to maintain.

### Swapping the typefaces

The prototype's Oswald / Montserrat / Roboto Mono are **web stand-ins** for the licensed
brand faces (Gotham and URW DIN Condensed). Because the font family slugs are abstract —
`display`, `body`, `mono` rather than font names — swapping them is a `theme.json`-only
change: drop the new `woff2` files into `assets/fonts/`, repoint the three `fontFamily`
and `fontFace` entries, and every template, pattern and stylesheet follows. No SCSS or
block markup references a font by name.

> **Watch the slug names.** WordPress kebab-cases preset slugs when generating CSS
> variables and `has-*-font-size` classes, so a slug like `h1` silently becomes
> `--wp--preset--font-size--h-1`. Every slug here is already kebab-stable
> (`heading-1`, `ui`, `meta`) so the two names stay identical.

## The university header

The black bar across the top of every page is **not this theme's markup**. It is the UCF
University Header, built at runtime by a script from
[universityheader.ucf.edu](https://universityheader.ucf.edu/), which owns its links, its
search and its appearance — UCF's terms are that none of the three may be altered locally.
The theme's whole share of it is `includes/university-header.php`: enqueue the script, and
print an empty `<div id="ucfhb">` at `wp_body_open` for it to fill.

Two details are load-bearing and both fail _silently_ — the bar renders either way, just
boxed at 940px instead of full width:

-   **`?use-full-width=1` has to be in the script's `src`.** The host serves a different
    build for that query string; the default build has no full-width branch compiled into it
    at all.
-   **The tag has to carry `id="ucfhb-script"`.** That build reads the option back out of its
    own `src` by looking itself up by that id. WordPress emits `id="{handle}-js"`, so
    `ucf_brand_university_header_script_tag()` rewrites it on `script_loader_tag`.

`tests/php/UniversityHeaderTest.php` covers both, for exactly that reason. Other documented
options (`use-1200-breakpoint`, `use-bootstrap-overrides`) go through the
`ucf_brand_university_header_src` filter rather than an edit here.

**The bar scrolls away.** It sits in normal flow above `.wp-site-blocks` and nothing pins
it, which is why the drawer below pins to the top of the viewport rather than to an offset.
The theme has no header of its own and no `parts/header.html`; site identity lives in the
drawer masthead, and on mobile in `parts/mobile-bar.html`.

The accessibility suite excludes `#ucfhb` from its audit — the same argument it already
applies to an embedded YouTube player. See the exclusion list in `tests/a11y/axe.js`.

## The drawer

The left drawer is the site's primary navigation and the reason for most of the layout
code. Three behaviors, in order of how they're implemented:

**1. It sticks as you scroll, and stops at the footer.** This is pure CSS — no JavaScript
positions the drawer.

```
<div id="ucfhb">                  ← the university header, normal flow, scrolls away
<main class="brand-shell">        ← sticky containing block
  <aside class="brand-sidebar">   ← position: sticky, top: 0
  <div class="brand-content">
</main>
<footer>                          ← sibling, OUTSIDE the shell
```

A sticky element can't travel past the bottom of its parent, so the drawer unpins exactly
where `<main>` ends — which is where the footer begins. Two details are load-bearing:
`align-items: start` on the grid (the default `stretch` makes sticky a no-op), and
`overflow-x: clip` rather than `hidden` on `html` (an `overflow: hidden` ancestor silently
disables sticky on every descendant).

**2. The menu is one level deep.** Authored as a core Navigation block in the Site Editor —
one link per top-level brand page. Edit it under Appearance → Editor → Patterns → Brand
Sidebar.

**3. Sub-navigation comes from the page's H2s.** Never authored. `src/js/brand-nav.js`
finds the nav item matching the current URL, reads the `<h2>`s out of `.brand-content`,
builds a list, and injects it beneath that one item. An `IntersectionObserver` highlights
each entry as its heading passes through the upper third of the viewport.

**This makes H2s structurally significant.** An H2 is a sub-nav entry; use H3 for anything
that shouldn't appear in the drawer.

**The same list can also render into the page.** Insert the `ucf-brand/section-index` block
and each H2 becomes a numbered jump link, using the same anchors and the same "3.1" numbering
the H2 badges print. Nothing about the list is
authored — only the short description beside each entry, which is stored on the block keyed
by heading text. Rename a heading and its description is orphaned, by design.

**The anchor ids come from the server, not the browser.** `includes/headings.php` assigns
an `id` to every H2 during `render_block`, so it is already in the HTML that arrives. That
matters because search emits `/page/#heading` links from PHP: when the browser and the
server each derived slugs independently, the two disagreed and deep links landed at the top
of the page. One owner, one implementation.

The mobile toggle lives in `parts/mobile-bar.html` and is included by the three templates
that actually have a drawer (`page`, `front-page`, `search`). The other three templates have
no sidebar, so they deliberately have no toggle.

## Search

Search resolves to a _section_, not just a page: each result lists up to three matching H2s
beneath it, linked to their anchors, with the query highlighted in a snippet. If Relevanssi
is installed it ranks the pages; the theme picks the heading within each one. Without
Relevanssi, core's search picks the pages and everything else behaves the same.

Nothing is indexed or stored — sections are resolved from `post_content` at render time, so
no reindex is ever needed. Full write-up in [`docs/search.md`](docs/search.md).

## The Badge format

A **Badge** button on the rich-text toolbar wraps a run of text in a small uppercase chip,
in any paragraph, heading or list item. Clicking it opens a swatch popover — built from the
same `ColorPalette` component core's Highlight dialog uses — and the tones are mutually
exclusive. Each swatch resolves its fill from the theme palette by slug at runtime, so no
hex is hard-coded and a palette change flows straight through. Source in
`src/js/badge-format.js`, styling in `src/scss/_badge.scss`.

## The page hero

Every brand page opens with a full-bleed band: the featured image behind a scrim, carrying
the section eyebrow, the page title, an accent rule and the deck. It lives in
`templates/page.html` rather than in page content, so it is the same object on every page
and cannot be deleted or drift out of shape.

Nothing in it is typed twice. The eyebrow — `Brand Guidelines · Section 05` — is the
`ucf-brand/section-number` binding reading the same `ucf_brand_number` the drawer orders by,
so the hero and the menu can never disagree about which section this is. The image is the
featured image. The title is the page title.

**All of it is edited in the canvas.** Click the image to replace it, the title to retype it,
either line of copy to rewrite it — no sidebar round trip. The two lines of copy are core
paragraphs bound to the `ucf_brand_deck` and `ucf_brand_hero_note` post meta through core's
`core/post-meta` source, which is editable in place because that source ships a setter. One
binding is one string, so the deck is one paragraph and the note is the second; inline links
work in both. Nothing in the hero can be added, removed or reordered.

That took one small block, `ucf-brand/page-hero`. It holds no content — it is a container
that exists so the editor has a block _type_ to keep unlocked, because the allowlist that
decides what stays editable while a page is open matches by name (see below). Its
`templateLock: 'contentOnly'` then sorts its children: core keeps the ones that declare a
`role: "content"` attribute and disables the rest, which is why the separator is inert and
the eyebrow is too — the eyebrow's binding source is read-only, so core will not let it be
typed into.

**Contrast is the constraint.** The copy sits on a photograph, so nothing in the hero may
depend on the photo being dark. The `scrim` gradient runs to 0.85–0.95 alpha over the lower
half where the copy sits, which clears 4.5:1 for white type even against a white frame — and
the closing note is ordinary body copy rather than `muted`, because grey-on-photo is the one
thing this band cannot afford. A page with no featured image renders no image _and no
scrim_ — `core/post-featured-image` emits nothing at all — so what shows through is the
black fill `is-style-dark` supplies. Legible rather than broken.

### Pages open with the template showing

`includes/setup.php` sets `default-mode` to `template-locked` for the `page` post type, which is
the "Show template" state: the template renders in the editor and everything in it is
disabled except `core/post-title`, `core/post-featured-image` and `core/post-content`.

That allowlist is filterable, and `src/js/editor/page-hero-editable.js` adds `ucf-brand/page-hero` to it through
`editor.postContentBlockTypes`. **This is the only mechanism that keeps something in a
template editable while a page is open** — reach for it before inventing anything else. It
matches by block type, so the thing you name has to be a block of your own; allowlisting
`core/paragraph` would unlock every paragraph in every template.

Two more consequences. It is a **default**, not a lock — the per-user preference wins, so an
author who switches "Show template" off stays off. And anything meant to stay editable has
to live in `templates/page.html` directly: template parts and their children are disabled
outright in this mode, which is why the hero is inline in the template rather than a part.

## Authoring

1. Create a Page for each top-level section.
2. Add it to the Navigation block in the Brand Sidebar template part — one level, no children.
3. Set its Brand order in the Brand panel of the document sidebar.
4. Fill in the hero by clicking straight into it: replace the image, retype the title, write
   the deck and the closing note.
5. Write the page using H2s for its named subsections. The drawer updates itself.

## Blocks and patterns

**No page should contain a Custom HTML block.** Anything that looks like a component is
either a custom block, a pattern, or core blocks carrying a registered block style.

### Custom blocks (`src/blocks/` → `build/`)

| Block                      | What it's for                                                                 |
| -------------------------- | ----------------------------------------------------------------------------- |
| `ucf-brand/color-swatches` | The swatch grid. Accepts only Color Swatch children.                          |
| `ucf-brand/color-swatch`   | One color: chip, name, HEX/RGB/CMYK/Pantone, usage note, measured contrast.   |
| `ucf-brand/tabs`           | Tab set container. Ships the only front-end `viewScript` in the theme.        |
| `ucf-brand/tab`            | One tab: a label and a panel. Child of Tabs.                                  |
| `ucf-brand/tab-label`      | The clickable label. Child of Tab.                                            |
| `ucf-brand/tab-panel`      | The panel body, an open drop zone. Child of Tab.                              |
| `ucf-brand/page-hero`      | The page hero's container. Holds no content; locks and unlocks what it wraps. |

Every block is **static** — `save()` emits real markup and there is no `render.php`, so
none of them renders on the server. The swatch chip takes its color from a palette **slug**
via core's `has-{slug}-background-color` class rather than an inline hex, so a swatch
keeps tracking its token if that token's value ever changes.

Tabs are the exception to "nothing but markup": the saved output is a plain stack of
label/panel pairs, and `src/blocks/tabs/view.js` is the only thing that turns that stack
into tabs, above its breakpoint. Below it — and whenever the script does not run — the
stack _is_ the mobile layout, every panel visible. That script is registered as the block's
`viewScript`, so WordPress loads it only on pages that use the block.

These are written to lift into a distribution plugin unchanged: nothing in `src/blocks/`
references the theme, so the move is a copy of the folder plus the `register_block_type()`
loop in `includes/blocks.php`.

### Theme glue that is _not_ in `src/blocks/`

Three blocks are server-rendered and stay in `includes/`, with the data they render:

| Block                          | Renders                                                    |
| ------------------------------ | ---------------------------------------------------------- |
| `ucf-brand/section-nav`        | The drawer menu, from each page's Brand order.             |
| `ucf-brand/search-subsections` | The matching-H2 deep links beneath each search result.     |
| `ucf-brand/section-index`      | An on-page jump list of that page's H2s — the Index block. |

They are theme glue rather than distributable design blocks, which is why the static-only
rule applies to `src/blocks/` and not to them. The first two have a client-side stand-in in
`src/js/editor/dynamic-blocks.js` so the Site Editor draws them instead of an
"unsupported block" placeholder; `section-index` has a real editor half in
`src/js/editor/section-index.js`, because its per-entry descriptions are edited in place.

### Patterns (`patterns/`)

Patterns are filed in a compositional ladder, small to large — `ucf-brand-units` (single
primitives) → `ucf-brand-groups` (clusters of units) → `ucf-brand-sections` (full-width
bands) → `ucf-brand-pages` (whole-page layouts).

| Pattern                    | Category | Built from                                                    |
| -------------------------- | -------- | ------------------------------------------------------------- |
| `ucf-brand/detail-card`    | units    | Core Group. Accent header bar over an open body drop zone.    |
| `ucf-brand/list-card`      | units    | Core Group + Columns. Numbered rows divided by a bottom rule. |
| `ucf-brand/color-swatches` | groups   | The two blocks above, pre-filled with the six core colors.    |
| `ucf-brand/type-specimens` | groups   | Core Group + Paragraph. One row per typeface.                 |
| `ucf-brand/type-scale`     | groups   | Core Group + Paragraph, generated from a PHP array.           |
| `ucf-brand/section`        | sections | Core Group (`section`, alignfull). H2, intro, drop zone.      |

**A pattern is core blocks and nothing else.** Every pattern here is vanilla Gutenberg
markup — structure, spacing, borders and type all come from the blocks' own controls. The
only classes a pattern carries are the composition on its container and the role utilities
that bind its accent (`accent-fill`, `accent-text`, `hairline`), which is how a pattern
holds a look without holding a color. Drop any of them into a Dark or Bold Gold group and
it recolors; none of them names a token. See
[`docs/architecture.md`](docs/architecture.md#patterns-declare-structure-and-composition-never-color)
for the full rule.

A specimen row, for instance, is a Group with the `Type Specimen` block style, an eyebrow
paragraph, and a sample paragraph whose face and size are set through ordinary block
controls (`fontFamily`, `fontSize`) — an editor can build one from the inserter without
touching code.

`type-scale.php` renders each row _using the preset it documents_, so the specimen column
is a live read of `theme.json` — change a size there and the sample follows without anyone
editing the pattern. The `clamp()` values printed in the middle column are a separate,
hand-written transcription and **can** drift; update them alongside `theme.json`.

### Block styles

Registered in `includes/block-styles.php`, defined in `src/scss/`.

| Block                                    | Styles                                                                   |
| ---------------------------------------- | ------------------------------------------------------------------------ |
| Group                                    | `light`, `paper`, `dark`, `bold-gold` — each also in an `-accent` flavor |
| Group                                    | `on-dark`, `halftone`, `specimen`                                        |
| Separator                                | `accent-rule` — the short, heavy rule under a hero or section title      |
| Paragraph, Heading                       | `lead`, `eyebrow`, `meta`, `muted`                                       |
| Group, Columns, Paragraph, Heading, List | `reading-width`                                                          |
| Button                                   | `glyph`                                                                  |
| Accordion                                | `brand`                                                                  |

The four Group color pairs are the **compositions** — a background plus everything that has
to be true of what sits on it. Each declares a set of `--brand-*` roles (`accent`, `line`,
`body`, `lead`, `eyebrow`, `meta`, `link`, …) that every downstream rule reads instead of
naming a color, which is what lets a card invert correctly when it is nested in a darker
one. The `-accent` flavor adds a 3px rule on the leading edge. Defined by the `$treatments`
and `$compositions` maps in `src/scss/_compositions.scss`; adding a pair means a row in
each of those maps plus a row in `ucf_brand_register_callout_styles()`.

## Architecture notes

-   **Page content is never server-rendered.** Nothing in `src/blocks/` has a `render.php`,
    and no page holds a `core/pattern` reference — pages contain real block markup an editor
    can change. The two `render_callback` blocks above are chrome the theme generates from
    live data, not content.
-   **Distribution.** Blocks live in the theme for now because the design is still moving.
    See the note above on lifting them into a plugin.
-   The conventions — tokens first, then existing classes, then core block controls, and only
    then something new — are written up in [`docs/architecture.md`](docs/architecture.md).
