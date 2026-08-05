# UCF Brand Block Theme

Block theme for UCF brand documentation, modeled on [brand.utah.edu](https://brand.utah.edu/)
and the UCF Brand Hub prototype. This pass establishes the foundations — color, typography
and the drawer navigation. Components come later.

## Getting started

```bash
npm install
npm run build         # blocks -> build/, and src/scss/main.scss -> assets/css/main.css
npm run build:blocks  # blocks only  (wp-scripts)
npm run build:css     # stylesheet only  (sass)
npm run start         # watch blocks
npm run watch         # watch stylesheet
```

Both `build/` and `assets/css/main.css` are compiled **and committed**, so the theme can
be deployed without running a build. Never edit either by hand — edit the source and
rebuild.

## Linting and formatting

```bash
npm run lint:js       # ESLint over blocks/            (wp-scripts)
npm run lint:css      # stylelint over src/scss/        (wp-scripts)
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

## The drawer

The left drawer is the site's primary navigation and the reason for most of the layout
code. Three behaviors, in order of how they're implemented:

**1. It sticks as you scroll, and stops at the footer.** This is pure CSS — no JavaScript
positions the drawer.

```
<main class="brand-shell">        ← sticky containing block
  <aside class="brand-sidebar">   ← position: sticky
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

**3. Sub-navigation comes from the page's H2s.** Never authored. `assets/js/brand-nav.js`
finds the nav item matching the current URL, reads the `<h2>`s out of `.brand-content`,
builds a list, and injects it beneath that one item. An `IntersectionObserver` highlights
each entry as its heading passes through the upper third of the viewport.

**This makes H2s structurally significant.** An H2 is a sub-nav entry; use H3 for anything
that shouldn't appear in the drawer.

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
that exists so the editor has a block *type* to keep unlocked, because the allowlist that
decides what stays editable while a page is open matches by name (see below). Its
`templateLock: 'contentOnly'` then sorts its children: core keeps the ones that declare a
`role: "content"` attribute and disables the rest, which is why the separator is inert and
the eyebrow is too — the eyebrow's binding source is read-only, so core will not let it be
typed into.

**Contrast is the constraint.** The copy sits on a photograph, so nothing in the hero may
depend on the photo being dark. The `scrim` gradient runs to 0.85–0.95 alpha over the lower
half where the copy sits, which clears 4.5:1 for white type even against a white frame — and
the closing note is ordinary body copy rather than `muted`, because grey-on-photo is the one
thing this band cannot afford. A page with no featured image renders no image *and no
scrim* — `core/post-featured-image` emits nothing at all — so what shows through is the
black fill `is-style-dark` supplies. Legible rather than broken.

### Pages open with the template showing

`functions.php` sets `default-mode` to `template-locked` for the `page` post type, which is
the "Show template" state: the template renders in the editor and everything in it is
disabled except `core/post-title`, `core/post-featured-image` and `core/post-content`.

That allowlist is filterable, and `blocks/index.js` adds `ucf-brand/page-hero` to it through
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

### Custom blocks (`blocks/` → `build/`)

| Block                      | What it's for                                                               |
| -------------------------- | --------------------------------------------------------------------------- |
| `ucf-brand/color-swatches` | The swatch grid. Accepts only Color Swatch children.                        |
| `ucf-brand/color-swatch`   | One color: chip, name, HEX/RGB/CMYK/Pantone, usage note, measured contrast. |
| `ucf-brand/page-hero`      | The page hero's container. Holds no content; locks and unlocks what it wraps. |

Every block is **static** — `save()` emits real markup and there is no `render.php`, so
nothing renders on the server. The swatch chip takes its color from a palette **slug**
via core's `has-{slug}-background-color` class rather than an inline hex, so a swatch
keeps tracking its token if that token's value ever changes.

These are written to lift into a distribution plugin unchanged: nothing in `blocks/`
references the theme, so the move is a copy of the folder plus the `register_block_type()`
loop in `functions.php`.

### Patterns (`patterns/`)

Patterns are filed in a compositional ladder, small to large — `ucf-brand-units` (single
primitives) → `ucf-brand-groups` (clusters of units) → `ucf-brand-sections` (full-width
bands) → `ucf-brand-pages` (whole-page layouts).

| Pattern                    | Category | Built from                                                  |
| -------------------------- | -------- | ----------------------------------------------------------- |
| `ucf-brand/detail-card`    | units    | Core Group. Accent header bar over an open body drop zone.  |
| `ucf-brand/list-card`      | units    | Core Group + Columns. Numbered rows divided by a bottom rule. |
| `ucf-brand/color-swatches` | groups   | The two blocks above, pre-filled with the six core colors.  |
| `ucf-brand/index`          | groups   | Core Columns + Separator. A numbered index of a section.    |
| `ucf-brand/type-specimens` | groups   | Core Group + Paragraph. One row per typeface.               |
| `ucf-brand/type-scale`     | groups   | Core Group + Paragraph, generated from a PHP array.         |
| `ucf-brand/section`        | sections | Core Group (`section`, alignfull). H2, intro, drop zone.    |

**A pattern is core blocks and nothing else.** Every pattern here is vanilla Gutenberg
markup — structure, spacing, borders and type all come from the blocks' own controls. The
only classes a pattern carries are the composition on its container and the role utilities
that bind its accent (`accent-fill`, `accent-text`, `hairline`), which is how a pattern
holds a look without holding a color. Drop any of them into a Dark or Bold Gold group and
it recolors; none of them names a token. See `CLAUDE.md` for the full rule.

A specimen row, for instance, is a Group with the `Type Specimen` block style, an eyebrow
paragraph, and a sample paragraph whose face and size are set through ordinary block
controls (`fontFamily`, `fontSize`) — an editor can build one from the inserter without
touching code.

`type-scale.php` renders each row _using the preset it documents_, so the page is a live
read of `theme.json` rather than a transcription of it — change a size there and the demo
follows.

### Block styles

Registered in `functions.php`, defined in `src/scss/`.

| Block                | Styles                                                                  |
| -------------------- | ----------------------------------------------------------------------- |
| Group                | `light`, `paper`, `dark`, `bold-gold` — each also in an `-accent` flavor |
| Group                | `on-dark`, `halftone`, `specimen`                                       |
| Separator            | `accent-rule` — the short, heavy rule under a hero or section title      |
| Paragraph, Heading   | `lead`, `eyebrow`, `meta`, `muted`                                      |
| Group, Columns, Paragraph, Heading, List | `reading-width`                                     |
| Button               | `glyph`                                                                  |
| Accordion            | `brand`                                                                  |

The four Group color pairs are the **compositions** — a background plus everything that has
to be true of what sits on it. Each declares a set of `--brand-*` roles (`accent`, `line`,
`body`, `lead`, `eyebrow`, `meta`, `link`, …) that every downstream rule reads instead of
naming a color, which is what lets a card invert correctly when it is nested in a darker
one. The `-accent` flavor adds a 3px rule on the leading edge. Defined by the `$treatments`
and `$compositions` maps in `src/scss/_compositions.scss`; adding a pair means a row in
each of those maps plus a row in `ucf_brand_register_callout_styles()`.

## Architecture notes

-   **Nothing is server-rendered.** No `render.php`, no `render_callback`, and no
    `core/pattern` references in page content — pages hold real block markup an editor
    can change.
-   **Distribution.** Blocks live in the theme for now because the design is still moving.
    See the note above on lifting them into a plugin.
-   Follows the conventions in `ucf-wordpress-block-theme/CLAUDE.md`: tokens first, then
    existing classes, then core block controls, and only then something new.
