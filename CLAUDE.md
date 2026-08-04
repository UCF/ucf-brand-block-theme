# UCF Brand Block Theme — working notes

Read `README.md` first for what the theme is and how the drawer works. This file covers
the rules to follow when changing it.

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
   `register_block_style()` in `functions.php` plus a partial in `src/scss/`.

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
-   **Core's constrained layout uses `margin-left: auto !important`.** Anything setting a
    narrower `max-width` gets centered as a side effect. The single override lives in
    `src/scss/_base.scss` — put new cases there rather than scattering `!important`.
-   **The footer must stay outside `<main>`.** That is the only thing making the drawer
    stop at the footer.

## Blocks

**Never use a Custom HTML (`core/html`) block in page content.** If content needs
structure that core blocks don't express, the answer is a custom block, a pattern, or a
registered block style — not raw markup pasted into a page. There is currently zero
`wp:html` in any seeded page; keep it that way.

Custom blocks live in `blocks/<name>/`, built by `wp-scripts` to `build/<name>/`, and
registered by the loop in `ucf_brand_register_blocks()`.

-   **Static only.** `save()` must emit real markup. No `render.php`, no `render_callback`.
-   Take colors by palette **slug** and apply core's `has-{slug}-background-color` /
    `has-{slug}-color` classes — never write an inline hex into `save()`.
-   Don't reference theme functions or paths from block sources; they must lift into a
    plugin unchanged.
-   `ucf-news-block-theme` uses `render.php` server rendering. That is **not** a precedent
    for this theme.

### Hand-writing block markup in patterns

Pattern PHP must serialize **exactly** what `save()` would produce or the editor flags
the block invalid. Class and inline-style _order_ doesn't matter (Gutenberg compares
class tokens as a set and parses style declarations), but presence and values do.

Verify by opening a seeded page in the block editor and asking the store directly:

```js
wp.data.select( 'core/block-editor' ).getBlocks(); // walk innerBlocks, check isValid
```

A page render is not a sufficient check — invalid blocks still render on the front end.

## Build and content

-   `npm run build` runs both halves: `wp-scripts` for `blocks/` → `build/`, and `sass` for
    `src/scss/main.scss` → `assets/css/main.css`. Both outputs are committed; never edit
    either directly. New SCSS partials must be `@use`d in `src/scss/main.scss`.
    Declarations alphabetical.
-   **Block CSS goes in `src/scss/`, not in the block folder.** One stylesheet pipeline —
    `wp-scripts` builds JS only. This keeps block styles in the editor automatically via
    `add_editor_style()`.
-   Webfonts come from `theme.json` `fontFace`, never from SCSS or `wp_enqueue_style`.
-   `add_editor_style()` loads the same `main.css` in the editor, so front-end and editor
    stay in parity. Keep it that way.
-   Pattern categories form a compositional ladder, small to large: `ucf-brand-units`
    (single primitives) → `ucf-brand-groups` (clusters of units) → `ucf-brand-sections`
    (full-width content bands) → `ucf-brand-pages` (whole-page layouts). Registered in
    `includes/patterns.php`. **Avoid the bare `ucf-sections` slug** — it is reserved by the
    UCF Section plugin.
-   `tools/seed/` is dev-only local content, not part of the distributed theme.

## H2s are structural

Sub-navigation is generated from each page's `<h2>` elements. An H2 is a drawer entry.
Use H3 for anything that should not appear in the drawer.
