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

## Design tokens

Everything lives in `theme.json`. Nothing in this theme hard-codes a hex, a font stack or
a spacing value; SCSS reads tokens through `--wp--preset--*` / `--wp--custom--*` variables
mapped in `src/scss/_variables.scss`.

- **19 brand colors**, with `defaultPalette: false` and `custom: false`, so the editor
  offers brand tokens and nothing else — authors cannot pick an off-brand color.
- **Three typefaces**, self-hosted in `assets/fonts/` and declared as `fontFace` entries.
  No request ever goes to a third-party font CDN.
- **Fluid type scale** — every heading size is a `clamp()`, so there is no separate mobile
  scale to maintain.

### Swapping the typefaces

The prototype's Oswald / Montserrat / JetBrains Mono are **web stand-ins** for the licensed
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

## Authoring

1. Create a Page for each top-level section.
2. Add it to the Navigation block in the Brand Sidebar template part — one level, no children.
3. Write the page using H2s for its named subsections. The drawer updates itself.

## Blocks and patterns

**No page should contain a Custom HTML block.** Anything that looks like a component is
either a custom block, a pattern, or core blocks carrying a registered block style.

### Custom blocks (`blocks/` → `build/`)

| Block | What it's for |
|---|---|
| `ucf-brand/color-swatches` | The swatch grid. Accepts only Color Swatch children. |
| `ucf-brand/color-swatch` | One color: chip, name, HEX/RGB/CMYK/Pantone, usage note, measured contrast. |

Every block is **static** — `save()` emits real markup and there is no `render.php`, so
nothing renders on the server. The swatch chip takes its color from a palette **slug**
via core's `has-{slug}-background-color` class rather than an inline hex, so a swatch
keeps tracking its token if that token's value ever changes.

These are written to lift into a distribution plugin unchanged: nothing in `blocks/`
references the theme, so the move is a copy of the folder plus the `register_block_type()`
loop in `functions.php`.

### Patterns (`patterns/`)

| Pattern | Built from |
|---|---|
| `ucf-brand/color-swatches` | The two blocks above, pre-filled with the six core colors. |
| `ucf-brand/type-specimens` | Core Group + Paragraph. One row per typeface. |
| `ucf-brand/type-scale` | Core Group + Paragraph, generated from a PHP array. |

The type patterns use **no custom markup at all** — a specimen row is a Group with the
`Type Specimen` block style, an eyebrow paragraph, and a sample paragraph whose face and
size are set through ordinary block controls (`fontFamily`, `fontSize`). An editor can
build one from the inserter without touching code.

`type-scale.php` renders each row *using the preset it documents*, so the page is a live
read of `theme.json` rather than a transcription of it — change a size there and the demo
follows.

### Block styles

`on-dark`, `gold-edge`, `halftone`, `specimen` (Group); `lead`, `eyebrow`, `meta`
(Paragraph and Heading). Registered in `functions.php`, defined in `src/scss/`.

## Architecture notes

- **Nothing is server-rendered.** No `render.php`, no `render_callback`, and no
  `core/pattern` references in seeded content — pages hold real block markup an editor
  can change.
- **Distribution.** Blocks live in the theme for now because the design is still moving.
  See the note above on lifting them into a plugin.
- Follows the conventions in `ucf-wordpress-block-theme/CLAUDE.md`: tokens first, then
  existing classes, then core block controls, and only then something new.

## Local development content

`tools/seed/` holds a dev-only seeder that populates a local site with the eleven brand
sections. It is not part of the distributed theme.

```bash
wp --path=/path/to/wordpress --url=http://localhost/wordpress/brand/ \
   eval-file tools/seed/seed-brand.php
```

Idempotent — pages are matched by slug and updated in place, so re-running after editing
`tools/seed/pages/*.html` refreshes content without creating duplicates.
